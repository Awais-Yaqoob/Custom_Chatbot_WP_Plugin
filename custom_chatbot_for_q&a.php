<?php
/*
Plugin Name: Advanced Custom Chatbot
Description: A chatbot plugin for custom questions and answers.
Version: 1.0
Author: Awais Y.
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue React scripts and styles
function react_chatbot_enqueue_scripts() {
    wp_enqueue_script(
        'react-chatbot-script',
        plugins_url('assets/index-35ZY6q-E.js', __FILE__), 
        array(),
        null,
        true
    );
    wp_enqueue_style(
        'react-chatbot-style',
        plugins_url('assets/index-Dt1CQmON.css', __FILE__)
    );
}
add_action('wp_enqueue_scripts', 'react_chatbot_enqueue_scripts', 20);


function enqueue_custom_media_uploader($hook) {
    // Restrict to the plugin’s admin page
    if ($hook !== 'toplevel_page_chatbot-qa') {
        return;
    }

    wp_enqueue_media(); // This function loads the media uploader
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_style('wp-color-picker');
}
add_action('admin_enqueue_scripts', 'enqueue_custom_media_uploader');

// Shortcode to render the chatbot
function react_chatbot_render() {
    return '<div id="root"></div>';
}
add_shortcode('react_chatbot', 'react_chatbot_render');

// Include database setup and API files
require_once plugin_dir_path(__FILE__) . 'includes/db-setup.php';
require_once plugin_dir_path(__FILE__) . 'includes/api.php';

// Add admin menu
add_action('admin_menu', 'chatbot_admin_menu');

function chatbot_admin_menu() {
    add_menu_page(
        'Chatbot Q&A',
        'Chatbot Q&A',
        'manage_options',
        'chatbot-qa',
        'chatbot_qa_page',
        'dashicons-format-chat',
        6
    );
}

// Admin page for managing questions
function chatbot_qa_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'customQY_chatbot_flow';

	if (isset($_POST['submit'])) {
    $question = sanitize_text_field($_POST['question']);
    $response_type = sanitize_text_field($_POST['response_type']);
    $parent_ids = isset($_POST['parent_id']) ? json_decode(stripslashes($_POST['parent_id']), true) : [];
    if (empty($parent_ids) || (is_array($parent_ids) && in_array(null, $parent_ids, true))) {
    $parent_ids_serialized = null;
} else {
    $parent_ids_serialized = json_encode($parent_ids);
}
    $response_data = '';

    // Check if response_type is "redirect" and save the link in response_data
    if ($response_type === 'redirect' && isset($_POST['redirect_link'])) {
        $response_data = sanitize_text_field($_POST['redirect_link']);
    } elseif ($response_type === 'options') {
        // If it's "options," gather the options data
       $options = isset($_POST['options']) ? array_filter(array_map('sanitize_text_field', $_POST['options'])) : [];

        $option_data = [];
    }
		elseif ($response_type === 'user-input') {
        // If it's "options," gather the options data
        $response_data = $_POST['input-type'];
		$options = isset($_POST['input-type']) ? $_POST['input-type'] : [];

        $option_data = [];
    }

    // Insert the main question into the database and get the ID
    $wpdb->insert($table_name, [
        'question' => $question,
        'response_type' => $response_type,
        'parent_id' => $parent_ids_serialized,
        'response_data' => $response_data,
        'is_option' => 0,
    ]);

    $question_id = $wpdb->insert_id; // Store the ID of the main question

    if ($response_type === 'options' && !empty($options)) {
        foreach ($options as $option) {
            $wpdb->insert($table_name, [
                'question' => $option,
                'parent_id' => $question_id, // Set the parent_id to main question ID
                'is_option' => 1,
            ]);
            $option_id = $wpdb->insert_id;
            $option_data[] = ['id' => $option_id, 'text' => $option];
        }

        $response_data = json_encode($option_data);

        // Update the main question with the options data
        $wpdb->update($table_name, [
            'response_data' => $response_data,
        ], ['id' => $question_id]);
    }
		  if ($response_type === 'user-input' && !empty($options)) {
        
            $wpdb->insert($table_name, [
                'question' => $options,
                'parent_id' => $question_id, // Set the parent_id to main question ID
                'is_option' => 1,
            ]);
            $option_id = $wpdb->insert_id;
            $option_data[] = ['id' => $option_id, 'text' => $options];
        

        $response_data = json_encode($option_data);

        // Update the main question with the options data
        $wpdb->update($table_name, [
            'response_data' => $response_data,
        ], ['id' => $question_id]);
    }
}

	
   // Handle delete request
if (isset($_GET['delete'])) {
    $id_to_delete = intval($_GET['delete']);

    // First, delete all child records
    $child_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM $table_name WHERE parent_id = %d", $id_to_delete));

    if (!empty($child_ids)) {
        foreach ($child_ids as $child_id) {
            $wpdb->delete($table_name, ['id' => $child_id]);
        }
    }

    // Then delete the main record
    $wpdb->delete($table_name, ['id' => $id_to_delete]);

    // Redirect to refresh the page
    echo '<script>window.location.href="' . admin_url('admin.php?page=chatbot-qa') . '"</script>';
}


    // Fetch all options for parent selection
    $options_records = $wpdb->get_results("SELECT id, question FROM $table_name WHERE is_option = 1");

    include plugin_dir_path(__FILE__) . 'includes/plugin-dashboard.php';
}



if (isset($_POST['update_question'])) {
    global $wpdb; // Ensure $wpdb is accessible
    $table_name = $wpdb->prefix . 'customQY_chatbot_flow'; // Define table name

    $id = intval($_POST['edit_id']);
    $question = sanitize_text_field($_POST['edit_question']);
    $response_type = sanitize_text_field($_POST['edit_response_type']);
    $response_data = '';

    if ($response_type === 'redirect' && isset($_POST['edit_redirect_link'])) {
        $response_data = sanitize_text_field($_POST['edit_redirect_link']);
    } elseif ($response_type === 'options') {
        $options = isset($_POST['edit_options']) ? array_map('sanitize_text_field', $_POST['edit_options']) : [];
        $response_data = json_encode(array_map(function($opt) { return ['text' => $opt]; }, $options));
    }

    // Update question in the database
    $wpdb->update($table_name, [
        'question' => $question,
        'response_type' => $response_type,
        'response_data' => $response_data
    ], ['id' => $id]);
}


function save_appearance_settings() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'customQY_appearance_settings';

    // Gather form data
    $title = sanitize_text_field($_POST['title']);
    $logo_url = sanitize_text_field($_POST['logo']);
    $font = sanitize_text_field($_POST['font']);
    $primary_color = sanitize_hex_color($_POST['primary-color']);
    $secondary_color = sanitize_hex_color($_POST['secondary-color']);
    $logo_size = intval($_POST['logo-size']);

    // Check if an appearance setting already exists
    $existing_setting = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");

    // Insert or update
    if ($existing_setting) {
        $wpdb->update($table_name, [
            'title' => $title,
            'logo_url' => $logo_url,
            'font' => $font,
            'primary_color' => $primary_color,
            'secondary_color' => $secondary_color,
            'logo_size' => $logo_size,
        ], ['id' => $existing_setting->id]);
    } else {
        $wpdb->insert($table_name, [
            'title' => $title,
            'logo_url' => $logo_url,
            'font' => $font,
            'primary_color' => $primary_color,
            'secondary_color' => $secondary_color,
            'logo_size' => $logo_size,
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appearance_form_submit'])) {
    save_appearance_settings();
}



// Activation hook to create the database table
register_activation_hook(__FILE__, 'create_chatbot_tables');
