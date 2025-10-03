/**
 * Property Listings Manager - Admin JavaScript
 * Handles AJAX search, filtering, price slider, date picker, and CSV export
 */

jQuery(document).ready(function ($) {

    /**
     * Initialize Price Range Slider
     */
    if ($('#price-slider').length) {
        $('#price-slider').slider({
            range: true,
            min: 0,
            max: 10000000,
            step: 50000,
            values: [0, 10000000],
            slide: function (event, ui) {
                $('#price-range-display').text('$' + formatNumber(ui.values[0]) + ' - $' + formatNumber(ui.values[1]));
            },
            stop: function (event, ui) {
                // Trigger search when slider is released
                performSearch();
            }
        });
    }

    /**
     * Initialize Date Pickers
     */
    if ($('#start-date').length && $('#end-date').length) {
        $('#start-date').datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: 0, // Today
            onSelect: function (selectedDate) {
                // Set minimum date for end date
                $('#end-date').datepicker('option', 'minDate', selectedDate);
            }
        });

        $('#end-date').datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: 0, // Today
            onSelect: function (selectedDate) {
                // Set maximum date for start date
                $('#start-date').datepicker('option', 'maxDate', selectedDate);
            }
        });
    }

    /**
     * AJAX Property Search
     * Triggers on search input with debouncing
     */
    var searchTimeout;
    $('#property-search').on('keyup', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function () {
            performSearch();
        }, 500); // 500ms debounce
    });

    /**
     * Agent Filter Change
     */
    $('#agent-filter').on('change', function () {
        performSearch();
    });

    /**
     * Date Filter Button
     */
    $('#filter-by-date').on('click', function () {
        var startDate = $('#start-date').val();
        var endDate = $('#end-date').val();

        if (startDate && !endDate) {
            alert('Please select an end date');
            return;
        }

        if (!startDate && endDate) {
            alert('Please select a start date');
            return;
        }

        performSearch();
    });

    /**
     * Reset Date Filter
     */
    $('#reset-date-filter').on('click', function () {
        $('#start-date').val('');
        $('#end-date').val('');
        performSearch();
    });

    /**
     * Perform AJAX Search
     * Collects all filter values and sends AJAX request
     */
    function performSearch() {
        var searchTerm = $('#property-search').val();
        var agentFilter = $('#agent-filter').val();
        var startDate = $('#start-date').val();
        var endDate = $('#end-date').val();
        var priceRange = $('#price-slider').slider('values');
        var minPrice = priceRange ? priceRange[0] : 0;
        var maxPrice = priceRange ? priceRange[1] : 10000000;

        $('#search-loading').show();

        $.ajax({
            url: propertyAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'search_properties',
                nonce: propertyAdmin.nonce,
                search: searchTerm,
                agent: agentFilter,
                start_date: startDate,
                end_date: endDate,
                min_price: minPrice,
                max_price: maxPrice
            },
            success: function (response) {
                $('#search-loading').hide();
                if (response.success) {
                    $('#properties-table-container').html(response.data.html);
                } else {
                    console.error('Search failed:', response);
                }
            },
            error: function (xhr, status, error) {
                $('#search-loading').hide();
                console.error('AJAX error:', error);
                alert('An error occurred while searching. Please try again.');
            }
        });
    }

    /**
     * Export to CSV
     */
    $('#export-csv').on('click', function () {
        var button = $(this);
        button.prop('disabled', true).text('Exporting...');

        // Create a temporary form to submit
        var form = $('<form>', {
            'method': 'POST',
            'action': propertyAdmin.ajax_url
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'action',
            'value': 'export_properties_csv'
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'nonce',
            'value': propertyAdmin.nonce
        }));

        // Append form to body and submit
        $('body').append(form);
        form.submit();

        // Remove form after submission
        setTimeout(function () {
            form.remove();
            button.prop('disabled', false).text('Export to CSV');
        }, 1000);
    });

    /**
     * Format Number with Commas
     * @param {number} num - Number to format
     * @return {string} Formatted number
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Fetch Location Details from ZIP Code API
     * Used in the property meta box
     */
    $(document).on('click', '#fetch_location', function (e) {
        e.preventDefault();

        var zipCode = $('#property_zip').val();
        var button = $(this);

        if (!zipCode) {
            alert('Please enter a ZIP code');
            return;
        }

        // Validate US ZIP code format (5 digits or 5+4 format)
        var zipRegex = /^\d{5}(-\d{4})?$/;
        if (!zipRegex.test(zipCode)) {
            alert('Please enter a valid US ZIP code (e.g., 90211 or 90211-1234)');
            return;
        }

        // Extract 5-digit ZIP if in extended format
        var zipCodeClean = zipCode.split('-')[0];

        button.prop('disabled', true).text('Fetching...');
        $('#zip_loading').show();

        $.ajax({
            url: 'https://api.zippopotam.us/us/' + zipCodeClean,
            type: 'GET',
            dataType: 'json',
            timeout: 10000, // 10 second timeout
            success: function (data) {
                $('#zip_loading').hide();
                button.prop('disabled', false).text('Fetch Location Details');

                if (data.places && data.places.length > 0) {
                    var place = data.places[0];

                    // Populate fields with fetched data
                    $('#property_city').val(place['place name']);
                    $('#property_state').val(place['state abbreviation'] + ' (' + place['state'] + ')');
                    $('#property_country').val(data['country']);

                    // Visual feedback
                    $('#property_city, #property_state, #property_country').css('background-color', '#d4edda');
                    setTimeout(function () {
                        $('#property_city, #property_state, #property_country').css('background-color', '');
                    }, 2000);

                    // Show success message
                    showNotice('Location details fetched successfully!', 'success');
                } else {
                    alert('No location data found for this ZIP code. Please check and try again.');
                }
            },
            error: function (xhr, status, error) {
                $('#zip_loading').hide();
                button.prop('disabled', false).text('Fetch Location Details');

                if (status === 'timeout') {
                    alert('Request timeout. Please check your internet connection and try again.');
                } else if (xhr.status === 404) {
                    alert('Invalid ZIP code. Please enter a valid US ZIP code.');
                } else {
                    alert('Error fetching location data. Please verify the ZIP code is correct and try again.');
                }

                console.error('ZIP API Error:', error);
            }
        });
    });

    /**
     * Show Admin Notice
     * @param {string} message - Message to display
     * @param {string} type - Notice type (success, error, warning, info)
     */
    function showNotice(message, type) {
        type = type || 'info';
        var noticeClass = 'notice notice-' + type + ' is-dismissible';
        var notice = $('<div>', {
            'class': noticeClass,
            'html': '<p>' + message + '</p>'
        });

        // Remove existing notices
        $('.property-meta-fields .notice').remove();

        // Add new notice
        $('.property-meta-fields').prepend(notice);

        // Auto-dismiss after 3 seconds
        setTimeout(function () {
            notice.fadeOut(function () {
                $(this).remove();
            });
        }, 3000);
    }

    /**
     * Auto-fetch location on ZIP code paste
     */
    $('#property_zip').on('paste', function () {
        var input = $(this);
        setTimeout(function () {
            var zipCode = input.val();
            if (zipCode && /^\d{5}(-\d{4})?$/.test(zipCode)) {
                $('#fetch_location').trigger('click');
            }
        }, 100);
    });

    /**
     * Confirm before leaving page with unsaved changes
     */
    var formChanged = false;
    $('.property-meta-fields input').on('change', function () {
        formChanged = true;
    });

    $('form#post').on('submit', function () {
        formChanged = false;
    });

    $(window).on('beforeunload', function () {
        if (formChanged) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });

    /**
     * Initialize tooltips (if needed in future)
     */
    if ($.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }

    /**
     * Handle pagination clicks via AJAX
     */
    $(document).on('click', '.tablenav-pages a', function (e) {
        e.preventDefault();
        var url = $(this).attr('href');
        var page = getUrlParameter('paged', url) || 1;

        // Update current search with new page
        var searchTerm = $('#property-search').val();
        var agentFilter = $('#agent-filter').val();
        var startDate = $('#start-date').val();
        var endDate = $('#end-date').val();
        var priceRange = $('#price-slider').slider('values');

        $('#search-loading').show();

        $.ajax({
            url: propertyAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'search_properties',
                nonce: propertyAdmin.nonce,
                search: searchTerm,
                agent: agentFilter,
                start_date: startDate,
                end_date: endDate,
                min_price: priceRange[0],
                max_price: priceRange[1],
                paged: page
            },
            success: function (response) {
                $('#search-loading').hide();
                if (response.success) {
                    $('#properties-table-container').html(response.data.html);
                    // Scroll to top of table
                    $('html, body').animate({
                        scrollTop: $('#properties-table-container').offset().top - 100
                    }, 300);
                }
            },
            error: function () {
                $('#search-loading').hide();
                alert('Error loading page. Please try again.');
            }
        });
    });

    /**
     * Get URL Parameter Value
     * @param {string} name - Parameter name
     * @param {string} url - URL string
     * @return {string|null} Parameter value
     */
    function getUrlParameter(name, url) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(url);
        return results === null ? null : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }

    /**
     * Real-time validation for numeric fields
     */
    $('#property_price, #property_bedrooms, #property_bathrooms').on('keypress', function (e) {
        // Allow: backspace, delete, tab, escape, enter, and decimal point
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
            // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true) ||
            // Allow: home, end, left, right
            (e.keyCode >= 35 && e.keyCode <= 39)) {
            return;
        }
        // Ensure it's a number
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });

    console.log('Property Listings Manager Admin JS Loaded');
});