<?php

/**
 * AJAX Handler
 * 
 * FILE PURPOSE:
 * This file handles ONLY AJAX requests
 */

// Security: Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


class WPPL_Ajax_Handler
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

        add_action('wp_ajax_wppl_search_properties', array($this, 'ajax_search_properties'));
        add_action('wp_ajax_nopriv_wppl_search_properties', array($this, 'ajax_search_properties'));
        add_action('wp_ajax_wppl_export_csv', array($this, 'ajax_export_csv'));
    }

    public function ajax_search_properties()
    {


        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wppl_ajax_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'wp-property-listing')
            ));
            return;
        }


        $search     = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $agent      = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : '';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date   = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $min_price  = isset($_POST['min_price']) ? intval($_POST['min_price']) : 0;
        $max_price  = isset($_POST['max_price']) ? intval($_POST['max_price']) : 10000000;
        $paged      = isset($_POST['paged']) ? intval($_POST['paged']) : 1;

        if ($paged < 1) {
            $paged = 1;
        }
        if ($min_price < 0) {
            $min_price = 0;
        }
        if ($max_price < 0) {
            $max_price = 10000000;
        }
        if ($min_price > $max_price) {
            $temp = $min_price;
            $min_price = $max_price;
            $max_price = $temp;
        }

  
        $args = array(
            'post_type'      => 'wppl_property',
            'posts_per_page' => 10,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
        );
        if (!empty($search)) {
            $args['s'] = $search;
        }

        if (!empty($agent)) {
            $args['meta_query'] = array(
                array(
                    'key'     => '_wppl_agent',
                    'value'   => $agent,
                    'compare' => '=',
                ),
            );
        }
        if (!empty($start_date) && !empty($end_date)) {
            $args['date_query'] = array(
                array(
                    'after'     => $start_date,
                    'before'    => $end_date,
                    'inclusive' => true,
                ),
            );
        }

     
        $query = new WP_Query($args);


        $filtered_properties = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();

                $price = get_post_meta(get_the_ID(), '_wppl_price', true);
                $price = floatval($price);

                if ($price >= $min_price && $price <= $max_price) {
                    $filtered_properties[] = get_the_ID();
                }
            }
            wp_reset_postdata();
        }

        ob_start();
        $this->render_table_html($filtered_properties, $query, $paged);

        $html = ob_get_clean();
        wp_send_json_success(array(
            'html' => $html
        ));
    }


    private function render_table_html($filtered_properties, $query, $paged)
    {
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
                        $title     = get_the_title($property_id);
                        $agent     = get_post_meta($property_id, '_wppl_agent', true);
                        $price     = get_post_meta($property_id, '_wppl_price', true);
                        $bedrooms  = get_post_meta($property_id, '_wppl_bedrooms', true);
                        $bathrooms = get_post_meta($property_id, '_wppl_bathrooms', true);
                        $zip       = get_post_meta($property_id, '_wppl_zip', true);
                        $city      = get_post_meta($property_id, '_wppl_city', true);
                        $state     = get_post_meta($property_id, '_wppl_state', true);

                        $location = $city;
                        if ($state) {
                            $location .= ', ' . $state;
                        }

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
        // Render pagination
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


    public function ajax_export_csv()
    {

        /**
         * SECURITY CHECK: Verify Nonce
         */
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wppl_ajax_nonce')) {
            wp_die(__('Security check failed', 'wp-property-listing'));
        }

    
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to export data', 'wp-property-listing'));
        }


        $args = array(
            'post_type'      => 'wppl_property',
            'posts_per_page' => -1,  // -1 means get ALL
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
        );

        $query = new WP_Query($args);
        $filename = 'properties-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        $output = fopen('php://output', 'w');

        fputcsv($output, array(
            'Property Name',
            'Agent',
            'City',
            'State',
            'Price',
            'Bedrooms',
            'Bathrooms',
            'ZIP',
            'Address',
            'Country',
            'Date Added'
        ));


        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $property_id = get_the_ID();

                $row = array(
                    get_the_title($property_id),
                    get_post_meta($property_id, '_wppl_agent', true),
                    get_post_meta($property_id, '_wppl_city', true),
                    get_post_meta($property_id, '_wppl_state', true),
                    get_post_meta($property_id, '_wppl_price', true),
                    get_post_meta($property_id, '_wppl_bedrooms', true),
                    get_post_meta($property_id, '_wppl_bathrooms', true),
                    get_post_meta($property_id, '_wppl_zip', true),
                    get_post_meta($property_id, '_wppl_address', true),
                    get_post_meta($property_id, '_wppl_country', true),
                    get_the_date('Y-m-d', $property_id)
                );

                fputcsv($output, $row);
            }
            wp_reset_postdata();
        }

        fclose($output);
        die();
    }
}
