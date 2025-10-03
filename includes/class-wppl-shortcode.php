<?php

/**
 * Shortcode Handler
 * 
 * FILE PURPOSE:
 * This file handles ONLY the shortcode functionality
 */

// Security: Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


class WPPL_Shortcode
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
        // Register the shortcode
        add_shortcode('property_listings', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts)
    {

        $atts = shortcode_atts(
            array(
                'agent'    => '',  
                'bedrooms' => '',  
            ),
            $atts,
            'property_listings' 
        );


        $agent    = sanitize_text_field($atts['agent']);
        $bedrooms = sanitize_text_field($atts['bedrooms']);
        $args = array(
            'post_type'      => 'wppl_property',
            'posts_per_page' => -1,  // -1 = get all (no pagination on frontend)
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

  
        $meta_query = array('relation' => 'AND');

    
        if (!empty($agent)) {
            $meta_query[] = array(
                'key'     => '_wppl_agent',     
                'value'   => $agent,             
                'compare' => '=',                
            );
        }

  
        if (!empty($bedrooms)) {
            $meta_query[] = array(
                'key'     => '_wppl_bedrooms',   
                'value'   => $bedrooms,          
                'compare' => '=',                
            );
        }

        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }

        $query = new WP_Query($args);
        ob_start();

        if ($query->have_posts()) {
?>
   
            <div class="wppl-property-grid">
                <?php
             
                while ($query->have_posts()) {
                    $query->the_post();

                    // Get property ID
                    $property_id = get_the_ID();

                    $agent     = get_post_meta($property_id, '_wppl_agent', true);
                    $price     = get_post_meta($property_id, '_wppl_price', true);
                    $bedrooms  = get_post_meta($property_id, '_wppl_bedrooms', true);
                    $bathrooms = get_post_meta($property_id, '_wppl_bathrooms', true);
                    $city      = get_post_meta($property_id, '_wppl_city', true);
                    $state     = get_post_meta($property_id, '_wppl_state', true);
                    $location = array_filter(array($city, $state)); 
                    $location = implode(', ', $location);           

                ?>
                    <div class="wppl-property-item">

                        <!-- PROPERTY IMAGE -->
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="wppl-property-image">
                                <?php
                              
                                the_post_thumbnail('medium');
                                ?>
                            </div>
                        <?php endif; ?>

                        <!-- PROPERTY CONTENT -->
                        <div class="wppl-property-content">

                            <!-- TITLE -->
                            <h3 class="wppl-property-title">
                                <?php the_title(); ?>
                            </h3>

                            <!-- AGENT -->
                            <?php if ($agent) : ?>
                                <p class="wppl-property-agent">
                                    <strong><?php _e('Agent:', 'wp-property-listing'); ?></strong>
                                    <?php echo esc_html($agent); ?>
                                </p>
                            <?php endif; ?>

                            <!-- LOCATION -->
                            <?php if ($location) : ?>
                                <p class="wppl-property-location">
                                    <strong><?php _e('Location:', 'wp-property-listing'); ?></strong>
                                    <?php echo esc_html($location); ?>
                                </p>
                            <?php endif; ?>

                            <!-- PRICE -->
                            <?php if ($price) : ?>
                                <p class="wppl-property-price">
                                    <strong><?php _e('Price:', 'wp-property-listing'); ?></strong>
                                    $<?php echo number_format(floatval($price), 2); ?>
                                </p>
                            <?php endif; ?>

                            <!-- BEDROOMS & BATHROOMS -->
                            <?php if ($bedrooms || $bathrooms) : ?>
                                <p class="wppl-property-details">
                                    <?php if ($bedrooms) : ?>
                                        <span class="wppl-bedrooms">
                                            <strong><?php _e('Bedrooms:', 'wp-property-listing'); ?></strong>
                                            <?php echo esc_html($bedrooms); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($bathrooms) : ?>
                                        <span class="wppl-bathrooms">
                                            <strong><?php _e('Bathrooms:', 'wp-property-listing'); ?></strong>
                                            <?php echo esc_html($bathrooms); ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>

                            <!-- EXCERPT (Optional) -->
                            <?php if (has_excerpt()) : ?>
                                <div class="wppl-property-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php endif; ?>

                        </div>

                    </div>
                <?php
                }
                ?>
            </div>
        <?php

         
            wp_reset_postdata();
        } else {
          
        ?>
            <div class="wppl-no-properties">
                <p><?php _e('No properties found matching your criteria.', 'wp-property-listing'); ?></p>
            </div>
<?php
        }

     
        $output = ob_get_clean();
        return $output;
    }
}


