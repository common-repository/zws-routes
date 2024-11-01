<?php
/*
 * Plugin Name: ZWS Routes
 * Description: This plugin helps you to find your routes.
 * Version: 1.4
 * Author: Zia Web Solutions
 * Author URI: http://ziawebsolutions.com/
 */

function zwsRoutes_install()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'zws_routes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  day varchar(60) DEFAULT '' NOT NULL,
  start varchar(60) DEFAULT '' NOT NULL,
  waypoints varchar(60) DEFAULT '' NOT NULL,
  end varchar(60) DEFAULT '' NOT NULL,
  UNIQUE KEY id (id)
) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'zwsRoutes_install');

// add the admin options page
add_action('admin_menu', 'zws_routesMenu');

// plugin menu
if (!function_exists('zws_routesMenu')) {

    function zws_routesMenu()
    {
        add_menu_page('ZWS Routes Plugin', 'ZWS Routes Menu', 'manage_options', 'zwsroutes-plugin', 'zws_waypointsOptions');
    }

}

function zws_waypointsOptions()
{
    ?>
    <div class="wrap">
        <p><?php echo esc_html('Enter your google API key'); ?></p>
        <form name="mapform" id="mapform" method="post" action="">
            <input type="text" name="your-google-api-key"><br /><br />
            <?php if (get_option('zws_google_api_key_col')) { ?>
                <input type="submit" name="api_submit" value="<?php esc_html_e('Update') ?>">
            <?php } else { ?>
                <input type="submit" name="api_submit" value="<?php esc_html_e('Save') ?>">
            <?php } ?>
        </form>
        <p><br /><?php echo esc_html('To show ZWS Routes in the frontend, please create a page and use this [zwsroutes-shortcode] shortcode.'); ?></p>
    </div>
    <?php
    if (isset($_POST['your-google-api-key'])) {
        if (!get_option('zws_google_api_key_col')) {
            add_option('zws_google_api_key_col', sanitize_text_field($_POST['your-google-api-key']));
            echo '</br>' . "API key successfully added.";
        } else {
            update_option('zws_google_api_key_col', sanitize_text_field($_POST['your-google-api-key']));
            echo '</br>' . "API key successfully Updated.";
        }
    }
}

$zws_google_api_key = get_option('zws_google_api_key_col');

//Include Plugins own style
wp_enqueue_style('zwsroutesStyles', plugins_url('/css/zwsroutes_styles.css', __FILE__));

//Include Plugins own Script
wp_enqueue_script('zwsroutesScript', plugins_url('/js/zwsroutes_script.js', __FILE__), array('jquery'));
//Include Google API
wp_enqueue_script('google-maps', '//maps.googleapis.com/maps/api/js?key=' . $zws_google_api_key . '&signed_in=true&libraries=places&callback=initMap', array(), '1.0', true);
// including ajax script in the plugin Myajax.ajaxurl
wp_localize_script('zwsroutesScript', 'MyAjax', array('ajaxurl' => admin_url('admin-ajax.php')));

function zws_addRoutes()
{
    global $wpdb;
    $day = sanitize_text_field($_POST['day']);
    $start = sanitize_text_field($_POST['start']);
    $waypoints = sanitize_text_field($_POST['waypoints']);
    $end = sanitize_text_field($_POST['end']);

    $check_day = $wpdb->get_var("SELECT day FROM " . $wpdb->prefix . "zws_routes WHERE day = '" . $day . "'");
    if ($check_day != $day) {
        $wpdb->insert(
                $wpdb->prefix . 'zws_routes', array(
            'day' => $day,
            'start' => $start,
            'waypoints' => $waypoints,
            'end' => $end
                ), array(
            '%s',
            '%s',
            '%s',
            '%s'
                )
        );
        $lastid = $wpdb->insert_id;
        echo json_encode($lastid);
        exit();
    } else {
        $wpdb->update(
                $wpdb->prefix . 'zws_routes', array(
            'day' => $day,
            'start' => $start,
            'waypoints' => $waypoints,
            'end' => $end
                ), array('day' => $day), array(
            '%s',
            '%s',
            '%s',
            '%s'
                ), array('%s')
        );
        $lastid = 'Successfully updated the route';
        echo json_encode($lastid);
        exit();
    }
}

function zws_getRoutes()
{
    $day = sanitize_text_field($_POST['day']);
    global $wpdb;
    $result = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "zws_routes where day = '" . $day . "'");
    echo json_encode($result);
    exit();
}

add_action('wp_ajax_zws_addRoutes', 'zws_addRoutes');
add_action('wp_ajax_nopriv_zws_addRoutes', 'zws_addRoutes');

add_action('wp_ajax_zws_getRoutes', 'zws_getRoutes');
add_action('wp_ajax_nopriv_zws_getRoutes', 'zws_getRoutes');

function zws_displayMap()
{
    if (current_user_can('administrator')) {
        ?>
        <p id="no-route-msg">Currently there is no route on this day. Please use the form on the left side to add a route.</p>
    <?php } else { ?>
        <p id="no-route-msg">There is no route on this day.</p>
    <?php } ?>
    <div class="left-panel">
        <form>
            <select id="day-options">
                <option selected="true" disabled="disabled" value="">Select</option>
                <option value="<?php esc_html_e('Monday') ?>"><?php esc_html_e('Monday') ?></option>
                <option value="<?php esc_html_e('Tuesday') ?>"><?php esc_html_e('Tuesday') ?></option>
                <option value="<?php esc_html_e('Wednesday') ?>"><?php esc_html_e('Wednesday') ?></option>
                <option value="<?php esc_html_e('Thursday') ?>"><?php esc_html_e('Thursday') ?></option>
                <option value="<?php esc_html_e('Friday') ?>"><?php esc_html_e('Friday') ?></option>
                <option value="<?php esc_html_e('Saturday') ?>"><?php esc_html_e('Saturday') ?></option>
                <option value="<?php esc_html_e('Sunday') ?>"><?php esc_html_e('Sunday') ?></option>
            </select> 
            <br />
            <?php
            if (!current_user_can('administrator')) {
                ?>
                <p><?php echo esc_html('Please select a day. We have different routes for each day.'); ?></p>
            <?php } ?>

            <?php if (current_user_can('administrator')) { ?>

                <label><?php esc_html_e('Start') ?></label>
                <input type='text' id='start' name='start' value=''/>
                <br/>
                <label><?php esc_html_e('Waypoints') ?></label>
                <input type='text' id='waypoints' name='waypoints' value=''/>
                <br/>
                <label><?php esc_html_e('End') ?></label>
                <input type='text' id='end' name='end' value=''/>
                <?php wp_nonce_field('waypoints_nonce', 'zwsweb') ?>
                <input type='button' id='submit-routes' name='submit' value='Add Routes'/>

            <?php } ?>
        </form>
    </div>

    <?php if (!get_option('zws_google_api_key_col')) { ?>
        <p class="plugin-settings-msg"><?php echo esc_html('To view the map please enter your google API key to the Plugin settings option.'); ?></p>
    <?php } else { ?>
        <div id='map'></div>
        <?php
    }
}

add_shortcode('zwsroutes-shortcode', 'zws_displayMap');


