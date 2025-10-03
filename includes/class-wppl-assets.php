<?php

/**
 * Assets Handler
 * 
 * FILE PURPOSE:
 * This file handles ONLY loading CSS and JavaScript files
 * 
 * WHAT ARE ASSETS?
 * Assets are external files our plugin needs:
 * - CSS files (styling)
 * - JavaScript files (interactivity)
 * - Images, fonts, etc.
 * 
 * WHY ENQUEUE?
 * WordPress has a proper way to load assets called "enqueueing"
 * Benefits:
 * - Prevents conflicts with other plugins
 * - Handles dependencies (load jQuery before our script)
 * - Prevents duplicate loading
 * - Better performance
 * 
 * WHAT IT DOES:
 * - Loads admin CSS and JavaScript
 * - Loads frontend CSS
 * - Passes PHP data to JavaScript (localization)
 * - Loads jQuery UI for slider and date picker
 * 
 * WHAT IT DOESN'T DO:
 * - Create the actual CSS/JS files (we'll do that next)
 * - Handle AJAX requests (that's AJAX handler class)
 * - Display HTML (that's other classes)
 * 
 * @package WP_Property_Listing
 * @since 1.0.0
 */

// Security: Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPPL_Assets
 * 
 * SINGLE RESPONSIBILITY:
 * Handle all asset loading (CSS and JavaScript)
 */
class WPPL_Assets
{

    /**
     * Single instance (Singleton Pattern)
     * 
     * @var WPPL_Assets|null
     */
    private static $instance = null;

    /**
     * Get the single instance
     * 
     * @return WPPL_Assets
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Sets up hooks
     * 
     * TWO MAIN HOOKS:
     * 1. admin_enqueue_scripts - Load admin assets
     * 2. wp_enqueue_scripts - Load frontend assets
     */
    private function __construct()
    {

        /**
         * HOOK: Load Admin Assets
         * 
         * Fires when admin pages load
         * We check which page and load appropriate assets
         */
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        /**
         * HOOK: Load Frontend Assets
         * 
         * Fires when frontend pages load
         * Only loads CSS (no JavaScript needed on frontend for now)
         */
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }

    /**
     * Enqueue Admin Assets
     * 
     * This method loads CSS and JavaScript for admin pages
     * 
     * CONDITIONAL LOADING:
     * We only load on our plugin pages (performance optimization)
     * No need to load everywhere!
     * 
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_assets($hook)
    {

        /**
         * CHECK IF WE'RE ON OUR PAGES
         * 
         * $hook tells us which admin page we're on
         * Examples:
         * - 'toplevel_page_wppl-properties' = Our main menu page
         * - 'post.php' = Edit post page
         * - 'post-new.php' = Add new post page
         * 
         * We load assets on:
         * 1. Our property listing page
         * 2. Property edit/new screens
         */

        // Get current screen info
        $screen = get_current_screen();

        /**
         * DETERMINE IF WE SHOULD LOAD ASSETS
         * 
         * Load on:
         * - Our main properties page
         * - Property post type edit screens
         */
        $load_assets = false;

        // Check if on our main page
        if ($hook === 'toplevel_page_wppl-properties') {
            $load_assets = true;
        }

        // Check if editing/creating property
        if ($screen && $screen->post_type === 'wppl_property') {
            $load_assets = true;
        }

        // Exit if not on our pages
        if (!$load_assets) {
            return;
        }

        /**
         * ENQUEUE JQUERY UI COMPONENTS
         * 
         * WordPress includes jQuery UI, we just need to enqueue it
         * 
         * WHY JQUERY UI?
         * - Slider widget (for price range)
         * - Datepicker widget (for date filters)
         */

        // jQuery UI Slider
        wp_enqueue_script('jquery-ui-slider');

        // jQuery UI Datepicker
        wp_enqueue_script('jquery-ui-datepicker');

        /**
         * ENQUEUE JQUERY UI CSS
         * 
         * jQuery UI needs CSS for styling
         * We load from CDN (Content Delivery Network)
         * 
         * WHY CDN?
         * - Fast loading (servers worldwide)
         * - Might already be cached in user's browser
         * - Don't need to include file in plugin
         */
        wp_enqueue_style(
            'jquery-ui-css',                                    // Handle (unique ID)
            'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.min.css', // URL
            array(),                                            // Dependencies (none)
            '1.13.2',                                           // Version
            'all'                                               // Media (all devices)
        );

        /**
         * ENQUEUE OUR ADMIN JAVASCRIPT
         * 
         * This is our custom JavaScript file
         * Handles AJAX, filters, sliders, etc.
         */
        wp_enqueue_script(
            'wppl-admin-js',                                    // Handle (unique ID)
            WPPL_ASSETS_URL . 'js/wppl-admin.js',             // File URL
            array('jquery', 'jquery-ui-slider', 'jquery-ui-datepicker'), // Dependencies
            WPPL_VERSION,                                       // Version (for cache busting)
            true                                                // Load in footer (better performance)
        );

        /**
         * LOCALIZE SCRIPT
         * 
         * This passes PHP data to JavaScript
         * Makes PHP variables available in JS
         * 
         * WHY?
         * JavaScript needs:
         * - AJAX URL (where to send requests)
         * - Nonce (security token)
         * - Other PHP data
         */
        wp_localize_script(
            'wppl-admin-js',                                    // Script handle
            'wpplAdmin',                                        // JavaScript object name
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),      // AJAX endpoint URL
                'nonce'   => wp_create_nonce('wppl_ajax_nonce'), // Security nonce
            )
        );

        /**
         * ENQUEUE OUR ADMIN CSS
         * 
         * Custom styles for admin pages
         */
        wp_enqueue_style(
            'wppl-admin-css',                                   // Handle (unique ID)
            WPPL_ASSETS_URL . 'css/wppl-admin.css',           // File URL
            array(),                                            // Dependencies (none)
            WPPL_VERSION,                                       // Version
            'all'                                               // Media
        );
    }

    /**
     * Enqueue Frontend Assets
     * 
     * This method loads CSS for frontend pages
     * 
     * WHAT IT LOADS:
     * - Frontend CSS (for shortcode display)
     * 
     * WHAT IT DOESN'T LOAD:
     * - JavaScript (not needed on frontend for now)
     * - Admin styles (wrong place!)
     * 
     * @return void
     */
    public function enqueue_frontend_assets()
    {

        /**
         * ENQUEUE FRONTEND CSS
         * 
         * Styles for property grid displayed by shortcode
         */
        wp_enqueue_style(
            'wppl-frontend-css',                                // Handle (unique ID)
            WPPL_ASSETS_URL . 'css/wppl-frontend.css',        // File URL
            array(),                                            // Dependencies (none)
            WPPL_VERSION,                                       // Version
            'all'                                               // Media
        );

        /**
         * OPTIONAL: Enqueue Frontend JavaScript
         * 
         * Uncomment if you need JS on frontend later
         * For example: property filtering on frontend
         */
        /*
        wp_enqueue_script(
            'wppl-frontend-js',
            WPPL_ASSETS_URL . 'js/wppl-frontend.js',
            array('jquery'),
            WPPL_VERSION,
            true
        );
        */
    }
}

/**
 * ===========================================
 * UNDERSTANDING WORDPRESS ASSET LOADING
 * ===========================================
 * 
 * ENQUEUE FUNCTIONS:
 * 
 * 1. wp_enqueue_style() - Load CSS file
 *    Parameters:
 *    - Handle: Unique identifier
 *    - URL: Path to CSS file
 *    - Dependencies: Other styles needed first
 *    - Version: For cache busting
 *    - Media: all, screen, print, etc.
 * 
 * 2. wp_enqueue_script() - Load JavaScript file
 *    Parameters:
 *    - Handle: Unique identifier
 *    - URL: Path to JS file
 *    - Dependencies: Other scripts needed first
 *    - Version: For cache busting
 *    - In footer: true = load in footer (faster page load)
 * 
 * 3. wp_localize_script() - Pass PHP data to JavaScript
 *    Parameters:
 *    - Script handle: Which script to attach to
 *    - Object name: JavaScript object name
 *    - Data: Array of data to pass
 * 
 * ===========================================
 * WHY USE HANDLES?
 * ===========================================
 * 
 * Handles are unique IDs for each asset
 * 
 * Benefits:
 * - WordPress tracks what's loaded
 * - Can dequeue if needed
 * - Prevents duplicate loading
 * - Other plugins can depend on your assets
 * 
 * Example:
 * If another plugin needs jQuery:
 * wp_enqueue_script('my-script', 'url', array('jquery'))
 * WordPress ensures jQuery loads first
 * 
 * ===========================================
 * WHY VERSION NUMBERS?
 * ===========================================
 * 
 * Version numbers prevent caching issues
 * 
 * Problem without versioning:
 * - You update CSS file
 * - User's browser still uses old cached version
 * - User sees broken styling
 * 
 * Solution with versioning:
 * - Old: style.css?ver=1.0.0
 * - New: style.css?ver=1.0.1
 * - Browser sees different URL
 * - Downloads fresh file
 * 
 * ===========================================
 * LOCALIZATION EXPLAINED
 * ===========================================
 * 
 * wp_localize_script() makes PHP variables available in JavaScript
 * 
 * PHP Side:
 * wp_localize_script('my-script', 'myData', array(
 *     'ajaxUrl' => 'http://site.com/wp-admin/admin-ajax.php',
 *     'nonce' => 'abc123'
 * ));
 * 
 * JavaScript Side:
 * console.log(myData.ajaxUrl);  // http://site.com/wp-admin/admin-ajax.php
 * console.log(myData.nonce);    // abc123
 * 
 * Common Uses:
 * - Pass AJAX URL
 * - Pass security nonces
 * - Pass settings/options
 * - Pass translated strings
 * 
 * ===========================================
 * THAT'S IT FOR ASSETS CLASS!
 * ===========================================
 * 
 * This class handles:
 * ✅ Loading admin CSS and JavaScript
 * ✅ Loading frontend CSS
 * ✅ Conditional loading (only on our pages)
 * ✅ jQuery UI components
 * ✅ Script localization (passing data to JS)
 * ✅ Proper dependency management
 * ✅ Version control for cache busting
 * 
 * KEY CONCEPTS COVERED:
 * 1. wp_enqueue_style() - Load CSS properly
 * 2. wp_enqueue_script() - Load JavaScript properly
 * 3. wp_localize_script() - Pass PHP data to JS
 * 4. Conditional loading - Only load when needed
 * 5. Dependencies - Ensure libraries load in order
 * 6. Versioning - Cache busting
 * 7. CDN usage - Loading external libraries
 * 
 * WHAT'S LOADED WHERE:
 * 
 * Admin Pages (Property Listing, Edit Property):
 * - jQuery UI CSS (from CDN)
 * - jQuery UI Slider
 * - jQuery UI Datepicker
 * - wppl-admin.js (our JavaScript)
 * - wppl-admin.css (our CSS)
 * - Localized data (AJAX URL, nonce)
 * 
 * Frontend Pages (where shortcode is used):
 * - wppl-frontend.css (our CSS)
 * 
 * ===========================================
 * 🎉 CONGRATULATIONS! 🎉
 * ===========================================
 * 
 * You've completed ALL PHP classes!
 * 
 * ✅ Bootstrap file
 * ✅ Post Type class
 * ✅ Meta Boxes class
 * ✅ Admin Menu class
 * ✅ AJAX Handler class
 * ✅ Shortcode class
 * ✅ Assets class
 * 
 * NEXT STEPS:
 * Now we need to create the actual asset files:
 * 1. wppl-admin.js (JavaScript)
 * 2. wppl-admin.css (Admin CSS)
 * 3. wppl-frontend.css (Frontend CSS)
 * 
 * These are the files this class loads!
 */
