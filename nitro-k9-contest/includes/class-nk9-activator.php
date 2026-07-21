<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NK9_Activator {

	public static function activate() {
		self::create_table();
		self::seed_default_settings();
	}

	private static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'nk9_contest_entries';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			first_name VARCHAR(100) NOT NULL,
			last_name VARCHAR(100) NOT NULL,
			dog_name VARCHAR(100) NOT NULL,
			email VARCHAR(190) NOT NULL,
			entry_method VARCHAR(20) NOT NULL,
			instagram_url TEXT NULL,
			dropbox_path VARCHAR(500) NULL,
			dropbox_shared_link TEXT NULL,
			original_filename VARCHAR(255) NULL,
			agreed_rules TINYINT(1) NOT NULL DEFAULT 0,
			ip_address VARCHAR(45) NULL,
			user_agent VARCHAR(500) NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'received',
			notes TEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY email (email),
			KEY dog_name (dog_name),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'nk9_contest_db_version', NITRO_K9_CONTEST_VERSION );
	}

	private static function seed_default_settings() {
		if ( false === get_option( 'nk9_contest_settings' ) ) {
			add_option( 'nk9_contest_settings', NK9_Settings::defaults() );
		}
	}
}
