<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NK9_Shortcode {

	public static function register() {
		add_shortcode( 'nitro_k9_contest', array( __CLASS__, 'render' ) );
	}

	public static function render( $atts = array() ) {
		$settings = NK9_Settings::get();

		$status  = isset( $_GET['nk9_status'] ) ? sanitize_key( $_GET['nk9_status'] ) : '';
		$message = '';
		$values  = array();

		if ( 'error' === $status && ! empty( $_GET['nk9_token'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_GET['nk9_token'] ) );
			$data  = get_transient( 'nk9_form_' . $token );
			if ( $data ) {
				$message = $data['message'];
				$values  = $data['values'];
				delete_transient( 'nk9_form_' . $token );
			}
		}

		ob_start();
		include NITRO_K9_CONTEST_DIR . 'includes/templates/contest-page.php';
		return ob_get_clean();
	}
}
