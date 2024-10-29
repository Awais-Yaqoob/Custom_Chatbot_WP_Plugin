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
        plugins_url('assets/index-D02pozkG.js', __FILE__), 
        array(),
        null,
        true
    );
    wp_enqueue_style(
        'react-chatbot-style',
        plugins_url('assets/index-UyhBv1Or.css', __FILE__)
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
   // Handle form submission
if (isset($_POST['submit'])) {
    $question = sanitize_text_field($_POST['question']);
    $response_type = sanitize_text_field($_POST['response_type']);
    $options = isset($_POST['options']) ? array_map('sanitize_text_field', $_POST['options']) : [];

    // Get the parent IDs from the JSON-encoded hidden input and decode them into an array
    $parent_ids = isset($_POST['parent_id']) ? json_decode(stripslashes($_POST['parent_id']), true) : [];
    $parent_ids_serialized = !empty($parent_ids) ? json_encode($parent_ids) : null;

    // Debug: Check JSON encoding of parent_ids_serialized
    error_log('Parent IDs Serialized: ' . print_r($parent_ids_serialized, true));

    // Insert the main question into the database
    $wpdb->insert($table_name, [
        'question' => $question,
        'response_type' => $response_type,
        'parent_id' => $parent_ids_serialized, // Store as JSON
        'is_option' => 0, // Indicates this is a question, not an option
    ]);
    $question_id = $wpdb->insert_id;

    // Process each option
    $option_data = [];
    foreach ($options as $option) {
        $wpdb->insert($table_name, [
            'question' => $option,
            'parent_id' => $question_id,
            'is_option' => 1,
        ]);
        $option_id = $wpdb->insert_id;
        $option_data[] = ['id' => $option_id, 'text' => $option];
    }

    // Update the parent question with the response_data containing all options
    $wpdb->update($table_name, [
        'response_data' => json_encode($option_data) 
    ], ['id' => $question_id]);
}


    // Handle delete request
    if (isset($_GET['delete'])) {
        $id_to_delete = intval($_GET['delete']);
        $wpdb->delete($table_name, ['id' => $id_to_delete]);
        echo '<script>window.location.href="' . admin_url('admin.php?page=chatbot-qa') . '"</script>';
    }

    // Fetch all options for parent selection
    $options_records = $wpdb->get_results("SELECT id, question FROM $table_name WHERE is_option = 1");

    ?>
    <div class="wrap">
        <h1>Manage Chatbot Q&A</h1>
        <form method="post">
            <label for="question">Question</label>
            <input type="text" name="question" required>

            <label for="response_type">Response Type</label>
            <select name="response_type" id="response_type" required onchange="toggleOptionsFields()">
                <option value="options">Options</option>
                <option value="message">Message</option>
                <option value="redirect">Redirect</option>
                <option value="popup">Popup</option>
            </select>

            <!-- Dynamic Options Fields -->
            <div id="options-container">
                <label>Options</label>
                <div class="option-field">
                    <input type="text" name="options[]" placeholder="Option 1">
                </div>
                <button type="button" onclick="addOptionField()">Add More Option</button>
            </div>

         <label for="parent_id">Select Parent Options</label>
<div id="parent-options-container">
    <input type="text" id="parent-search" placeholder="Type to search options...">
    <div id="options-list" style="border: 1px solid #ccc; max-height: 150px; overflow-y: auto; display: none;">
        <?php foreach ($options_records as $record): ?>
            <div class="option-item" data-id="<?php echo esc_attr($record->id); ?>">
                <?php echo esc_html($record->question); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div id="selected-parents" style="margin-top: 10px;"></div>
    <input type="hidden" name="parent_id" id="parent_id">
</div>


            <input type="submit" name="submit" value="Add Q&A">
        </form>

        <h2>Existing Q&As</h2>
        <ul>
            <?php
            // Fetch all questions and options for display
            $qa_records = $wpdb->get_results("SELECT * FROM $table_name");
            foreach ($qa_records as $record): ?>
                <li>
                    <strong><?php echo esc_html($record->question); ?>:</strong> 
                    <?php echo esc_html($record->response_data); ?> 
                    (Type: <?php echo esc_html($record->response_type); ?>)
                    <a href="?page=chatbot-qa&delete=<?php echo $record->id; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <script>
        // Toggle options container based on response type
        function toggleOptionsFields() {
            var responseType = document.getElementById("response_type").value;
            var optionsContainer = document.getElementById("options-container");
            optionsContainer.style.display = responseType === 'options' ? 'block' : 'none';
        }
        
        // Function to add more option fields
        function addOptionField() {
            var container = document.getElementById("options-container");
            var newField = document.createElement("div");
            newField.classList.add("option-field");
            newField.innerHTML = '<input type="text" name="options[]" placeholder="New Option">';
            container.appendChild(newField);
        }

        // Initialize visibility of options fields
        toggleOptionsFields();
    </script>





<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('parent-search');
    const optionsList = document.getElementById('options-list');
    const selectedParentsContainer = document.getElementById('selected-parents');
    const parentIdField = document.getElementById('parent_id');

    let selectedOptions = [];

    // Show options matching the search input
    searchInput.addEventListener('input', function() {
        const query = searchInput.value.toLowerCase();
        const options = document.querySelectorAll('.option-item');
        optionsList.style.display = 'block';

        options.forEach(option => {
            if (option.textContent.toLowerCase().includes(query)) {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });
    });

    // Add an option to selected parents when clicked
    document.querySelectorAll('.option-item').forEach(option => {
        option.addEventListener('click', function() {
            const id = option.getAttribute('data-id');
            const text = option.textContent;

            if (!selectedOptions.some(opt => opt.id === id)) {
                selectedOptions.push({ id, text });
                renderSelectedOptions();
                updateParentIdField();
            }

            searchInput.value = '';
            optionsList.style.display = 'none';
        });
    });

    // Remove an option from selected parents when clicked
    selectedParentsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-option')) {
            const id = e.target.getAttribute('data-id');
            selectedOptions = selectedOptions.filter(opt => opt.id !== id);
            renderSelectedOptions();
            updateParentIdField();
        }
    });

    // Render selected options
    function renderSelectedOptions() {
        selectedParentsContainer.innerHTML = selectedOptions.map(opt =>
            `<span>${opt.text} <button type="button" class="remove-option" data-id="${opt.id}">x</button></span>`
        ).join(' ');
    }

    // Update the hidden field with selected parent IDs as a JSON string
    function updateParentIdField() {
        parentIdField.value = JSON.stringify(selectedOptions.map(opt => opt.id));
    }
});
</script>



    <?php
}






// Activation hook to create the database table
register_activation_hook(__FILE__, 'create_chatbot_flow_table');
