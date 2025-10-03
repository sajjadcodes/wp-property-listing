<?php

/**
 * Plugin Name: WP Property Listing
 * Plugin URI: https://sajjadcodes.com/plugins/wp-property-listing
 * Description: A comprehensive property listings management plugin with custom post types, AJAX search, and CSV export functionality.
 * Version: 1.0.0
 * Author: Sajad Hussain
 * Author URI: https://sajjadcodes.com
 * License: GPL v2 or later
 * Text Domain: wp-property-listing
 */

// ============================================
// SECURITY: Exit if accessed directly
// ============================================

if (!defined('ABSPATH')) {
    exit; // Exit if ABSPATH is not defined (not loaded by WordPress)
}

// ============================================
// DEFINE CONSTANTS
// ============================================


// Plugin version - useful for cache busting CSS/JS
define('WPPL_VERSION', '1.0.0');

// Full path to THIS file (wp-property-listing.php)
// Example: /var/www/html/wp-content/plugins/wp-property-listing/wp-property-listing.php
define('WPPL_PLUGIN_FILE', __FILE__);

// Full path to plugin directory (folder)
// Example: /var/www/html/wp-content/plugins/wp-property-listing/
define('WPPL_PLUGIN_DIR', plugin_dir_path(__FILE__));

// URL to plugin directory
// Example: https://yoursite.com/wp-content/plugins/wp-property-listing/
define('WPPL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Path to includes folder (where our class files are)
// Example: /var/www/html/wp-content/plugins/wp-property-listing/includes/
define('WPPL_INCLUDES_DIR', WPPL_PLUGIN_DIR . 'includes/');

// URL to assets folder (CSS, JS, images)
// Example: https://yoursite.com/wp-content/plugins/wp-property-listing/assets/
define('WPPL_ASSETS_URL', WPPL_PLUGIN_URL . 'assets/');

// ============================================
// MAIN PLUGIN CLASS - THE MANAGER
// ============================================

class WP_Property_Listing
{


    private static $instance = null;

    /**
     * 
     * @return WP_Property_Listing The single instance
     */
    public static function get_instance()
    {
        // Check if instance already exists
        if (null === self::$instance) {
            // Doesn't exist? Create it!
            self::$instance = new self();
        }
        // Return the instance (either existing or newly created)
        return self::$instance;
    }

    /**
     * Constructor - Private to prevent direct instantiation
     */
    private function __construct()
    {
        // Step 1: Load all class files
        $this->load_dependencies();

        // Step 2: Initialize all modules (start the workers)
        $this->init_hooks();
    }

    /**
     * Load Required Files
     */
    private function load_dependencies()
    {

        /**
         * Load each class file using require_once
         * 
         */

        // Worker 1: Custom Post Type handler
        require_once WPPL_INCLUDES_DIR . 'class-wppl-post-type.php';

        // Worker 2: Meta boxes (input fields) handler
        require_once WPPL_INCLUDES_DIR . 'class-wppl-meta-boxes.php';

        // Worker 3: Admin menu and pages handler
        require_once WPPL_INCLUDES_DIR . 'class-wppl-admin-menu.php';

        // Worker 4: AJAX requests handler
        require_once WPPL_INCLUDES_DIR . 'class-wppl-ajax-handler.php';

        // Worker 5: Shortcode handler
        require_once WPPL_INCLUDES_DIR . 'class-wppl-shortcode.php';

        // Worker 6: Assets (CSS/JS) loader
        require_once WPPL_INCLUDES_DIR . 'class-wppl-assets.php';
    }

    /**
     * Initialize Hooks and Modules
     * 
     */
    private function init_hooks()
    {

        /**
         * Initialize each module by getting its instance
         */

        // Start the custom post type module
        WPPL_Post_Type::get_instance();

        // Start the meta boxes module
        WPPL_Meta_Boxes::get_instance();

        // Start the admin menu module
        WPPL_Admin_Menu::get_instance();

        // Start the AJAX handler module
        WPPL_Ajax_Handler::get_instance();

        // Start the shortcode module
        WPPL_Shortcode::get_instance();

        // Start the assets loader module
        WPPL_Assets::get_instance();

        /**
         * Register activation and deactivation hooks
         * 
         * These run when:
         * - Activation: User clicks "Activate" in plugins page
         * - Deactivation: User clicks "Deactivate" in plugins page
         */
        register_activation_hook(WPPL_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(WPPL_PLUGIN_FILE, array($this, 'deactivate'));
    }

    /**
     * Plugin Activation
     */
    public function activate()
    {
        // Flush rewrite rules so WordPress knows about our custom post type
        flush_rewrite_rules();
    }

    /**
     * Plugin Deactivation
     */
    public function deactivate()
    {
        // Flush rewrite rules to clean up
        flush_rewrite_rules();
    }
}

// ============================================
// INITIALIZE THE PLUGIN
// ============================================
/**
 * This function kicks everything off
 * 
 * WHY A FUNCTION?
 */
function wp_property_listing_init()
{
    // Get the single instance and return it
    return WP_Property_Listing::get_instance();
}

// Hook our initialization function to WordPress
add_action('plugins_loaded', 'wp_property_listing_init');
