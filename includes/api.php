<?php

function get_chatbot_questions() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_flow';
    $results = $wpdb->get_results("SELECT * FROM $table_name");
    return rest_ensure_response($results);
}

function get_chatbot_questions_by_parent($data) {
    global $wpdb;
    $parent_id = $data['parent_id'];
    $table_name = $wpdb->prefix . 'chatbot_flow';
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE parent_id = %d", $parent_id));
    return rest_ensure_response($results);
}

add_action('rest_api_init', function () {
    register_rest_route('chatbot/v1', '/questions', [
        'methods' => 'GET',
        'callback' => 'get_chatbot_questions',
    ]);

    register_rest_route('chatbot/v1', '/questions/(?P<parent_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_chatbot_questions_by_parent',
    ]);
});
