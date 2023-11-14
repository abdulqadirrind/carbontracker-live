<?php 
/*
 * Plugin Name:       CarbonTracker Live
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       Monitor and showcase your fleet's environmental impact in real-time, from miles driven to CO2 reduction, with flexible update settings.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Abdul Qadir 
 * Author URI:        https://www.linkedin.com/in/abdulqadir101/
 * License:           GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include the admin page file
require_once(plugin_dir_path(__FILE__) . 'admin/admin.php');

function carbontracker_live_shortcode_action($atts) {

    /**
     * Shortcode Action for Carbon Tracker Live.
     *
     * This function is responsible for handling the Carbon Tracker Live shortcode.
     * It retrieves plugin options, including the Carbon API URL, heading, total carbon,
     * and textarea content. It then fetches data from the API, replaces placeholders
     * in the heading, total carbon, and textarea with actual API data, and displays
     * the formatted content along with a live-updating total carbon.
     *
     * @author   Abdul Qadir 
     * 
     * @param array $atts Shortcode attributes (unused).
     * @return string Formatted HTML content with live-updating total carbon.
     *
     * @since 1.0.0
     */
    $plugin_url = plugin_dir_url(__FILE__);
    $carbon_heading = get_option( 'carbon_heading' );
    $total_carbon = get_option( 'total_carbon' );
    $carbon_textarea = get_option( 'carbon_textarea' );
    $carbon_timer = get_option( 'carbon_timer' );
    $carbon_timer = $carbon_timer * 1000;
    $carbon_api_url = get_option( 'carbon_api_url' );
    

    $response = wp_remote_get($carbon_api_url);

    if (is_array($response) && !is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);

        $data = json_decode($body);

                // Placeholder replacement
                $carbon_heading = str_replace(['{date}', '{totalMilesDriven}', '{milesDrivenDoD}', '{totalCO2Avoided}', '{co2AvoidedDoD}'], 
                [$data->date, $data->totalMilesDriven, $data->milesDrivenDoD, $data->totalCO2Avoided, $data->co2AvoidedDoD], 
                $carbon_heading);

$total_carbon = str_replace(['{date}', '{totalMilesDriven}', '{milesDrivenDoD}', '{totalCO2Avoided}', '{co2AvoidedDoD}'], 
              [$data->date, $data->totalMilesDriven, $data->milesDrivenDoD, $data->totalCO2Avoided, $data->co2AvoidedDoD], 
              $total_carbon);

$carbon_textarea = str_replace(['{date}', '{totalMilesDriven}', '{milesDrivenDoD}', '{totalCO2Avoided}', '{co2AvoidedDoD}'], 
                 [$data->date, $data->totalMilesDriven, $data->milesDrivenDoD, $data->totalCO2Avoided, $data->co2AvoidedDoD], 
                 $carbon_textarea);

    }
    if (!empty($carbon_heading) && !empty($total_carbon) && !empty($carbon_textarea)) {
        ob_start(); 
        echo '<h3 class="carbontracker-heading">'.$carbon_heading.'</h3>';
        ?>
        <div class="row">
        <div class="column-1">
            <?php echo $carbon_textarea; ?>
        </div>
        <div class="column-2 corban-box row" >
            <img src="<?php echo $plugin_url ?>img/icon.png" alt="icon" width="70px">
            <div class="flex-container">
                <div class="total-carbon-heading"><?php echo $total_carbon; ?></div>
                <div class="total-carbon" id="carbon-update"><?php echo number_format(calculateProjectedMiles($carbon_api_url)); ?></div>
            </div>    
    </div>
    </div>
    <?php $site_url = site_url(); ?>
    <script>
        jQuery(document).ready(function($) {
    setInterval(function() {
        $.ajax({
            url: '<?php echo $site_url;  ?>/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'carbontracker_api_request'
            },
            success: function(response) {
                // Update the content with the new data
                document.getElementById('carbon-update').innerHTML = response;
            },
            error: function(error) {
                console.log('Error:', error);
            }
        });
    }, <?php echo $carbon_timer; ?>); 
});
    </script>
        <?php
        $output = ob_get_clean(); 
    
        return $output;
    } else {
        return "contact Admin to add important information";
    }
}
// Register the shortcode
add_shortcode('carbontracker_live_shortcode', 'carbontracker_live_shortcode_action');

function carbontracker_live_enqueue_plugin_styles() {
    /**
     * Enqueue Plugin Styles for Carbon Tracker Live.
     *
     * This function is hooked into the WordPress 'wp_enqueue_scripts' action.
     * It enqueues the necessary styles and scripts for the Carbon Tracker Live plugin,
     * ensuring that jQuery is included and the custom plugin stylesheet ('style.css') is loaded.
     *
     * @since 1.0.0
     * 
     * @author Abdul Qadir
     */

    wp_enqueue_script('jquery');

    $plugin_url = plugin_dir_url(__FILE__);

    wp_enqueue_style('carbon-tracker', $plugin_url . 'css/style.css');
}
// Hook the function to the 'wp_enqueue_scripts' action
add_action('wp_enqueue_scripts', 'carbontracker_live_enqueue_plugin_styles');


function carbontracker_live_update(){

    /**
     * AJAX Handler for Carbon Tracker Live Update.
     *
     * This function is an AJAX handler that responds to requests for live updates from the Carbon Tracker.
     * It retrieves the API URL from the plugin options, calculates the projected miles using the
     * `calculateProjectedMiles` function, formats the result with commas using `number_format`,
     * and sends the updated miles as a JSON response.
     *
     * @since 1.0.0
     * 
     * @author  Abdul Qadir 
     * 
     * @return int|string If successful, returns the projected miles as an integer.
     * 
     */
    $carbon_api_url = get_option( 'carbon_api_url' );
    wp_send_json( number_format(calculateProjectedMiles($carbon_api_url)) );
    
}

// Register the AJAX actions for logged-in and non-logged-in users
add_action( 'wp_ajax_carbontracker_api_request', 'carbontracker_live_update' );
add_action( 'wp_ajax_nopriv_carbontracker_api_request', 'carbontracker_live_update' );

function calculateProjectedMiles($api_url) {

    /**
     * Calculate Projected Miles based on API response.
     *
     * This function fetches data from the provided API URL, calculates the time difference
     * between the current server time and the API date, determines the rate of change,
     * and projects the miles driven using the formula:
     * projectedMiles = totalMilesDriven + (milesDrivenDoD / 24 / 60 / 60) * timeDifference.
     *
     * @author  Abdul Qadir 
     * 
     * @since   1.0.0
     * 
     * 
     * @param string $api_url The URL of the API providing the necessary data.
     * @return int|string If successful, returns the projected miles as an integer.
     * If there is an error fetching API data, returns an error message.
     */

    $response = wp_remote_get($api_url);

    if (is_array($response) && !is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);

        $data = json_decode($body);

        $now = time();

        $apiDate = strtotime($data->date);

        $timeDifference = $now - $apiDate;

        $mps = $data->milesDrivenDoD / 24 / 60 / 60;

        $projectedMiles = $data->totalMilesDriven + $mps * $timeDifference;

        return intval($projectedMiles);
    } else {

        return "Error fetching API data";
    }
}

