<?php
// Security: Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WPPL_Admin_Menu
{


    private static $instance = null;
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    private function __construct()
    {
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }


    public function add_admin_menu()
    {


        add_menu_page(
            __('Property Listings', 'wp-property-listing'),     // Page title
            __('Property Listings', 'wp-property-listing'),     // Menu title
            'manage_options',                                    // Capability
            'wppl-properties',                                   // Menu slug
            array($this, 'display_properties_page'),            // Callback
            'dashicons-building',                                // Icon
            6                                                    // Position
        );

        add_submenu_page(
            'wppl-properties',                                   // Parent slug
            __('All Properties', 'wp-property-listing'),         // Page title
            __('All Properties', 'wp-property-listing'),         // Menu title
            'manage_options',                                    // Capability
            'wppl-properties',                                   // Same slug as parent
            array($this, 'display_properties_page')             // Callback
        );

    
        add_submenu_page(
            'wppl-properties',                                   // Parent slug
            __('Add New Property', 'wp-property-listing'),       // Page title
            __('Add New Property', 'wp-property-listing'),       // Menu title
            'manage_options',                                    // Capability
            'post-new.php?post_type=wppl_property'              // WordPress editor URL
        );
    }


    public function display_properties_page()
    {
?>
        <div class="wrap">
            <!-- PAGE TITLE -->
            <h1 class="wp-heading-inline">
                <?php _e('All Properties', 'wp-property-listing'); ?>
            </h1>

            <!-- Add New Button (optional, since we have menu item) -->
            <a href="<?php echo admin_url('post-new.php?post_type=wppl_property'); ?>" class="page-title-action">
                <?php _e('Add New', 'wp-property-listing'); ?>
            </a>

            <hr class="wp-header-end">

            <!-- FILTERS SECTION -->
            <div class="wppl-filters-section" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">

                <!-- SEARCH BOX -->
                <div class="wppl-filter-row" style="margin-bottom: 15px;">
                    <label for="wppl-property-search" style="font-weight: 600; margin-right: 10px;">
                        <?php _e('Search:', 'wp-property-listing'); ?>
                    </label>
                    <input
                        type="text"
                        id="wppl-property-search"
                        placeholder="<?php _e('Search properties by name...', 'wp-property-listing'); ?>"
                        style="width: 300px; padding: 8px;" />
                    <span id="wppl-search-loading" style="display:none; margin-left: 10px; color: #2271b1;">
                        <?php _e('Searching...', 'wp-property-listing'); ?>
                    </span>
                </div>

                <!-- DATE RANGE FILTER -->
                <div class="wppl-filter-row" style="margin-bottom: 15px;">
                    <label style="font-weight: 600; margin-right: 10px;">
                        <?php _e('Date Range:', 'wp-property-listing'); ?>
                    </label>
                    <input
                        type="text"
                        id="wppl-start-date"
                        placeholder="<?php _e('Start Date', 'wp-property-listing'); ?>"
                        style="width: 150px; padding: 8px; margin-right: 10px;"
                        readonly />
                    <input
                        type="text"
                        id="wppl-end-date"
                        placeholder="<?php _e('End Date', 'wp-property-listing'); ?>"
                        style="width: 150px; padding: 8px; margin-right: 10px;"
                        readonly />
                    <button id="wppl-filter-by-date" class="button">
                        <?php _e('Filter', 'wp-property-listing'); ?>
                    </button>
                    <button id="wppl-reset-date-filter" class="button">
                        <?php _e('Reset', 'wp-property-listing'); ?>
                    </button>
                </div>

                <!-- PRICE RANGE SLIDER -->
                <div class="wppl-filter-row" style="margin-bottom: 15px;">
                    <label style="font-weight: 600; margin-right: 10px;">
                        <?php _e('Price Range:', 'wp-property-listing'); ?>
                    </label>
                    <div id="wppl-price-slider" style="width: 300px; display: inline-block; margin: 0 20px; vertical-align: middle;"></div>
                    <span id="wppl-price-range-display" style="font-weight: 600; color: #2271b1;">
                        $0 - $10,000,000
                    </span>
                </div>

                <!-- AGENT FILTER -->
                <div class="wppl-filter-row" style="margin-bottom: 15px;">
                    <label for="wppl-agent-filter" style="font-weight: 600; margin-right: 10px;">
                        <?php _e('Filter by Agent:', 'wp-property-listing'); ?>
                    </label>
                    <select id="wppl-agent-filter" style="padding: 8px; min-width: 200px;">
                        <option value=""><?php _e('All Agents', 'wp-property-listing'); ?></option>
                        <?php
                        // Get all unique agents from database
                        $agents = $this->get_all_agents();
                        foreach ($agents as $agent) {
                            echo '<option value="' . esc_attr($agent) . '">' . esc_html($agent) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- EXPORT BUTTON -->
                <div class="wppl-filter-row">
                    <button id="wppl-export-csv" class="button button-primary" style="padding: 8px 20px;">
                        <?php _e('Export to CSV', 'wp-property-listing'); ?>
                    </button>
                </div>

            </div>

            <!-- PROPERTIES TABLE CONTAINER -->
            <div id="wppl-properties-table-container">
                <?php $this->render_properties_table(); ?>
            </div>

        </div>
    <?php
    }


    public function render_properties_table($paged = 1, $search = '', $agent = '', $start_date = '', $end_date = '', $min_price = 0, $max_price = 10000000)
    {


        $args = array(
            'post_type'      => 'wppl_property',        // Only get properties
            'posts_per_page' => 10,                     // 10 per page
            'paged'          => $paged,                 // Current page
            'orderby'        => 'date',                 // Sort by date
            'order'          => 'DESC',                 // Newest first
            'post_status'    => 'publish',              // Only published
        );

     
        if (!empty($search)) {
            $args['s'] = sanitize_text_field($search);
        }

   
        if (!empty($agent)) {
            $args['meta_query'] = array(
                array(
                    'key'     => '_wppl_agent',         // Meta key
                    'value'   => sanitize_text_field($agent), // Agent name
                    'compare' => '=',                   // Exact match
                ),
            );
        }

        if (!empty($start_date) && !empty($end_date)) {
            $args['date_query'] = array(
                array(
                    'after'     => sanitize_text_field($start_date),
                    'before'    => sanitize_text_field($end_date),
                    'inclusive' => true,                // Include start/end dates
                ),
            );
        }
        $query = new WP_Query($args);
        $filtered_properties = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $price = get_post_meta(get_the_ID(), '_wppl_price', true);
                $price = floatval($price); // Convert to number

                if ($price >= $min_price && $price <= $max_price) {
                    $filtered_properties[] = get_the_ID();
                }
            }
            wp_reset_postdata();
        }

   
    ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Property Name', 'wp-property-listing'); ?></th>
                    <th><?php _e('Agent', 'wp-property-listing'); ?></th>
                    <th><?php _e('Location', 'wp-property-listing'); ?></th>
                    <th><?php _e('Price', 'wp-property-listing'); ?></th>
                    <th><?php _e('Bedrooms', 'wp-property-listing'); ?></th>
                    <th><?php _e('Bathrooms', 'wp-property-listing'); ?></th>
                    <th><?php _e('ZIP', 'wp-property-listing'); ?></th>
                    <th><?php _e('Actions', 'wp-property-listing'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($filtered_properties)) {
                    foreach ($filtered_properties as $property_id) {
                        // Get all meta data for this property
                        $title     = get_the_title($property_id);
                        $agent     = get_post_meta($property_id, '_wppl_agent', true);
                        $price     = get_post_meta($property_id, '_wppl_price', true);
                        $bedrooms  = get_post_meta($property_id, '_wppl_bedrooms', true);
                        $bathrooms = get_post_meta($property_id, '_wppl_bathrooms', true);
                        $zip       = get_post_meta($property_id, '_wppl_zip', true);
                        $city      = get_post_meta($property_id, '_wppl_city', true);
                        $state     = get_post_meta($property_id, '_wppl_state', true);

                        // Build location string
                        $location = $city;
                        if ($state) {
                            $location .= ', ' . $state;
                        }

                        // Get edit URL
                        $edit_url = get_edit_post_link($property_id);
                ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?php echo esc_url($edit_url); ?>">
                                        <?php echo esc_html($title); ?>
                                    </a>
                                </strong>
                            </td>
                            <td><?php echo esc_html($agent); ?></td>
                            <td><?php echo esc_html($location); ?></td>
                            <td>
                                <?php
                                if ($price) {
                                    echo '$' . number_format(floatval($price), 2);
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($bedrooms ?: '—'); ?></td>
                            <td><?php echo esc_html($bathrooms ?: '—'); ?></td>
                            <td><?php echo esc_html($zip); ?></td>
                            <td>
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">
                                    <?php _e('Edit', 'wp-property-listing'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php
                    }
                } else {
                  
                    ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px 20px;">
                            <?php _e('No properties found.', 'wp-property-listing'); ?>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>

        <?php

        if ($query->max_num_pages > 1) {
        ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '',
                        'current'   => max(1, $paged),
                        'total'     => $query->max_num_pages,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                    ));
                    ?>
                </div>
            </div>
<?php
        }
    }

    private function get_all_agents()
    {
        global $wpdb;
        $agents = $wpdb->get_col(
            "SELECT DISTINCT meta_value 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_wppl_agent' 
             AND meta_value != '' 
             ORDER BY meta_value ASC"
        );

        return $agents;
    }
}


