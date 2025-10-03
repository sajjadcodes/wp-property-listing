<?php



// Security: Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


class WPPL_Meta_Boxes
{

    /**
     * Single instance (Singleton Pattern)
     * 
     * @var WPPL_Meta_Boxes|null
     */
    private static $instance = null;

    /**
     * Get the single instance
     * 
     * @return WPPL_Meta_Boxes
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
       
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
    }

  
    public function add_meta_boxes()
    {
    
        add_meta_box(
            'wppl_property_details',                    // Unique ID
            __('Property Details', 'wp-property-listing'), // Box title
            array($this, 'render_meta_box'),            // Callback function
            'wppl_property',                            // Post type
            'normal',                                   // Context (normal = main column)
            'high'                                      // Priority (high = near top)
        );
    }

    public function render_meta_box($post)
    {

 
        wp_nonce_field('wppl_save_meta_box', 'wppl_meta_box_nonce');

        $agent      = get_post_meta($post->ID, '_wppl_agent', true);
        $price      = get_post_meta($post->ID, '_wppl_price', true);
        $bedrooms   = get_post_meta($post->ID, '_wppl_bedrooms', true);
        $bathrooms  = get_post_meta($post->ID, '_wppl_bathrooms', true);
        $zip        = get_post_meta($post->ID, '_wppl_zip', true);
        $address    = get_post_meta($post->ID, '_wppl_address', true);
        $city       = get_post_meta($post->ID, '_wppl_city', true);
        $state      = get_post_meta($post->ID, '_wppl_state', true);
        $country    = get_post_meta($post->ID, '_wppl_country', true);

?>
        <div class="wppl-meta-fields">

            <!-- AGENT NAME FIELD -->
            <p class="wppl-field">
                <label for="wppl_agent">
                    <strong><?php _e('Agent Name:', 'wp-property-listing'); ?></strong>
                </label>
                <input
                    type="text"
                    id="wppl_agent"
                    name="wppl_agent"
                    value="<?php echo esc_attr($agent); ?>"
                    class="widefat"
                    placeholder="<?php _e('Enter agent name', 'wp-property-listing'); ?>" />
                <span class="description">
                    <?php _e('Name of the real estate agent handling this property', 'wp-property-listing'); ?>
                </span>
            </p>

            <!-- PRICE FIELD -->
            <p class="wppl-field">
                <label for="wppl_price">
                    <strong><?php _e('Price ($):', 'wp-property-listing'); ?></strong>
                </label>
                <input
                    type="number"
                    id="wppl_price"
                    name="wppl_price"
                    value="<?php echo esc_attr($price); ?>"
                    class="widefat"
                    step="0.01"
                    min="0"
                    placeholder="<?php _e('Enter price in USD', 'wp-property-listing'); ?>" />
                <span class="description">
                    <?php _e('Property price in US Dollars', 'wp-property-listing'); ?>
                </span>
            </p>

            <!-- BEDROOMS FIELD -->
            <p class="wppl-field">
                <label for="wppl_bedrooms">
                    <strong><?php _e('Bedrooms:', 'wp-property-listing'); ?></strong>
                </label>
                <input
                    type="number"
                    id="wppl_bedrooms"
                    name="wppl_bedrooms"
                    value="<?php echo esc_attr($bedrooms); ?>"
                    class="widefat"
                    min="0"
                    placeholder="<?php _e('Number of bedrooms', 'wp-property-listing'); ?>" />
                <span class="description">
                    <?php _e('Total number of bedrooms', 'wp-property-listing'); ?>
                </span>
            </p>

            <!-- BATHROOMS FIELD -->
            <p class="wppl-field">
                <label for="wppl_bathrooms">
                    <strong><?php _e('Bathrooms:', 'wp-property-listing'); ?></strong>
                </label>
                <input
                    type="number"
                    id="wppl_bathrooms"
                    name="wppl_bathrooms"
                    value="<?php echo esc_attr($bathrooms); ?>"
                    class="widefat"
                    step="0.5"
                    min="0"
                    placeholder="<?php _e('Number of bathrooms', 'wp-property-listing'); ?>" />
                <span class="description">
                    <?php _e('Total number of bathrooms (use 0.5 for half bath)', 'wp-property-listing'); ?>
                </span>
            </p>

            <!-- ZIP CODE FIELD WITH API BUTTON -->
            <p class="wppl-field">
                <label for="wppl_zip">
                    <strong><?php _e('ZIP Code:', 'wp-property-listing'); ?></strong>
                </label>
                <input
                    type="text"
                    id="wppl_zip"
                    name="wppl_zip"
                    value="<?php echo esc_attr($zip); ?>"
                    class="widefat"
                    maxlength="10"
                    placeholder="<?php _e('Enter 5-digit US ZIP code', 'wp-property-listing'); ?>" />
                <!-- Fetch Location Button -->
                <button
                    type="button"
                    id="wppl_fetch_location"
                    class="button button-secondary"
                    style="margin-top: 8px;">
                    <?php _e('Fetch Location Details', 'wp-property-listing'); ?>
                </button>
                <!-- Loading Indicator -->
                <span id="wppl_zip_loading" style="display:none; margin-left: 10px;">
                    <span class="spinner is-active" style="float: none;"></span>
                    <?php _e('Fetching...', 'wp-property-listing'); ?>
                </span>
                <span class="description">
                    <?php _e('Enter US ZIP code and click "Fetch Location Details" to auto-populate city, state, and country', 'wp-property-listing'); ?>
                </span>
            </p>

            <!-- ADDRESS FIELD -->
            <p class="wppl-field">
                <label for="wppl_address">
                    <strong><?php _e('Street Address:', 'wp-property-listing'); ?></strong>
                </label>
                <input
                    type="text"
                    id="wppl_address"
                    name="wppl_address"
                    value="<?php echo esc_attr($address); ?>"
                    class="widefat"
                    placeholder="<?php _e('Enter street address', 'wp-property-listing'); ?>" />
                <span class="description">
                    <?php _e('Street address of the property', 'wp-property-listing'); ?>
                </span>
            </p>

            <!-- CITY FIELD (Auto-populated from ZIP) -->
            <p class="wppl-field">
                <label for="wppl_city">
                    <strong><?php _e('City:', 'wp-property-listing'); ?></strong>
                </label>
                <input
                    type="text"
                    id="wppl_city"
                    name="wppl_city"
                    value="<?php echo esc_attr($city); ?>"
                    class="widefat"
                    readonly
                    placeholder="<?php _e('Auto-populated from ZIP code', 'wp-property-listing'); ?>" />
                <span class="description">
                    <?php _e('City name (auto-populated from ZIP code)', 'wp-property-listing'); ?>
                </span>
            </p>

            <!-- STATE FIELD (Auto-populated from ZIP) -->
            <p class="wppl-field">
                <label for="wppl_state">
                    <strong><?php _e('State:', 'wp-property-listing'); ?></strong>
                </label>
                <input
                    type="text"
                    id="wppl_state"
                    name="wppl_state"
                    value="<?php echo esc_attr($state); ?>"
                    class="widefat"
                    readonly
                    placeholder="<?php _e('Auto-populated from ZIP code', 'wp-property-listing'); ?>" />
                <span class="description">
                    <?php _e('State name (auto-populated from ZIP code)', 'wp-property-listing'); ?>
                </span>
            </p>

            <!-- COUNTRY FIELD (Auto-populated from ZIP) -->
            <p class="wppl-field">
                <label for="wppl_country">
                    <strong><?php _e('Country:', 'wp-property-listing'); ?></strong>
                </label>
                <input
                    type="text"
                    id="wppl_country"
                    name="wppl_country"
                    value="<?php echo esc_attr($country); ?>"
                    class="widefat"
                    readonly
                    placeholder="<?php _e('Auto-populated from ZIP code', 'wp-property-listing'); ?>" />
                <span class="description">
                    <?php _e('Country name (auto-populated from ZIP code)', 'wp-property-listing'); ?>
                </span>
            </p>

        </div>
        <script type="text/javascript">
            jQuery(document).ready(function($) {

                /**
                 * Fetch Location Button Click Handler
                 */
                $('#wppl_fetch_location').on('click', function(e) {
                    e.preventDefault(); // Prevent form submission

                    // Get the ZIP code entered by user
                    var zipCode = $('#wppl_zip').val().trim();

                    // Validate: Check if ZIP code entered
                    if (!zipCode) {
                        alert('<?php _e('Please enter a ZIP code first', 'wp-property-listing'); ?>');
                        return;
                    }

                    // Validate: Check if it's a valid US ZIP format
                    var zipRegex = /^\d{5}(-\d{4})?$/;
                    if (!zipRegex.test(zipCode)) {
                        alert('<?php _e('Please enter a valid US ZIP code (5 digits)', 'wp-property-listing'); ?>');
                        return;
                    }

                    // Get clean 5-digit ZIP (remove extended part if exists)
                    var zipClean = zipCode.split('-')[0];

                    // UI: Disable button and show loading
                    var $button = $(this);
                    $button.prop('disabled', true).text('<?php _e('Fetching...', 'wp-property-listing'); ?>');
                    $('#wppl_zip_loading').show();

                    /**
                     * AJAX Call to Zippopotam API
                     * 
                     * API: https://api.zippopotam.us/us/[ZIP]
                     * Returns: City, State, Country for the ZIP code
                     * 
                     * WHY HTTPS?
                     * To avoid mixed-content errors on SSL sites
                     */
                    $.ajax({
                        url: 'https://api.zippopotam.us/us/' + zipClean,
                        type: 'GET',
                        dataType: 'json',
                        timeout: 10000, // 10 second timeout

                        /**
                         * SUCCESS: API returned data
                         */
                        success: function(data) {
                            // Hide loading indicator
                            $('#wppl_zip_loading').hide();
                            $button.prop('disabled', false).text('<?php _e('Fetch Location Details', 'wp-property-listing'); ?>');

                            // Check if we got valid data
                            if (data.places && data.places.length > 0) {
                                var place = data.places[0];

                        

                                // City name
                                $('#wppl_city').val(place['place name']);

                                // State (full name + abbreviation)
                                $('#wppl_state').val(place['state abbreviation'] + ' (' + place['state'] + ')');

                                // Country
                                $('#wppl_country').val(data['country']);

                                // Visual feedback - highlight updated fields
                                $('#wppl_city, #wppl_state, #wppl_country')
                                    .css('background-color', '#d4edda')
                                    .delay(2000)
                                    .queue(function(next) {
                                        $(this).css('background-color', '');
                                        next();
                                    });

                                // Show success message
                                alert('<?php _e('Location details fetched successfully!', 'wp-property-listing'); ?>');

                            } else {
                                // No data found for this ZIP
                                alert('<?php _e('No location data found for this ZIP code. Please verify it is correct.', 'wp-property-listing'); ?>');
                            }
                        },

                        /**
                         * ERROR: API call failed
                         */
                        error: function(xhr, status, error) {
                            // Hide loading indicator
                            $('#wppl_zip_loading').hide();
                            $button.prop('disabled', false).text('<?php _e('Fetch Location Details', 'wp-property-listing'); ?>');

                            // Show appropriate error message
                            if (status === 'timeout') {
                                alert('<?php _e('Request timeout. Please check your internet connection and try again.', 'wp-property-listing'); ?>');
                            } else if (xhr.status === 404) {
                                alert('<?php _e('Invalid ZIP code. Please enter a valid US ZIP code.', 'wp-property-listing'); ?>');
                            } else {
                                alert('<?php _e('Error fetching location data. Please try again or enter manually.', 'wp-property-listing'); ?>');
                            }

                            console.error('ZIP API Error:', error);
                        }
                    });
                });
            });
        </script>
<?php
    }

    public function save_meta_boxes($post_id)
    {

      
        if (
            !isset($_POST['wppl_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['wppl_meta_box_nonce'], 'wppl_save_meta_box')
        ) {
            return; // Invalid nonce, don't save
        }

    
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return; // It's an autosave, don't save
        }

    
        if (!current_user_can('edit_post', $post_id)) {
            return; // User can't edit, don't save
        }

      
        if (get_post_type($post_id) !== 'wppl_property') {
            return; // Not our post type, don't save
        }

        $fields = array(
            'wppl_agent'     => '_wppl_agent',
            'wppl_price'     => '_wppl_price',
            'wppl_bedrooms'  => '_wppl_bedrooms',
            'wppl_bathrooms' => '_wppl_bathrooms',
            'wppl_zip'       => '_wppl_zip',
            'wppl_address'   => '_wppl_address',
            'wppl_city'      => '_wppl_city',
            'wppl_state'     => '_wppl_state',
            'wppl_country'   => '_wppl_country',
        );

        foreach ($fields as $field_name => $meta_key) {

            // Check if field exists in POST data
            if (isset($_POST[$field_name])) {
                $value = sanitize_text_field($_POST[$field_name]);
                update_post_meta($post_id, $meta_key, $value);
            } else {
    
                delete_post_meta($post_id, $meta_key);
            }
        }
    }
}

