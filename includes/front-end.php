<?php

// Enqueue scripts and styles
function pumpers_waitlist_enqueue_scripts() {
    wp_enqueue_style('pumpers-waitlist-style', PUMPERS_WAITLIST_PLUGIN_URL . 'assets/css/style.css');
    wp_enqueue_script('pumpers-waitlist-script', PUMPERS_WAITLIST_PLUGIN_URL . 'assets/js/script.js', array('jquery'), null, true);
    wp_localize_script('pumpers-waitlist-script', 'pumpersWaitlist', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('pumpers_waitlist_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'pumpers_waitlist_enqueue_scripts');

// Shortcode function to display the waitlist
function pumpers_waitlist_shortcode() {
    ob_start();
    ?>
    <div id="pumpers-waitlist">
        <button id="pumpers-add-guest-btn">+ Add to Waitlist</button>
        <div id="pumpers-add-guest-form" style="display:none;">
            <!-- Form Fields -->
            <form id="pumpers-add-guest">
                <input type="text" name="name" placeholder="Guest Name" required>
                <input type="text" name="phone" placeholder="Phone Number" required>
                <input type="text" name="quoted_time" placeholder="Quoted Wait Time" required>
                <input type="number" name="guest_count" placeholder="Number of Guests in Party" min="1" required>
                <button type="submit">Add Guest</button>
            </form>
        </div>
        <div id="pumpers-waitlist-table">
            <!-- Waitlist Table -->
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Quote</th>
                        <th>Added</th>
                        <th>Guests</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Guest Entries Will Be Loaded Here -->
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('pumpers_waitlist', 'pumpers_waitlist_shortcode');

// AJAX function to get the waitlist
function pumpers_get_waitlist() {
    check_ajax_referer('pumpers_waitlist_nonce', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'pumpers_waitlist';

    // Fetch results with explicit field selection
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, phone, quoted_time, guest_count, added_time, status 
         FROM $table_name 
         WHERE status = %s 
         ORDER BY added_time ASC",
        'waiting'
    ));

    if (empty($results)) {
        wp_send_json_success([]);
    }

    // Add position numbers and format time_added
    foreach ($results as $index => $guest) {
        $guest->position = $index + 1;

        // Ensure added_time is set
        if (!empty($guest->added_time)) {
            // Create a DateTime object from added_time
            $date = new DateTime($guest->added_time, new DateTimeZone('UTC')); // Adjust if your added_time is stored in a different timezone

            // Set timezone to CST
            $date->setTimezone(new DateTimeZone('America/Chicago'));

            // Format the time as HH:MM AM/PM
            $guest->time_added = $date->format('h:i A');
        } else {
            $guest->time_added = 'N/A';
        }
    }

    wp_send_json_success($results);
}
add_action('wp_ajax_pumpers_get_waitlist', 'pumpers_get_waitlist');
add_action('wp_ajax_nopriv_pumpers_get_waitlist', 'pumpers_get_waitlist');

// AJAX function to add a guest
function pumpers_add_guest() {
    check_ajax_referer('pumpers_waitlist_nonce', 'nonce');

    // Sanitize and validate inputs
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $quoted_time = isset($_POST['quoted_time']) ? sanitize_text_field($_POST['quoted_time']) : '';
    $guest_count = isset($_POST['guest_count']) ? intval($_POST['guest_count']) : 1;

    if (empty($name) || empty($phone) || empty($quoted_time)) {
        wp_send_json_error('Please fill in all required fields.');
        return;
    }

    // Validate phone number
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 10) {
        $phone = '+1' . $phone;
    } elseif (strlen($phone) === 11 && $phone[0] === '1') {
        $phone = '+' . $phone;
    } else {
        wp_send_json_error('Invalid phone number format.');
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'pumpers_waitlist';

    $data = array(
        'name' => $name,
        'phone' => $phone,
        'quoted_time' => $quoted_time,
        'guest_count' => $guest_count,
        'status' => 'waiting'
    );

    $format = array('%s', '%s', '%s', '%d', '%s');

    $inserted = $wpdb->insert($table_name, $data, $format);

    if ($inserted) {
        // Get the position of the new guest
        $position = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'waiting'");

        // Retrieve message template
        $options = get_option('pumpers_waitlist_options');
        $message_template = isset($options['sms_template_added']) ? $options['sms_template_added'] : 'You have been added to the waitlist.';

        // Prepare placeholders
        $placeholders = [
            '{name}' => $name,
            '{quoted_time}' => $quoted_time,
            '{position}' => $position,
            '{guest_count}' => $guest_count
        ];

        $message = strtr($message_template, $placeholders);

        // Send SMS notification
        pumpers_send_sms($phone, $message);

        wp_send_json_success('Guest added successfully.');
    } else {
        $wpdb_last_error = $wpdb->last_error;
        error_log('Failed to add guest. MySQL error: ' . $wpdb_last_error);
        wp_send_json_error('Failed to add guest. Database error: ' . $wpdb_last_error);
    }
}
add_action('wp_ajax_pumpers_add_guest', 'pumpers_add_guest');
add_action('wp_ajax_nopriv_pumpers_add_guest', 'pumpers_add_guest');

// AJAX function to seat a guest
function pumpers_seat_guest() {
    check_ajax_referer('pumpers_waitlist_nonce', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'pumpers_waitlist';

    $id = intval($_POST['id']);

    // Update the guest's status to 'seated'
    $updated = $wpdb->update($table_name, array('status' => 'seated'), array('id' => $id), array('%s'), array('%d'));

    if ($updated !== false) {
        // Get the guest's information
        $guest = $wpdb->get_row($wpdb->prepare("SELECT name, phone FROM $table_name WHERE id = %d", $id));
        if ($guest) {
            $name = $guest->name;
            $phone = $guest->phone;

            // Retrieve message template
            $options = get_option('pumpers_waitlist_options');
            $message_template = isset($options['sms_template_seated']) ? $options['sms_template_seated'] : 'Your table is ready. Please proceed to the host stand.';

            // Prepare placeholders
            $placeholders = [
                '{name}' => $name
            ];

            // Replace placeholders in the template
            $message = strtr($message_template, $placeholders);

            // Send SMS notification
            pumpers_send_sms($phone, $message);
        }

        wp_send_json_success('Guest seated successfully.');
    } else {
        wp_send_json_error('Failed to seat guest.');
    }
}
add_action('wp_ajax_pumpers_seat_guest', 'pumpers_seat_guest');

// AJAX function to cancel a guest
function pumpers_cancel_guest() {
    check_ajax_referer('pumpers_waitlist_nonce', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'pumpers_waitlist';

    $id = intval($_POST['id']);

    // Update the guest's status to 'canceled'
    $updated = $wpdb->update($table_name, array('status' => 'canceled'), array('id' => $id), array('%s'), array('%d'));

    if ($updated !== false) {
        // Get the guest's information
        $guest = $wpdb->get_row($wpdb->prepare("SELECT name, phone FROM $table_name WHERE id = %d", $id));
        if ($guest) {
            $name = $guest->name;
            $phone = $guest->phone;

            // Retrieve message template
            $options = get_option('pumpers_waitlist_options');
            $message_template = isset($options['sms_template_canceled']) ? $options['sms_template_canceled'] : 'You have been removed from the waitlist.';

            // Prepare placeholders
            $placeholders = [
                '{name}' => $name
            ];

            // Replace placeholders in the template
            $message = strtr($message_template, $placeholders);

            // Send SMS notification
            pumpers_send_sms($phone, $message);
        }

        wp_send_json_success('Guest canceled successfully.');
    } else {
        wp_send_json_error('Failed to cancel guest.');
    }
}
add_action('wp_ajax_pumpers_cancel_guest', 'pumpers_cancel_guest');

?>
