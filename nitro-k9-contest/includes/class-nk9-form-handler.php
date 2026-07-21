<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NK9_Form_Handler {

	const NONCE_ACTION = 'nk9_submit_entry';
	const ALLOWED_VIDEO_MIMES = array(
		'mp4'  => 'video/mp4',
		'mov'  => 'video/quicktime',
		'webm' => 'video/webm',
		'avi'  => 'video/x-msvideo',
		'm4v'  => 'video/x-m4v',
	);

	public static function register() {
		add_action( 'admin_post_nk9_submit_entry', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_nopriv_nk9_submit_entry', array( __CLASS__, 'handle' ) );
	}

	public static function handle() {
		$redirect_url = wp_get_referer() ? wp_get_referer() : home_url( '/' );

		if ( ! isset( $_POST['nk9_nonce'] ) || ! wp_verify_nonce( $_POST['nk9_nonce'], self::NONCE_ACTION ) ) {
			self::fail( $redirect_url, 'Security check failed. Please reload the page and try again.' );
		}

		// Honeypot: real users never fill this hidden field.
		if ( ! empty( $_POST['nk9_website'] ) ) {
			self::fail( $redirect_url, 'Submission rejected.' );
		}

		$settings = NK9_Settings::get();

		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
		$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
		$dog_name   = isset( $_POST['dog_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dog_name'] ) ) : '';
		$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$method     = isset( $_POST['entry_method'] ) ? sanitize_key( $_POST['entry_method'] ) : '';
		$instagram  = isset( $_POST['instagram_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['instagram_url'] ) ) ) : '';
		$agree      = ! empty( $_POST['agree_rules'] );

		$form_values = compact( 'first_name', 'last_name', 'dog_name', 'email', 'method', 'instagram' );

		if ( ! $first_name || ! $last_name || ! $dog_name || ! $email || ! is_email( $email ) ) {
			self::fail( $redirect_url, 'Please fill in your first name, last name, dog\'s name, and a valid email address.', $form_values );
		}

		if ( ! $agree ) {
			self::fail( $redirect_url, 'You must confirm that you have read and agree to the Official Rules to enter.', $form_values );
		}

		if ( ! in_array( $method, array( 'instagram', 'video' ), true ) ) {
			self::fail( $redirect_url, 'Please choose how you\'d like to submit your clip.', $form_values );
		}

		if ( 'instagram' === $method ) {
			if ( ! $instagram || false === strpos( wp_parse_url( $instagram, PHP_URL_HOST ) ?? '', 'instagram.com' ) ) {
				self::fail( $redirect_url, 'Please enter a valid Instagram post URL.', $form_values );
			}
		}

		if ( ! self::within_entry_period( $settings ) ) {
			self::fail( $redirect_url, 'The entry period for the Art of the Leash Clip Contest is closed.', $form_values );
		}

		if ( self::entry_count( $email, $dog_name ) >= (int) $settings['max_entries_per_dog'] ) {
			self::fail( $redirect_url, sprintf( 'You\'ve already reached the maximum of %d entries for this dog.', (int) $settings['max_entries_per_dog'] ), $form_values );
		}

		$dropbox_path = null;
		$shared_link  = null;
		$orig_name    = null;

		if ( 'video' === $method ) {
			$upload_result = self::process_video_upload( $first_name, $last_name, $dog_name, $settings );
			if ( is_wp_error( $upload_result ) ) {
				self::fail( $redirect_url, $upload_result->get_error_message(), $form_values );
			}
			$dropbox_path = $upload_result['path'];
			$shared_link  = $upload_result['shared_link'];
			$orig_name    = $upload_result['original_filename'];
		}

		$entry_id = self::save_entry(
			array(
				'first_name'          => $first_name,
				'last_name'           => $last_name,
				'dog_name'            => $dog_name,
				'email'               => $email,
				'entry_method'        => $method,
				'instagram_url'       => 'instagram' === $method ? $instagram : null,
				'dropbox_path'        => $dropbox_path,
				'dropbox_shared_link' => $shared_link,
				'original_filename'   => $orig_name,
				'agreed_rules'        => 1,
				'ip_address'          => self::get_ip(),
				'user_agent'          => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 500 ) : '',
			)
		);

		self::send_notification( $settings, compact( 'first_name', 'last_name', 'dog_name', 'email', 'method', 'instagram', 'shared_link' ), $entry_id );

		$redirect = add_query_arg( 'nk9_status', 'success', self::strip_status_args( $redirect_url ) );
		wp_safe_redirect( $redirect . '#nk9-contest-form' );
		exit;
	}

	private static function process_video_upload( $first_name, $last_name, $dog_name, $settings ) {
		if ( empty( $_FILES['video_file'] ) || UPLOAD_ERR_NO_FILE === $_FILES['video_file']['error'] ) {
			return new WP_Error( 'nk9_no_file', 'Please upload a video file, or choose the Instagram option instead.' );
		}

		$file = $_FILES['video_file'];

		if ( UPLOAD_ERR_OK !== $file['error'] ) {
			return new WP_Error( 'nk9_upload_error', 'There was a problem uploading your video. Please try again or use the Instagram entry option.' );
		}

		$max_bytes = (int) $settings['max_video_mb'] * 1024 * 1024;
		if ( $file['size'] > $max_bytes ) {
			return new WP_Error( 'nk9_file_too_large', sprintf( 'That video is too large. Please keep uploads under %dMB, or post it to Instagram instead.', (int) $settings['max_video_mb'] ) );
		}

		$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( ! isset( self::ALLOWED_VIDEO_MIMES[ $ext ] ) ) {
			return new WP_Error( 'nk9_bad_type', 'Please upload a video file (MP4, MOV, WEBM, AVI, or M4V).' );
		}

		$finfo     = finfo_open( FILEINFO_MIME_TYPE );
		$real_mime = finfo_file( $finfo, $file['tmp_name'] );
		finfo_close( $finfo );

		if ( 0 !== strpos( $real_mime, 'video/' ) ) {
			return new WP_Error( 'nk9_bad_type', 'That file doesn\'t look like a video. Please upload a valid video file.' );
		}

		$safe_name = sanitize_file_name( "{$last_name}-{$first_name}-{$dog_name}-" . gmdate( 'Ymd-His' ) . ".{$ext}" );

		$result = NK9_Dropbox::upload_file( $file['tmp_name'], $safe_name );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result['original_filename'] = sanitize_file_name( $file['name'] );
		return $result;
	}

	private static function within_entry_period( $settings ) {
		try {
			$tz    = new DateTimeZone( 'America/Los_Angeles' );
			$now   = new DateTime( 'now', $tz );
			$start = new DateTime( $settings['entry_start'], $tz );
			$end   = new DateTime( $settings['entry_end'], $tz );
			return $now >= $start && $now <= $end;
		} catch ( Exception $e ) {
			return true; // Fail open on a misconfigured date rather than blocking all entries.
		}
	}

	private static function entry_count( $email, $dog_name ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nk9_contest_entries';
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE LOWER(email) = LOWER(%s) AND LOWER(dog_name) = LOWER(%s)",
				$email,
				$dog_name
			)
		);
	}

	private static function save_entry( $data ) {
		global $wpdb;
		$table            = $wpdb->prefix . 'nk9_contest_entries';
		$data['created_at'] = current_time( 'mysql' );
		$wpdb->insert( $table, $data );
		return $wpdb->insert_id;
	}

	private static function send_notification( $settings, $entry, $entry_id ) {
		$to      = $settings['notification_email'];
		$subject = sprintf( '[Nitro K-9 Contest] New entry: %s & %s', $entry['first_name'] . ' ' . $entry['last_name'], $entry['dog_name'] );

		$lines = array(
			'A new "Art of the Leash" Clip Contest entry was submitted.',
			'',
			'Entrant: ' . $entry['first_name'] . ' ' . $entry['last_name'],
			'Dog: ' . $entry['dog_name'],
			'Email: ' . $entry['email'],
			'Method: ' . ucfirst( $entry['method'] ),
		);

		if ( 'instagram' === $entry['method'] ) {
			$lines[] = 'Instagram URL: ' . $entry['instagram'];
		} else {
			$lines[] = 'Video uploaded to Dropbox.';
			if ( ! empty( $entry['shared_link'] ) ) {
				$lines[] = 'Dropbox link: ' . $entry['shared_link'];
			}
		}

		$lines[] = '';
		$lines[] = 'View and manage all entries: ' . admin_url( 'admin.php?page=nk9-contest-entries' );

		wp_mail( $to, $subject, implode( "\n", $lines ) );
	}

	private static function get_ip() {
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return '';
	}

	private static function strip_status_args( $url ) {
		return remove_query_arg( array( 'nk9_status', 'nk9_msg', 'nk9_token' ), $url );
	}

	private static function fail( $redirect_url, $message, $form_values = array() ) {
		$token = wp_generate_password( 12, false );
		set_transient( 'nk9_form_' . $token, array( 'message' => $message, 'values' => $form_values ), 5 * MINUTE_IN_SECONDS );

		$redirect = add_query_arg(
			array(
				'nk9_status' => 'error',
				'nk9_token'  => $token,
			),
			self::strip_status_args( $redirect_url )
		);

		wp_safe_redirect( $redirect . '#nk9-contest-form' );
		exit;
	}
}
