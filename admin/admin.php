<?php

 // Add an admin menu page
 function carbon_tracker_live_menu() {
   
    /**
     * Add an admin menu page for Carbon Tracker Live.
     *
     * This function adds an admin menu page for the Carbon Tracker Live plugin.
     * It specifies the page title, menu title, capability required to access, menu slug,
     * callback function for page content, icon (using 'dashicons-chart-line'), and position.
     *
     * @since 1.0.0
     * 
     * @author  Abdul Qadir 
     * 
     */
    add_menu_page(
        'CarbonTracker Live',
        'CarbonTracker Live',
        'manage_options',
        'carbon-tracker-page',
        'carbon_tracker_live_page',
        'dashicons-chart-line', // You can change the icon
        30
    );
}
// Hook the menu addition to the admin menu action
add_action('admin_menu', 'carbon_tracker_live_menu');

// Callback function for the admin page
function carbon_tracker_live_page() {
    /**
     * Callback function for the admin page of Carbon Tracker Live.
     *
     * This function displays the content for the admin page of the Carbon Tracker Live plugin.
     * It retrieves live API data, displays the data if available, and provides a form for
     * configuring plugin settings such as API URL, heading, total carbon, textarea content,
     * and timer interval.
     *
     * @since 1.0.0
     * 
     * @author  Abdul Qadir 
     */

    $carbon_api_url = get_option( 'carbon_api_url' );

    
    $response = wp_remote_get($carbon_api_url);


    ?>
    <div class="wrap">
        <h2>CarbonTracker Live</h2>
        <?php
            if (is_array($response) && !is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
        
                $data = json_decode($body);
        
            
        ?>
        <p>Here is live API data.</p>
        <?php
            echo "<p><strong>Date: </strong>".$data->date."</p>";
            echo "<p><strong>totalMilesDriven: </strong>".$data->totalMilesDriven."</p>";
            echo "<p><strong>milesDrivenDoD: </strong>".$data->milesDrivenDoD."</p>";
            echo "<p><strong>totalCO2Avoided: </strong>".$data->totalCO2Avoided."</p>";
            echo "<p><strong>co2AvoidedDoD: </strong>".$data->co2AvoidedDoD."</p>";
        ?>
        <p>Please use following perameter to create templete to serve the shortcode: <br>
    {date}, {totalMilesDriven}, {milesDrivenDoD}, {totalCO2Avoided}, and {co2AvoidedDoD}
    </p>
    <?php
    }else{
        echo "issue with API please check API";
    }
    ?>
        <form method="post" action="options.php">
            <?php settings_fields('carbon-tracker-settings-group'); ?>
            <?php do_settings_sections('carbon-tracker-page'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="carbon-api-url">API URL</label></th>
                    <td><input type="text" id="carbon-api-url" name="carbon_api_url" class="regular-text" value="<?php echo esc_attr(get_option('carbon_api_url')); ?>" placeholder="Add the API URL here."></td>
                </tr>
                <tr>
                    <th scope="row"><label for="carbon-heading">Heading</label></th>
                    <td><input type="text" id="carbon-heading" name="carbon_heading" class="regular-text" value="<?php echo esc_attr(get_option('carbon_heading')); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="total-carbon">Total Carbon</label></th>
                    <td><input type="text" id="total-carbon" name="total_carbon" class="regular-text" value="<?php echo esc_attr(get_option('total_carbon')); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="carbon-textarea">Textarea</label></th>
                    <td><textarea id="carbon-textarea" name="carbon_textarea" rows="4" class="large-text"><?php echo esc_html(get_option('carbon_textarea')); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="carbon-textarea">Set the timer for reload</label></th>
                    <td><input type="number" name="carbon_timer" value="<?php echo esc_attr( get_option( 'carbon_timer' ) ); ?>" placeholder="Add time in seconds." ></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}


function carbon_tracker_live_settings() {

    /**
     * Register and initialize the settings for Carbon Tracker Live.
     *
     * This function registers and initializes the settings for the Carbon Tracker Live plugin.
     * It defines the settings group and the individual settings fields, such as Carbon API URL,
     * heading, total carbon, textarea content, and timer interval.
     *
     * @author  Abdul Qadir 
     * 
     * @since 1.0.0
     */

    register_setting('carbon-tracker-settings-group', 'carbon_api_url');
    register_setting('carbon-tracker-settings-group', 'carbon_heading');
    register_setting('carbon-tracker-settings-group', 'total_carbon');
    register_setting('carbon-tracker-settings-group', 'carbon_textarea');
    register_setting('carbon-tracker-settings-group', 'carbon_timer');
}
// Hook the settings registration to the admin initialization action
add_action('admin_init', 'carbon_tracker_live_settings');