<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NK9_Plugin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init', array( 'NK9_Settings', 'register' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_nk9_export_entries', array( 'NK9_Entries_Admin', 'handle_export' ) );

		NK9_Shortcode::register();
		NK9_Form_Handler::register();
	}

	public function register_admin_menu() {
		add_menu_page(
			'Nitro K-9 Contest',
			'Contest Entries',
			'manage_options',
			'nk9-contest-entries',
			array( 'NK9_Entries_Admin', 'render_page' ),
			'dashicons-awards',
			26
		);

		add_submenu_page(
			'nk9-contest-entries',
			'Entries',
			'Entries',
			'manage_options',
			'nk9-contest-entries',
			array( 'NK9_Entries_Admin', 'render_page' )
		);

		add_submenu_page(
			'nk9-contest-entries',
			'Contest Settings',
			'Settings',
			'manage_options',
			'nk9-contest-settings',
			array( 'NK9_Settings', 'render_page' )
		);
	}

	public function enqueue_assets() {
		global $post;

		$has_shortcode = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'nitro_k9_contest' );

		if ( ! $has_shortcode ) {
			return;
		}

		wp_enqueue_style(
			'nk9-contest-fonts',
			'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap',
			array(),
			null
		);

		wp_enqueue_style(
			'nk9-contest',
			NITRO_K9_CONTEST_URL . 'assets/css/contest.css',
			array( 'nk9-contest-fonts' ),
			NITRO_K9_CONTEST_VERSION
		);

		wp_enqueue_script(
			'nk9-contest',
			NITRO_K9_CONTEST_URL . 'assets/js/contest.js',
			array(),
			NITRO_K9_CONTEST_VERSION,
			true
		);
	}
}
