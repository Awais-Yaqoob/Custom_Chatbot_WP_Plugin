<?php
function create_chatbot_tables() {
    global $wpdb;
    $chatbot_table = $wpdb->prefix . 'customQY_chatbot_flow';
    $appearance_table = $wpdb->prefix . 'customQY_appearance_settings';

    $charset_collate = $wpdb->get_charset_collate();

    // Table for chatbot questions and responses
    $sql1 = "CREATE TABLE $chatbot_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        question text NOT NULL,
        response_type varchar(50) NOT NULL,
        response_data text DEFAULT NULL,
        parent_id text DEFAULT NULL,
        is_option tinyint(1) DEFAULT 0,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Table for appearance settings
    $sql2 = "CREATE TABLE $appearance_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) DEFAULT NULL,
        logo_url varchar(255) DEFAULT NULL,
        font varchar(50) DEFAULT NULL,
        primary_color varchar(7) DEFAULT NULL,
        secondary_color varchar(7) DEFAULT NULL,
        logo_size int DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
}
