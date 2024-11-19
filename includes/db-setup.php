<?php
function create_chatbot_tables() {
    global $wpdb;
    $chatbot_table = $wpdb->prefix . 'customQY_chatbot_flow';
    $appearance_table = $wpdb->prefix . 'customQY_appearance_settings';
	 $user_input_table = $wpdb->prefix . 'customQY_user_inputs';
	 $email_settings_table = $wpdb->prefix . 'customQY_email_settings';

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
	
	// New table for storing user inputs
  $sql3 = "CREATE TABLE $user_input_table (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_statement text NOT NULL,
    user_name varchar(255) DEFAULT NULL,
    user_email varchar(255) DEFAULT NULL,
    user_phone varchar(15) DEFAULT NULL,
    selected_options text DEFAULT NULL, -- Add this column to store selected options as JSON or text
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id)
) $charset_collate;";
	
	
	  // Table for email settings
    $sql4 = "CREATE TABLE $email_settings_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        is_enabled tinyint(1) DEFAULT 0, -- 0 for disabled, 1 for enabled
        email varchar(255) NOT NULL, -- Admin email address
        subject varchar(255) NOT NULL, -- Email subject
        email_heading varchar(255) DEFAULT NULL, -- Email heading
        created_at datetime DEFAULT CURRENT_TIMESTAMP, -- Timestamp for record creation
        PRIMARY KEY  (id)
    ) $charset_collate;";


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
	dbDelta($sql3);
	dbDelta($sql4);
}
