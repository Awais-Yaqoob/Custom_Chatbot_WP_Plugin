<?php

add_action('rest_api_init', function () {
    register_rest_route('chatbot/v1', '/questions', array(
        'methods' => 'GET',
        'callback' => 'get_chatbot_questions',
        'permission_callback' => '__return_true'
    ));
});

add_action('rest_api_init', function () {
    register_rest_route('chatbot/v1', '/appearance', array(
            'methods' => 'GET',
            'callback' => 'get_chatbot_appearance_settings',
            'permission_callback' => '__return_true'
        
    ));
});

function get_chatbot_questions($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'customQY_chatbot_flow';

    // Fetch all questions
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    if ($wpdb->last_error) {
        error_log("Database error: " . $wpdb->last_error);
        return new WP_Error('db_error', 'Database error occurred', array('status' => 500));
    }

    // Decode response_data if it is valid JSON, otherwise treat it as a plain URL
    foreach ($results as $result) {
        if (!empty($result->response_data) && is_string($result->response_data)) {
            $decoded_data = json_decode($result->response_data, true);
            $result->response_data = (json_last_error() === JSON_ERROR_NONE) ? $decoded_data : $result->response_data;
        } else {
            $result->response_data = null;
        }
    }

    return $results ? $results : [];
}

function get_chatbot_appearance_settings() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'customQY_appearance_settings';

    // Fetch appearance settings from the database
    $settings = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");

    if ($wpdb->last_error) {
        error_log("Database error: " . $wpdb->last_error);
        return new WP_Error('db_error', 'Database error occurred', array('status' => 500));
    }

    return $settings ? $settings : new stdClass(); // Return empty object if no settings found
}


