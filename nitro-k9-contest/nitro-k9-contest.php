<?php
/**
 * Plugin Name: Nitro K-9 Contest
 * Description: "Art of the Leash" Clip Contest entry form -- collects entries, uploads video clips to Dropbox, emails a notification on every submission, and displays the official rules for Nitro K-9 LLC.
 * Version: 1.0.0
 * Author: Nitro K-9 LLC
 * Text Domain: nitro-k9-contest
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NITRO_K9_CONTEST_VERSION', '1.0.0' );
define( 'NITRO_K9_CONTEST_FILE', __FILE__ );
define( 'NITRO_K9_CONTEST_DIR', plugin_dir_path( __FILE__ ) );
define( 'NITRO_K9_CONTEST_URL', plugin_dir_url( __FILE__ ) );

require_once NITRO_K9_CONTEST_DIR . 'includes/class-nk9-activator.php';
require_once NITRO_K9_CONTEST_DIR . 'includes/class-nk9-settings.php';
require_once NITRO_K9_CONTEST_DIR . 'includes/class-nk9-dropbox.php';
require_once NITRO_K9_CONTEST_DIR . 'includes/class-nk9-entries-table.php';
require_once NITRO_K9_CONTEST_DIR . 'includes/class-nk9-entries-admin.php';
require_once NITRO_K9_CONTEST_DIR . 'includes/class-nk9-form-handler.php';
require_once NITRO_K9_CONTEST_DIR . 'includes/class-nk9-shortcode.php';
require_once NITRO_K9_CONTEST_DIR . 'includes/class-nk9-plugin.php';

register_activation_hook( __FILE__, array( 'NK9_Activator', 'activate' ) );

NK9_Plugin::instance();
