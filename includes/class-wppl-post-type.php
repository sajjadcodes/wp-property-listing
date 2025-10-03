<?php

/**
 * Custom Post Type Handler
 * 
 * @package WP_Property_Listing
 * @since 1.0.0
 */

// Security: Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPPL_Post_Type
 */
class WPPL_Post_Type
{

    /**
     * Single instance of this class (Singleton Pattern)
     * 
     * @var WPPL_Post_Type|null
     */
    private static $instance = null;

    /**
     * Get the single instance
     * 
     * USAGE: WPPL_Post_Type::get_instance();
     * 
     * @return WPPL_Post_Type
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Private to enforce singleton
     */
    private function __construct()
    {
        // Hook: When WordPress initializes, register our post type
        add_action('init', array($this, 'register_post_type'));
    }

    /**
     * Register the Properties Custom Post Type
     * 
     * @return void
     */
    public function register_post_type()
    {

        /**
         * STEP 1: Define Labels
    
         */
        $labels = array(
            'name'                  => _x('Properties', 'Post type general name', 'wp-property-listing'),
            'singular_name'         => _x('Property', 'Post type singular name', 'wp-property-listing'),
            'menu_name'             => _x('Properties', 'Admin Menu text', 'wp-property-listing'),
            'add_new'               => _x('Add New', 'Property', 'wp-property-listing'),
            'add_new_item'          => __('Add New Property', 'wp-property-listing'),
            'edit_item'             => __('Edit Property', 'wp-property-listing'),
            'new_item'              => __('New Property', 'wp-property-listing'),
            'view_item'             => __('View Property', 'wp-property-listing'),
            'view_items'            => __('View Properties', 'wp-property-listing'),
            'search_items'          => __('Search Properties', 'wp-property-listing'),
            'not_found'             => __('No properties found', 'wp-property-listing'),
            'not_found_in_trash'    => __('No properties found in Trash', 'wp-property-listing'),
            'featured_image'        => __('Property Image', 'wp-property-listing'),
            'set_featured_image'    => __('Set property image', 'wp-property-listing'),
            'remove_featured_image' => __('Remove property image', 'wp-property-listing'),
            'use_featured_image'    => __('Use as property image', 'wp-property-listing'),
            'archives'              => __('Property Archives', 'wp-property-listing'),
            'insert_into_item'      => __('Insert into property', 'wp-property-listing'),
            'uploaded_to_this_item' => __('Uploaded to this property', 'wp-property-listing'),
            'filter_items_list'     => __('Filter properties list', 'wp-property-listing'),
            'items_list_navigation' => __('Properties list navigation', 'wp-property-listing'),
            'items_list'            => __('Properties list', 'wp-property-listing'),
            'all_items'             => __('All Properties', 'wp-property-listing'),
        );

        /**
         * STEP 2: Define Arguments
         */
        $args = array(
      
            'labels'                => $labels,
            'description'           => __('Properties for listing and management', 'wp-property-listing'),
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => false,
            'show_in_nav_menus'     => true,
            'show_in_admin_bar'     => true,
            'show_in_rest'          => true,
            'rest_base'             => 'properties',
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-building',
            'capability_type'       => 'post',
            'map_meta_cap'          => true,
            'hierarchical'          => false,
            'supports'              => array(
                'title',          
                'editor',        
                'thumbnail',      
                'excerpt',         
                'custom-fields',   
            ),
            'has_archive'           => true,
            'rewrite'               => array(
                'slug'       => 'properties',
                'with_front' => false,
            ),
            'can_export'            => true,
            'delete_with_user'      => false,
        );
        register_post_type('wppl_property', $args);
    }
}

