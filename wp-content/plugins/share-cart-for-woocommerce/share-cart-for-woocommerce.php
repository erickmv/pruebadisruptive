<?php
/**
 * Plugin Name: Share Cart for WooCommerce
 * Description: Share Cart URL for WooCommerce enables customers to share their cart URL directly from the WooCommerce cart page.
 * Version: 1.0
 * Author: Nitya Saha
 * Author URI: https://profiles.wordpress.org/nityasaha/
 * Text Domain: share-cart-for-woocommerce
 * Requires plugins: woocommerce
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( esc_html__( "No direct access!", 'share-cart-for-woocommerce' ) );
}

define( 'SCURL_VERSION', '1.0');
define( 'SCURL_PLUGIN_PATH', plugin_dir_url(__FILE__) );

/**
 * Plugin activation hook.
 * Checks if WooCommerce is active, otherwise deactivates this plugin.
 * Also sets default options.
 */
function scurl_plugin_activation() {
    // Check if WooCommerce is active.
    if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( esc_html__( 'This plugin requires WooCommerce to be installed and active.', 'share-cart-for-woocommerce' ) );
    }
}
register_activation_hook( __FILE__, 'scurl_plugin_activation' );

if ( ! class_exists( 'SCURL_Main' ) ) {
    class SCURL_Main {

        private $plugin_basename;

        public function __construct() {
            // Get the plugin basename.
            $this->plugin_basename = plugin_basename( __FILE__ );
            // Initialize the plugin when plugins are loaded.
            add_action( 'plugins_loaded', array( $this, 'init' ) );
        }

        public function init() {
            // Ensure WooCommerce is active before running plugin code.
            if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                return;
            } else {
                require_once plugin_dir_path( __FILE__ ) . 'includes/setting.php';
                require_once plugin_dir_path( __FILE__ ) . 'includes/share-cart-url.php';
            }

            // Set default option for button position if not already set.
            if ( false === get_option( 'scurl_button_position' ) ) {
                update_option( 'scurl_button_position', 'woocommerce_before_cart_table' );
            }

            // Add settings link on the plugins page.
            add_filter( 'plugin_action_links_' . $this->plugin_basename, array( $this, 'insert_view_logs_link' ) );
            add_filter( 'plugin_row_meta', array( $this, 'addon_plugin_links' ), 10, 2 );
        }

        /**
         * Add a settings link to the plugin's action links.
         *
         * @param array $links
         * @return array
         */
        public function insert_view_logs_link( $links ) {
            $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=share_cart_url' ) ) . '">' . esc_html__( 'Settings', 'share-cart-for-woocommerce' ) . '</a>';
            array_unshift( $links, $settings_link );
            return $links;
        }

        public function addon_plugin_links( $links, $file ) {
            if ( $file === $this->plugin_basename ) {
                $links[] = __( '<a href="https://buymeacoffee.com/nityasaha">Donate</a>', 'share-cart-for-woocommerce' );
                $links[] = __( 'Made with Love ❤️', 'share-cart-for-woocommerce' );
            }
    
            return $links;
        }
    }
}

// Instantiate the main plugin class.
new SCURL_Main();
