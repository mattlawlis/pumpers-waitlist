<?php

define('PUMPERS_WAITLIST_OPTION_GROUP', 'pumpers_waitlist_options_group');
define('PUMPERS_WAITLIST_OPTION_NAME', 'pumpers_waitlist_options');
define('PUMPERS_WAITLIST_SETTINGS_PAGE', 'pumpers-waitlist');

function pumpers_waitlist_add_menu() {
    add_options_page(
        'Pumpers Waitlist Settings',
        'Pumpers Waitlist',
        'manage_options',
        'pumpers-waitlist',
        'pumpers_waitlist_settings_page'
    );
}
add_action('admin_menu', 'pumpers_waitlist_add_menu');

function pumpers_waitlist_settings_page() {
    ?>
    <div class="wrap">
        <h1>Pumpers Waitlist Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('pumpers_waitlist_options_group');
            do_settings_sections('pumpers-waitlist');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function pumpers_waitlist_register_settings() {
    register_setting(
        'pumpers_waitlist_options_group',
        'pumpers_waitlist_options',
        'pumpers_waitlist_sanitize_options'
    );

    add_settings_section(
        'pumpers_waitlist_sms_settings',
        'SMS Settings',
        'pumpers_waitlist_sms_settings_description',
        'pumpers-waitlist'
    );

    // Twilio fields
    add_settings_field(
        'twilio_account_sid',
        'Twilio Account SID',
        'pumpers_waitlist_twilio_account_sid_render',
        'pumpers-waitlist',
        'pumpers_waitlist_sms_settings'
    );

    add_settings_field(
        'twilio_auth_token',
        'Twilio Auth Token',
        'pumpers_waitlist_twilio_auth_token_render',
        'pumpers-waitlist',
        'pumpers_waitlist_sms_settings'
    );

    add_settings_field(
        'twilio_phone_number',
        'Twilio Phone Number',
        'pumpers_waitlist_twilio_phone_number_render',
        'pumpers-waitlist',
        'pumpers_waitlist_sms_settings'
    );

    // SMS message templates fields
    add_settings_field(
        'sms_template_added',
        'SMS Template - Added to Waitlist',
        'pumpers_waitlist_sms_template_added_render',
        'pumpers-waitlist',
        'pumpers_waitlist_sms_settings'
    );

    add_settings_field(
        'sms_template_seated',
        'SMS Template - Guest Seated',
        'pumpers_waitlist_sms_template_seated_render',
        'pumpers-waitlist',
        'pumpers_waitlist_sms_settings'
    );

    add_settings_field(
        'sms_template_canceled',
        'SMS Template - Guest Canceled',
        'pumpers_waitlist_sms_template_canceled_render',
        'pumpers-waitlist',
        'pumpers_waitlist_sms_settings'
    );
}
add_action('admin_init', 'pumpers_waitlist_register_settings');

// SMS Settings Section Description
function pumpers_waitlist_sms_settings_description() {
    ?>
    <p><?php esc_html_e('Configure your Twilio credentials and customize the SMS message templates.', 'pumpers-waitlist'); ?></p>
    <p><?php esc_html_e('Available placeholders for messages:', 'pumpers-waitlist'); ?></p>
    <ul>
        <li><strong>{name}</strong> - <?php esc_html_e("Guest's name", 'pumpers-waitlist'); ?></li>
        <li><strong>{quoted_time}</strong> - <?php esc_html_e('Quoted wait time', 'pumpers-waitlist'); ?></li>
        <li><strong>{position}</strong> - <?php esc_html_e("Guest's position in the waitlist", 'pumpers-waitlist'); ?></li>
        <li><strong>{guest_count}</strong> - <?php esc_html_e('Number of guests in the party', 'pumpers-waitlist'); ?></li>
    </ul>
    <?php
}

// Twilio Account SID
function pumpers_waitlist_twilio_account_sid_render() {
    $options = get_option('pumpers_waitlist_options', array());
    $twilio_account_sid = isset($options['twilio_account_sid']) ? $options['twilio_account_sid'] : '';
    ?>
    <input type='text' name='pumpers_waitlist_options[twilio_account_sid]' value='<?php echo esc_attr($twilio_account_sid); ?>' style="width: 400px;">
    <p class="description"><?php esc_html_e('Enter your Twilio Account SID here. You can find this in your Twilio dashboard.', 'pumpers-waitlist'); ?></p>
    <?php
}

// Twilio Auth Token
function pumpers_waitlist_twilio_auth_token_render() {
    $options = get_option('pumpers_waitlist_options');
    $twilio_auth_token = isset($options['twilio_auth_token']) ? $options['twilio_auth_token'] : '';
    ?>
    <input type='password' name='pumpers_waitlist_options[twilio_auth_token]' value='<?php echo esc_attr($twilio_auth_token); ?>' style="width: 400px;">
    <?php
}

// Twilio Phone Number
function pumpers_waitlist_twilio_phone_number_render() {
    $options = get_option('pumpers_waitlist_options');
    $twilio_phone_number = isset($options['twilio_phone_number']) ? $options['twilio_phone_number'] : '';
    ?>
    <input type='text' name='pumpers_waitlist_options[twilio_phone_number]' value='<?php echo esc_attr($twilio_phone_number); ?>' style="width: 400px;">
    <p>Enter the Twilio phone number in E.164 format (e.g., +1234567890).</p>
    <?php
}

// SMS Template - Added to Waitlist
function pumpers_waitlist_sms_template_added_render() {
    $options = get_option('pumpers_waitlist_options');
    $template = isset($options['sms_template_added']) ? $options['sms_template_added'] : 'You have been added to the waitlist.';
    ?>
    <textarea name='pumpers_waitlist_options[sms_template_added]' rows='3' cols='70'><?php echo esc_textarea($template); ?></textarea>
    <p>Use placeholders: {name}, {guest_count}, {quoted_time}, {position}</p>
    <?php
}

// SMS Template - Guest Seated
function pumpers_waitlist_sms_template_seated_render() {
    $options = get_option('pumpers_waitlist_options');
    $template = isset($options['sms_template_seated']) ? $options['sms_template_seated'] : 'Your table is ready. Please proceed to the host stand.';
    ?>
    <textarea name='pumpers_waitlist_options[sms_template_seated]' rows='3' cols='70'><?php echo esc_textarea($template); ?></textarea>
    <p>Use placeholders: {name}</p>
    <?php
}

// SMS Template - Guest Canceled
function pumpers_waitlist_sms_template_canceled_render() {
    $options = get_option('pumpers_waitlist_options');
    $template = isset($options['sms_template_canceled']) ? $options['sms_template_canceled'] : 'You have been removed from the waitlist.';
    ?>
    <textarea name='pumpers_waitlist_options[sms_template_canceled]' rows='3' cols='70'><?php echo esc_textarea($template); ?></textarea>
    <p>Use placeholders: {name}</p>
    <?php
}

function pumpers_waitlist_sanitize_options($input) {
    $sanitized_input = array();
    
    if (isset($input['twilio_account_sid'])) {
        $sanitized_input['twilio_account_sid'] = sanitize_text_field($input['twilio_account_sid']);
    }
    
    if (isset($input['twilio_auth_token'])) {
        $sanitized_input['twilio_auth_token'] = sanitize_text_field($input['twilio_auth_token']);
    }
    
    if (isset($input['twilio_phone_number'])) {
        $sanitized_input['twilio_phone_number'] = sanitize_text_field($input['twilio_phone_number']);
    }
    
    // Sanitize and validate SMS templates
    $templates = ['sms_template_added', 'sms_template_seated', 'sms_template_canceled'];
    foreach ($templates as $template) {
        if (isset($input[$template])) {
            $sanitized_input[$template] = wp_kses_post($input[$template]);
        }
    }
    
    return $sanitized_input;
}
?>
