<?php

function pumpers_waitlist_install() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'pumpers_waitlist';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        phone varchar(20) NOT NULL,
        quoted_time varchar(50) NOT NULL,
        guest_count int(3) NOT NULL DEFAULT 1,
        added_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status varchar(20) NOT NULL DEFAULT 'waiting',
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql);

    if (is_wp_error($result)) {
        error_log('Error creating pumpers_waitlist table: ' . $result->get_error_message());
    } else {
        // Update the database version option to 1.2
        update_option('pumpers_waitlist_db_version', '1.2');
    }
}

// Uninstallation hook is handled in the main plugin file

function pumpers_waitlist_update_db_check() {
    $installed_ver = get_option('pumpers_waitlist_db_version');

    if (version_compare($installed_ver, '1.2', '<')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pumpers_waitlist';

        // Ensure 'id' column exists
        $id_column = $wpdb->get_results("SHOW COLUMNS FROM `$table_name` LIKE 'id'");
        if (empty($id_column)) {
            $wpdb->query("ALTER TABLE `$table_name` ADD COLUMN `id` MEDIUMINT(9) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
            error_log("Added 'id' column to pumpers_waitlist table.");
        }

        // Ensure 'guest_count' column exists
        $guest_count_column = $wpdb->get_results("SHOW COLUMNS FROM `$table_name` LIKE 'guest_count'");
        if (empty($guest_count_column)) {
            $wpdb->query("ALTER TABLE `$table_name` ADD COLUMN `guest_count` INT(3) NOT NULL DEFAULT 1 AFTER `quoted_time`");
            error_log("Added 'guest_count' column to pumpers_waitlist table.");
        }

        // Ensure 'added_time' column exists
        $added_time_column = $wpdb->get_results("SHOW COLUMNS FROM `$table_name` LIKE 'added_time'");
        if (empty($added_time_column)) {
            $wpdb->query("ALTER TABLE `$table_name` ADD COLUMN `added_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `guest_count`");
            error_log("Added 'added_time' column to pumpers_waitlist table.");
        }

        // Remove the 'notes' column if it exists
        $notes_column = $wpdb->get_results("SHOW COLUMNS FROM `$table_name` LIKE 'notes'");
        if (!empty($notes_column)) {
            $wpdb->query("ALTER TABLE `$table_name` DROP COLUMN `notes`");
            error_log("Removed 'notes' column from pumpers_waitlist table.");
        }

        // Update the database version option to 1.2
        update_option('pumpers_waitlist_db_version', '1.2');
    }
}
add_action('plugins_loaded', 'pumpers_waitlist_update_db_check');

?>

