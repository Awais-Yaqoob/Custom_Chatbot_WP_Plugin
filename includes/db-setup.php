<?php

function create_chatbot_flow_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_flow';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        question text NOT NULL,
        response_type varchar(50) NOT NULL,
        response_data text DEFAULT NULL,
        parent_id text DEFAULT NULL,  -- Update to text for JSON storage
        is_option tinyint(1) DEFAULT 0,
        PRIMARY KEY  (id)
    ) {$wpdb->get_charset_collate()};";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}



function insert_initial_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_flow';

    // Clear existing data
    $wpdb->query("TRUNCATE TABLE $table_name");

    // Insert initial data
    $wpdb->insert($table_name, [
        'question' => 'How may I help you?',
        'response_type' => 'options',
        'response_data' => json_encode(['Need Help', 'Verification Insurance', 'Ask A Question', 'Tour Our Facility']),
    ]);
    $wpdb->insert($table_name, [
        'question' => 'What Symptoms are you having?',
        'parent_id' => 1,
        'response_type' => 'options',
        'response_data' => json_encode(['Anxiety', 'Depression', 'Insomnia', 'Mood Disorder', 'Self Harm', 'Suicidal Thoughts']),
    ]);
    $wpdb->insert($table_name, [
        'question' => 'How long have you been suffering from Anxiety?',
        'parent_id' => 2,
        'response_type' => 'options',
        'response_data' => json_encode(['0-3 Years', '3-5 Years', 'Not Sure']),
    ]);
}