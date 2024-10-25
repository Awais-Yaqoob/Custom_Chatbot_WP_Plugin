<?php
/*
Plugin Name: Custom Chatbot for Q&A
Description: A plugin to manage chatbot questions and answers.
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
        plugins_url('assets/index-D02pozkG.js', __FILE__), // Adjust the path based on your build
        array(),
        null,
        true
    );
    wp_enqueue_style(
        'react-chatbot-style',
        plugins_url('assets/index-UyhBv1Or.css', __FILE__) // Adjust the path based on your build
    );
}
add_action('wp_enqueue_scripts', 'react_chatbot_enqueue_scripts');

// Shortcode to render the chatbot
function react_chatbot_render() {
    return '<div class="awais-chatbot-container"><div id="root"></div></div>';
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
    $table_name = $wpdb->prefix . 'chatbot_flow';

    // Handle form submission
    if (isset($_POST['submit'])) {
        $question = sanitize_text_field($_POST['question']);
        $response_type = sanitize_text_field($_POST['response_type']);
        $response_data = sanitize_textarea_field($_POST['response_data']);
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

        // Insert question into the database
        $wpdb->insert($table_name, [
            'question' => $question,
            'response_type' => $response_type,
            'response_data' => $response_data,
            'parent_id' => $parent_id,
        ]);
    }

    // Handle delete request
    if (isset($_GET['delete'])) {
        $id_to_delete = intval($_GET['delete']);
        $wpdb->delete($table_name, ['id' => $id_to_delete]);
        echo '<script>window.location.href="' . admin_url('admin.php?page=chatbot-qa') . '"</script>';
    }

    // Fetch existing questions
    $qa_records = $wpdb->get_results("SELECT * FROM $table_name");
    ?>
    <div class="wrap">
        <h1>Manage Chatbot Q&A</h1>
        <form method="post">
            <label for="question">Question</label>
            <input type="text" name="question" required>
            
            <label for="response_type">Response Type</label>
            <select name="response_type" required>
                <option value="options">Options</option>
                <option value="message">Message</option>
                <option value="redirect">Redirect</option>
                <option value="popup">Popup</option>
            </select>

            <label for="response_data">Response Data (JSON)</label>
            <textarea name="response_data" placeholder='e.g. ["Need Help", "Verification Insurance"]'></textarea>
            
            <label for="parent_id">Parent ID (for follow-up questions)</label>
            <input type="number" name="parent_id" placeholder="Optional">

            <input type="submit" name="submit" value="Add Q&A">
        </form>

        <h2>Existing Q&As</h2>
        <ul>
            <?php foreach ($qa_records as $record): ?>
                <li>
                    <strong><?php echo esc_html($record->question); ?>:</strong> 
                    <?php echo esc_html($record->response_data); ?> 
                    (Type: <?php echo esc_html($record->response_type); ?>)
                    <a href="?page=chatbot-qa&delete=<?php echo $record->id; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}
