<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Minimal Dropbox API v2 client: refreshes a long-lived refresh token into a
 * short-lived access token, then streams a local file to a Dropbox path via
 * a chunked upload session (keeps memory use flat regardless of file size).
 */
class NK9_Dropbox {

	const TOKEN_TRANSIENT = 'nk9_dropbox_access_token';
	const CHUNK_SIZE      = 8 * 1024 * 1024; // 8MB per chunk for session upload.

	/**
	 * @return string|WP_Error Access token or error.
	 */
	public static function get_access_token() {
		$cached = get_transient( self::TOKEN_TRANSIENT );
		if ( $cached ) {
			return $cached;
		}

		$settings = NK9_Settings::get();

		if ( empty( $settings['dropbox_app_key'] ) || empty( $settings['dropbox_app_secret'] ) || empty( $settings['dropbox_refresh_token'] ) ) {
			return new WP_Error( 'nk9_dropbox_not_configured', 'Dropbox is not configured. Add App key, App secret, and refresh token under Nitro K-9 Contest settings.' );
		}

		$response = wp_remote_post(
			'https://api.dropboxapi.com/oauth2/token',
			array(
				'timeout' => 30,
				'body'    => array(
					'grant_type'    => 'refresh_token',
					'refresh_token' => $settings['dropbox_refresh_token'],
					'client_id'     => $settings['dropbox_app_key'],
					'client_secret' => $settings['dropbox_app_secret'],
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['access_token'] ) ) {
			$message = $body['error_description'] ?? 'Unknown Dropbox authentication error.';
			return new WP_Error( 'nk9_dropbox_auth_failed', $message );
		}

		$expires_in = isset( $body['expires_in'] ) ? intval( $body['expires_in'] ) : 3600;
		set_transient( self::TOKEN_TRANSIENT, $body['access_token'], max( 60, $expires_in - 120 ) );

		return $body['access_token'];
	}

	/**
	 * Upload a local file to Dropbox and return a shared link.
	 *
	 * @param string $local_path   Path to the file on local disk (e.g. PHP tmp upload).
	 * @param string $dropbox_name Destination filename (no path).
	 * @return array|WP_Error { 'path' => string, 'shared_link' => string }
	 */
	public static function upload_file( $local_path, $dropbox_name ) {
		$access_token = self::get_access_token();
		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		$settings      = NK9_Settings::get();
		$folder        = rtrim( $settings['dropbox_folder_path'], '/' );
		$dropbox_path  = $folder . '/' . $dropbox_name;

		if ( false === filesize( $local_path ) ) {
			return new WP_Error( 'nk9_dropbox_file_missing', 'Uploaded file could not be read from the server.' );
		}

		// Always stream in small chunks rather than reading the whole file into
		// memory, since shared hosting PHP memory limits can't reliably hold a
		// multi-hundred-MB video as a string.
		$result = self::session_upload( $access_token, $local_path, $dropbox_path );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$shared_link = self::create_shared_link( $access_token, $dropbox_path );

		return array(
			'path'        => $dropbox_path,
			'shared_link' => is_wp_error( $shared_link ) ? '' : $shared_link,
		);
	}

	private static function session_upload( $access_token, $local_path, $dropbox_path ) {
		$handle = fopen( $local_path, 'rb' );
		if ( ! $handle ) {
			return new WP_Error( 'nk9_dropbox_open_failed', 'Could not open uploaded file for reading.' );
		}

		// Start session with the first chunk.
		$first_chunk = fread( $handle, self::CHUNK_SIZE );
		$start_resp  = wp_remote_post(
			'https://content.dropboxapi.com/2/files/upload_session/start',
			array(
				'timeout' => 120,
				'headers' => array(
					'Authorization'   => 'Bearer ' . $access_token,
					'Content-Type'    => 'application/octet-stream',
					'Dropbox-API-Arg' => wp_json_encode( array( 'close' => false ) ),
				),
				'body'    => $first_chunk,
			)
		);

		$start_body = self::check_response( $start_resp );
		if ( is_wp_error( $start_body ) ) {
			fclose( $handle );
			return $start_body;
		}

		$session_id = $start_body['session_id'];
		$offset     = strlen( $first_chunk );

		// The whole file fit in the first chunk: finish immediately with an empty body.
		if ( feof( $handle ) ) {
			$finish_resp = wp_remote_post(
				'https://content.dropboxapi.com/2/files/upload_session/finish',
				array(
					'timeout' => 120,
					'headers' => array(
						'Authorization'   => 'Bearer ' . $access_token,
						'Content-Type'    => 'application/octet-stream',
						'Dropbox-API-Arg' => wp_json_encode(
							array(
								'cursor' => array(
									'session_id' => $session_id,
									'offset'     => $offset,
								),
								'commit' => array(
									'path'       => $dropbox_path,
									'mode'       => 'add',
									'autorename' => true,
									'mute'       => false,
								),
							)
						),
					),
					'body'    => '',
				)
			);
			fclose( $handle );
			return self::check_response( $finish_resp );
		}

		while ( ! feof( $handle ) ) {
			$chunk       = fread( $handle, self::CHUNK_SIZE );
			$is_last     = feof( $handle );
			$cursor      = array(
				'session_id' => $session_id,
				'offset'     => $offset,
			);

			if ( $is_last ) {
				$finish_resp = wp_remote_post(
					'https://content.dropboxapi.com/2/files/upload_session/finish',
					array(
						'timeout' => 120,
						'headers' => array(
							'Authorization'   => 'Bearer ' . $access_token,
							'Content-Type'    => 'application/octet-stream',
							'Dropbox-API-Arg' => wp_json_encode(
								array(
									'cursor' => $cursor,
									'commit' => array(
										'path'       => $dropbox_path,
										'mode'       => 'add',
										'autorename' => true,
										'mute'       => false,
									),
								)
							),
						),
						'body'    => $chunk,
					)
				);
				fclose( $handle );
				return self::check_response( $finish_resp );
			}

			$append_resp = wp_remote_post(
				'https://content.dropboxapi.com/2/files/upload_session/append_v2',
				array(
					'timeout' => 120,
					'headers' => array(
						'Authorization'   => 'Bearer ' . $access_token,
						'Content-Type'    => 'application/octet-stream',
						'Dropbox-API-Arg' => wp_json_encode( array( 'cursor' => $cursor, 'close' => false ) ),
					),
					'body'    => $chunk,
				)
			);

			$append_check = self::check_response( $append_resp, true );
			if ( is_wp_error( $append_check ) ) {
				fclose( $handle );
				return $append_check;
			}

			$offset += strlen( $chunk );
		}

		fclose( $handle );
		return new WP_Error( 'nk9_dropbox_session_incomplete', 'Upload session ended unexpectedly.' );
	}

	private static function create_shared_link( $access_token, $dropbox_path ) {
		$response = wp_remote_post(
			'https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings',
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( array( 'path' => $dropbox_path ) ),
			)
		);

		$body = self::check_response( $response );
		if ( is_wp_error( $body ) ) {
			// Fall back to listing existing shared links (link may already exist on autorename collisions).
			return '';
		}

		return $body['url'] ?? '';
	}

	private static function check_response( $response, $allow_empty_body = false ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$message = is_array( $body ) && isset( $body['error_summary'] ) ? $body['error_summary'] : 'Dropbox API error (HTTP ' . $code . ').';
			return new WP_Error( 'nk9_dropbox_api_error', $message );
		}

		if ( ! is_array( $body ) && ! $allow_empty_body ) {
			return new WP_Error( 'nk9_dropbox_bad_response', 'Unexpected response from Dropbox.' );
		}

		return $body ?: array();
	}
}
