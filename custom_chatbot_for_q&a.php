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
        plugins_url('assets/index-Driau5As.js', __FILE__), 
        array(),
        null,
        true
    );
    wp_enqueue_style(
        'react-chatbot-style',
        plugins_url('assets/index-CNkWsFPE.css', __FILE__)
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
    global $wpdb;
    $table_name = $wpdb->prefix . 'customQY_chatbot_flow';

    $id = intval($_POST['edit_id']);
    $question = sanitize_text_field($_POST['edit_question']);
    $response_type = sanitize_text_field($_POST['edit_response_type']);
    $response_data = '';

    if ($response_type === 'options' && isset($_POST['edit_options'])) {
        $options = array_map('sanitize_text_field', $_POST['edit_options']);

        // Fetch existing child options to maintain IDs
        $existing_children = $wpdb->get_results(
            $wpdb->prepare("SELECT id, question FROM $table_name WHERE parent_id = %d", $id)
        );

        $existing_children_ids = array_map(function ($child) {
            return $child->id;
        }, $existing_children);

        $new_options = array_slice($options, 0, count($options));
        $new_children_count = count($new_options);

        $updated_response_data = [];

        // Update existing child options
        foreach ($new_options as $index => $new_option) {
            if ($index < count($existing_children_ids)) {
                $child_id = $existing_children_ids[$index];
                $wpdb->update(
                    $table_name,
                    ['question' => $new_option],
                    ['id' => $child_id],
                    ['%s'],
                    ['%d']
                );
                $updated_response_data[] = ['id' => $child_id, 'text' => $new_option];
            } else {
                // Add new child option if more options are provided
                $wpdb->insert(
                    $table_name,
                    [
                        'question' => $new_option,
                        'parent_id' => $id,
                        'is_option' => 1,
                    ],
                    ['%s', '%d', '%d']
                );
                $new_child_id = $wpdb->insert_id;
                $updated_response_data[] = ['id' => $new_child_id, 'text' => $new_option];
            }
        }

        // Remove excess children if fewer options are provided
        if ($new_children_count < count($existing_children_ids)) {
            $ids_to_remove = array_slice($existing_children_ids, $new_children_count);
            foreach ($ids_to_remove as $child_id) {
                $wpdb->delete($table_name, ['id' => $child_id], ['%d']);
            }
        }

        // Encode the updated response_data
        $response_data = json_encode($updated_response_data);
    } elseif ($response_type === 'redirect' && isset($_POST['edit_redirect_link'])) {
        $response_data = sanitize_text_field($_POST['edit_redirect_link']);
    }

    $result = $wpdb->update(
        $table_name,
        [
            'question' => $question,
            'response_type' => $response_type,
            'response_data' => $response_data,
        ],
        ['id' => $id],
        ['%s', '%s', '%s'],
        ['%d']
    );

//     if ($result === false) {
//         echo '<div class="notice notice-error"><p>Failed to update question.</p></div>';
//     } else {
//         echo '<div class="notice notice-success"><p>Question and child options updated successfully.</p></div>';
//     }
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



function save_email_settings() {
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'customQY_email_settings';

        $enable_email = isset($_POST['enable_email']) ? 1 : 0;
        $email = sanitize_email($_POST['email']);
        $subject = sanitize_text_field($_POST['subject']);
        $email_heading = sanitize_text_field($_POST['email_heading']);

        // Check if an entry already exists
        $existing_settings = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");

        if ($existing_settings) {
            $wpdb->update($table_name, [
                'is_enabled' => $enable_email,
                'email' => $email,
                'subject' => $subject,
                'email_heading' => $email_heading,
            ], ['id' => $existing_settings->id]);
        } else {
            $wpdb->insert($table_name, [
                'is_enabled' => $enable_email,
                'email' => $email,
                'subject' => $subject,
                'email_heading' => $email_heading,
            ]);
        }
    
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customQY-email_form_submit']))  {
    save_email_settings();
}



if (isset($_POST['export_csv'])) {
    global $wpdb;
    $table_name_leads = $wpdb->prefix . 'customQY_user_inputs';
    // Fetch data from the database
    $results = $wpdb->get_results("SELECT user_statement, user_name, user_email, user_phone, selected_options, created_at FROM $table_name_leads ORDER BY created_at DESC", ARRAY_A);

    // Define the filename
    $filename = 'leads_' . date('Ymd') . '.csv';

    // Set headers for file download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $filename . '"');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Add the column headings
    fputcsv($output, ['User Question', 'User Name', 'User Email', 'User Phone', 'Selected Options', 'Date']);

    // Add data rows
    foreach ($results as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

if (isset($_POST['clear_all_leads'])) {
    global $wpdb;

    // Delete all rows from the wp_customQY_user_inputs table
    $wpdb->query("DELETE FROM wp_customQY_user_inputs");
}




// Activation hook to create the database table
register_activation_hook(__FILE__, 'create_chatbot_tables');
