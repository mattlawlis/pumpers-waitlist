<?php

// Adjust the path to the Twilio autoloader
require_once PUMPERS_WAITLIST_PLUGIN_DIR . 'lib/twilio-php/src/Twilio/autoload.php';

use Twilio\Rest\Client;

function pumpers_send_sms($to, $message) {
    $options = get_option('pumpers_waitlist_options', []);

    $account_sid    = $options['twilio_account_sid'] ?? '';
    $auth_token     = $options['twilio_auth_token'] ?? '';
    $twilio_number  = $options['twilio_phone_number'] ?? '';

    // Check if Twilio credentials are set
    if (empty($account_sid) || empty($auth_token) || empty($twilio_number)) {
        error_log('Twilio credentials are not set. SMS not sent.');
        return;
    }

    // Initialize the Twilio client
    $client = new Client($account_sid, $auth_token);

    // Format the phone number
    $to = format_phone_number($to);

    // Send the SMS
    try {
        $client->messages->create(
            $to,
            [
                'from' => $twilio_number,
                'body' => $message
            ]
        );
    } catch (\Twilio\Exceptions\RestException $e) {
        error_log('Twilio Error: ' . $e->getMessage());
    }
}

// Helper function to format phone numbers
function format_phone_number($number) {
    // Remove non-digit characters
    $number = preg_replace('/\D/', '', $number);

    // Add country code if missing (assuming US '+1')
    if (strlen($number) == 10) {
        return '+1' . $number;
    } elseif (strlen($number) == 11 && $number[0] == '1') {
        return '+' . $number;
    }

    // If it doesn't match these patterns, just prepend '+'
    return '+' . $number;
}

?>
