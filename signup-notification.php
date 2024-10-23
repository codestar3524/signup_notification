<?php
/*
Plugin Name: Signup Notification
Description: Displays newly registered plates in a notification box on your website.
Version: 2.0
Author: Junior dawkins
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register AJAX endpoints
add_action('wp_ajax_get_new_plates', 'notifi_plates_get_new_entries');
add_action('wp_ajax_nopriv_get_new_plates', 'notifi_plates_get_new_entries');
add_action('wp_footer', 'notifi_plates_display');
add_action('wp_enqueue_scripts', 'notifi_plates_enqueue_assets');

function notifi_plates_get_new_entries() {
    global $wpdb;

    $time_ago = date('Y-m-d H:i:s', strtotime('-20 seconds'));

    $qry = "SELECT e.id, e.date_created, e.payment_status, e.payment_amount, 
                   u.display_name, u.user_email, 
                   MAX(CASE WHEN em.meta_key = '6' THEN em.meta_value END) as country,
                   MAX(CASE WHEN em.meta_key = '3' THEN em.meta_value END) as dog_name,
                   MAX(CASE WHEN em.meta_key = '16' THEN em.meta_value END) as state,
                   MAX(CASE WHEN em.meta_key = '1' THEN em.meta_value END) as type,
                   e.referral
            FROM {$wpdb->prefix}gf_entry e
            JOIN $wpdb->users u ON e.created_by = u.ID
            JOIN {$wpdb->prefix}gf_entry_meta em ON e.id = em.entry_id
            WHERE e.form_id = 1
            AND e.status = 'active'
            AND e.date_created >= %s
            GROUP BY e.id, e.date_created, e.payment_status, e.payment_amount, u.display_name, u.user_email, e.referral
            ORDER BY e.date_created DESC";

    $entries = $wpdb->get_results($wpdb->prepare($qry, $time_ago));
    
    $formatted_entries = array_map(function($entry) {
        return array(
            'id' => $entry->id,
            'dog_name' => $entry->dog_name,
            'state' => $entry->state,
            'type' => trim(str_replace("Registration", "", $entry->type)),
            'date' => human_time_diff(strtotime(get_date_from_gmt($entry->date_created)), current_time('timestamp')) . ' ago'
        );
    }, $entries);

    wp_send_json_success($formatted_entries);
}

function notifi_plates_display() {
    ?>
    <div id="notifi-plates-container" class="notifi-plates">
        <div class="notifi-plate" style="display: none;">
            <div class="plate-icon">
                <?php echo wp_get_attachment_image(114); ?>
            </div>
            <div class="plate-info"></div>
        </div>
    </div>
    <?php
}

function notifi_plates_enqueue_assets() {
    wp_enqueue_style(
        'notifi-plates-styles', 
        plugins_url('assets/css/notifi-plates.css', __FILE__),
        array(),
        '2.0'
    );
    
    wp_enqueue_script(
        'notifi-plates-script',
        plugins_url('assets/js/notifi-plates.js', __FILE__),
        array('jquery'),
        '2.0',
        true
    );

    wp_localize_script('notifi-plates-script', 'notifiPlatesAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('notifi_plates_nonce')
    ));
}