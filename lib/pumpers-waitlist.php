<?php
/*
Plugin Name: Pumpers Waitlist
Description: A waitlist plugin for Pumpers with SMS notifications.
Version: 1.0
Author: LAWLESS MEDIA
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Define plugin constants
define('PUMPERS_WAITLIST_VERSION', '1.0');
define('PUMPERS_WAITLIST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PUMPERS_WAITLIST_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PUMPERS_WAITLIST_PLUGIN_FILE', __FILE__);

// Include necessary files
include_once PUMPERS_WAITLIST_PLUGIN_DIR . 'includes/database.php';
include_once PUMPERS_WAITLIST_PLUGIN_DIR . 'includes/admin-settings.php';
include_once PUMPERS_WAITLIST_PLUGIN_DIR . 'includes/front-end.php';
include_once PUMPERS_WAITLIST_PLUGIN_DIR . 'includes/sms.php';

// Activation and deactivation hooks
register_activation_hook(PUMPERS_WAITLIST_PLUGIN_FILE, 'pumpers_waitlist_install');
register_deactivation_hook(PUMPERS_WAITLIST_PLUGIN_FILE, 'pumpers_waitlist_uninstall');

// Uninstallation hook
register_uninstall_hook(PUMPERS_WAITLIST_PLUGIN_FILE, 'pumpers_waitlist_uninstall');

// Uninstallation function
function pumpers_waitlist_uninstall() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pumpers_waitlist';

    // Delete the table
    $sql = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);

    // Delete the database version option
    delete_option('pumpers_waitlist_db_version');
}
?>
