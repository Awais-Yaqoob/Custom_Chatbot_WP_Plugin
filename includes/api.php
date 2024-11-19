<?php

add_action('rest_api_init', function () {
    register_rest_route('customQY-chatbot/v1', '/questions', array(
        'methods' => 'GET',
        'callback' => 'get_chatbot_questions',
        'permission_callback' => '__return_true'
    ));
});

add_action('rest_api_init', function () {
    register_rest_route('customQY-chatbot/v1', '/appearance', array(
            'methods' => 'GET',
            'callback' => 'get_chatbot_appearance_settings',
            'permission_callback' => '__return_true'
        
    ));
});

add_action('rest_api_init', function () {
    register_rest_route('customQY-chatbot/v1', '/save-user-data', array(
        'methods' => 'POST',
        'callback' => 'save_user_data',
        'permission_callback' => '__return_true'
    ));
});

add_action('rest_api_init', function () {
    header("Access-Control-Allow-Origin: http://localhost:5173"); // Allow local development origin
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    // If it's an OPTIONS request, exit immediately
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        status_header(200);
        exit();
    }
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






    // Insert data into the database
 function save_user_data(WP_REST_Request $request) {
    global $wpdb;

    // Get data from the request
    $user_data = $request->get_json_params();

    // Sanitize and encode selected options
    $selected_options = isset($user_data['selected_options']) ? $user_data['selected_options'] : [];
    $sanitized_options = array_map(function ($option) {
        return isset($option['optionText']) ? sanitize_text_field($option['optionText']) : '';
    }, $selected_options);
    $selected_options_json = json_encode($sanitized_options);

    // Insert data into the database
    $table_name = $wpdb->prefix . 'customQY_user_inputs';
    $inserted = $wpdb->insert(
        $table_name,
        array(
            'user_statement' => sanitize_textarea_field($user_data['user_question']),
            'user_name' => sanitize_text_field($user_data['user_name']),
            'user_email' => sanitize_email($user_data['user_email']),
            'user_phone' => sanitize_text_field($user_data['user_phone']),
            'selected_options' => $selected_options_json, // Save selected options as JSON
        ),
        array('%s', '%s', '%s', '%s', '%s')
    );

    if ($inserted === false) {
        error_log("Database error: " . $wpdb->last_error);
        return new WP_Error('db_error', 'Failed to save user data', array('status' => 500));
    }

   // Check if email integration is enabled
    $email_settings_table = $wpdb->prefix . 'customQY_email_settings';
    $email_settings = $wpdb->get_row("SELECT * FROM $email_settings_table LIMIT 1");

    if ($email_settings && $email_settings->is_enabled) {
        // Email Integration
        $email_body = "<h2>" . esc_html($email_settings->email_heading) . "</h2>";
        $non_empty_fields = [];

        if (!empty($user_data['user_question'])) {
            $non_empty_fields['User Question'] = sanitize_text_field($user_data['user_question']);
        }
        if (!empty($user_data['user_name'])) {
            $non_empty_fields['User Name'] = sanitize_text_field($user_data['user_name']);
        }
        if (!empty($user_data['user_email'])) {
            $non_empty_fields['User Email'] = sanitize_email($user_data['user_email']);
        }
        if (!empty($user_data['user_phone'])) {
            $non_empty_fields['User Phone'] = sanitize_text_field($user_data['user_phone']);
        }
        if (!empty($sanitized_options)) {
            $non_empty_fields['Selected Options'] = implode(' → ', $sanitized_options);
        }

        foreach ($non_empty_fields as $key => $value) {
            $email_body .= "<p><strong>$key:</strong> $value</p>";
        }

        // Send Email
        $to = sanitize_email($email_settings->email);
        $subject = sanitize_text_field($email_settings->subject);
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $email_sent = wp_mail($to, $subject, $email_body, $headers);

        if (!$email_sent) {
            error_log("Failed to send email for user input.");
        }
    }

    return array(
        'status' => 'success',
        'message' => 'User data saved successfully',
        'data' => $user_data
    );
}