<?php
/**
 * Plugin Name: Mewshtari Email in Order for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/mewshtari-email-in-order-for-woocommerce
 * Description: Dynamically sends custom status-mapped HTML emails to customers from WooCommerce orders.
 * Version: 1.2.0
 * Author: micromax
 * Text Domain: mewshtari-email-in-order-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * Requires Plugins: woocommerce
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

// Define Plugin Constants.
define( 'MEW_EMAIL_ORDER_VERSION', '1.2.0' );
define( 'MEW_EMAIL_ORDER_PATH', plugin_dir_path( __FILE__ ) );
define( 'MEW_EMAIL_ORDER_URL', plugin_dir_url( __FILE__ ) );

// Load Split Component Classes.
require_once MEW_EMAIL_ORDER_PATH . 'includes/class-mewshtari-email-in-order.php';
require_once MEW_EMAIL_ORDER_PATH . 'includes/class-mewshtari-email-in-order-admin.php';
require_once MEW_EMAIL_ORDER_PATH . 'includes/class-mewshtari-email-in-order-metabox.php';
require_once MEW_EMAIL_ORDER_PATH . 'includes/class-mewshtari-email-in-order-injector.php';

// Initialize the plugin class inside the plugins_loaded hook, ensuring WooCommerce is fully active.
add_action( 'plugins_loaded', 'mewshtari_email_in_order_init' );

function mewshtari_email_in_order_init() {
    if ( class_exists( 'WooCommerce' ) ) {
        Mewshtari_Email_In_Order::get_instance();
    }
}