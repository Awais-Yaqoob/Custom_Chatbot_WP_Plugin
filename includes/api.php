<?php

add_action('rest_api_init', function () {
    register_rest_route('chatbot/v1', '/questions', array(
        'methods' => 'GET',
        'callback' => 'get_chatbot_questions',
        'permission_callback' => '__return_true'
    ));
});

function get_chatbot_questions($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_flow';
    $parent_id = isset($data['parent_id']) ? intval($data['parent_id']) : null;

    if ($parent_id === null) {
        // Fetch initial question with no parent
        $results = $wpdb->get_results("SELECT * FROM $table_name WHERE parent_id IS NULL AND is_option = 0");
    } else {
        // Fetch questions where parent_id matches the selected option
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE parent_id = %d AND is_option = 0", $parent_id));
    }

    foreach ($results as $result) {
        $result->response_data = json_decode($result->response_data); // Decode JSON if options are present
    }

    return $results ? $results : new WP_Error('no_data', 'No questions found', array('status' => 404));
}
