<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NK9_Settings {

	const OPTION_KEY = 'nk9_contest_settings';

	public static function defaults() {
		return array(
			'notification_email'   => 'remorrison93@gmail.com',
			'entry_start'          => '2026-07-24 00:00:00',
			'entry_end'            => '2026-08-19 23:59:59',
			'max_entries_per_dog'  => 3,
			'max_video_mb'         => 200,
			'instagram_handle'     => '@nitrok9',
			'hashtag'              => '#NitroK9Giveaway',
			'telegram_group_url'   => '',
			'dropbox_app_key'      => '',
			'dropbox_app_secret'   => '',
			'dropbox_refresh_token' => '',
			'dropbox_folder_path'  => '/Nitro K9 Contest Entries',
		);
	}

	public static function get() {
		$saved = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( $saved, self::defaults() );
	}

	public static function get_field( $key ) {
		$settings = self::get();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	public static function register() {
		register_setting( 'nk9_contest_settings_group', self::OPTION_KEY, array( __CLASS__, 'sanitize' ) );

		add_settings_section( 'nk9_general', 'Contest Settings', '__return_false', 'nk9-contest-settings' );

		self::field( 'notification_email', 'Notification email', 'email' );
		self::field( 'entry_start', 'Entry period start (site timezone, e.g. 2026-07-24 00:00:00)', 'text' );
		self::field( 'entry_end', 'Entry period end (Pacific Time, e.g. 2026-08-19 23:59:59)', 'text' );
		self::field( 'max_entries_per_dog', 'Max entries per email + dog name', 'number' );
		self::field( 'max_video_mb', 'Max video upload size (MB)', 'number' );
		self::field( 'instagram_handle', 'Official Instagram handle', 'text' );
		self::field( 'hashtag', 'Required hashtag', 'text' );
		self::field( 'telegram_group_url', 'Telegram group URL (optional link shown on page)', 'text' );

		add_settings_section( 'nk9_dropbox', 'Dropbox Integration', array( __CLASS__, 'dropbox_section_intro' ), 'nk9-contest-settings' );

		self::field( 'dropbox_app_key', 'Dropbox App key', 'text', 'nk9_dropbox' );
		self::field( 'dropbox_app_secret', 'Dropbox App secret', 'password', 'nk9_dropbox' );
		self::field( 'dropbox_refresh_token', 'Dropbox refresh token', 'password', 'nk9_dropbox' );
		self::field( 'dropbox_folder_path', 'Dropbox upload folder path', 'text', 'nk9_dropbox' );
	}

	public static function dropbox_section_intro() {
		echo '<p>See the plugin README for step-by-step instructions on creating a Dropbox App and generating a refresh token.</p>';
	}

	private static function field( $key, $label, $type = 'text', $section = 'nk9_general' ) {
		add_settings_field(
			$key,
			$label,
			array( __CLASS__, 'render_field' ),
			'nk9-contest-settings',
			$section,
			array(
				'key'  => $key,
				'type' => $type,
			)
		);
	}

	public static function render_field( $args ) {
		$settings = self::get();
		$key      = $args['key'];
		$type     = $args['type'];
		$value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		$name     = self::OPTION_KEY . "[{$key}]";

		if ( 'number' === $type ) {
			printf(
				'<input type="number" name="%1$s" value="%2$s" class="small-text" />',
				esc_attr( $name ),
				esc_attr( $value )
			);
		} elseif ( 'password' === $type ) {
			printf(
				'<input type="password" name="%1$s" value="%2$s" class="regular-text" autocomplete="off" />',
				esc_attr( $name ),
				esc_attr( $value )
			);
		} else {
			printf(
				'<input type="%1$s" name="%2$s" value="%3$s" class="regular-text" />',
				'email' === $type ? 'email' : 'text',
				esc_attr( $name ),
				esc_attr( $value )
			);
		}
	}

	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$output   = array();

		$output['notification_email']    = sanitize_email( $input['notification_email'] ?? $defaults['notification_email'] );
		$output['entry_start']           = sanitize_text_field( $input['entry_start'] ?? $defaults['entry_start'] );
		$output['entry_end']             = sanitize_text_field( $input['entry_end'] ?? $defaults['entry_end'] );
		$output['max_entries_per_dog']   = max( 1, intval( $input['max_entries_per_dog'] ?? $defaults['max_entries_per_dog'] ) );
		$output['max_video_mb']          = max( 1, intval( $input['max_video_mb'] ?? $defaults['max_video_mb'] ) );
		$output['instagram_handle']      = sanitize_text_field( $input['instagram_handle'] ?? $defaults['instagram_handle'] );
		$output['hashtag']               = sanitize_text_field( $input['hashtag'] ?? $defaults['hashtag'] );
		$output['telegram_group_url']    = esc_url_raw( $input['telegram_group_url'] ?? '' );
		$output['dropbox_app_key']       = sanitize_text_field( $input['dropbox_app_key'] ?? '' );
		$output['dropbox_app_secret']    = sanitize_text_field( $input['dropbox_app_secret'] ?? '' );
		$output['dropbox_refresh_token'] = sanitize_text_field( $input['dropbox_refresh_token'] ?? '' );
		$output['dropbox_folder_path']   = '/' . ltrim( sanitize_text_field( $input['dropbox_folder_path'] ?? $defaults['dropbox_folder_path'] ), '/' );

		return $output;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1>Nitro K-9 Contest Settings</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'nk9_contest_settings_group' );
				do_settings_sections( 'nk9-contest-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
