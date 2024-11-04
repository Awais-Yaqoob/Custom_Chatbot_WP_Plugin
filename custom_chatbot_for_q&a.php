<?php
/*
Plugin Name: Custom Chatbot for Q&A
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
        plugins_url('assets/index-BvubrvaF.js', __FILE__), 
        array(),
        null,
        true
    );
    wp_enqueue_style(
        'react-chatbot-style',
        plugins_url('assets/index-Cr6jIwaJ.css', __FILE__)
    );
}
add_action('wp_enqueue_scripts', 'react_chatbot_enqueue_scripts', 20);

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
    $table_name = $wpdb->prefix . 'chatbot_flow';

    // Handle form submission
//    // Handle form submission
// if (isset($_POST['submit'])) {
//     $question = sanitize_text_field($_POST['question']);
//     $response_type = sanitize_text_field($_POST['response_type']);
//     $options = isset($_POST['options']) ? array_map('sanitize_text_field', $_POST['options']) : [];

//     // Get the parent IDs from the JSON-encoded hidden input and decode them into an array
//     $parent_ids = isset($_POST['parent_id']) ? json_decode(stripslashes($_POST['parent_id']), true) : [];
//     $parent_ids_serialized = !empty($parent_ids) ? json_encode($parent_ids) : null;

//     // Debug: Check JSON encoding of parent_ids_serialized
//     error_log('Parent IDs Serialized: ' . print_r($parent_ids_serialized, true));

//     // Insert the main question into the database
//     $wpdb->insert($table_name, [
//         'question' => $question,
//         'response_type' => $response_type,
//         'parent_id' => $parent_ids_serialized, // Store as JSON
//         'is_option' => 0, // Indicates this is a question, not an option
//     ]);
//     $question_id = $wpdb->insert_id;

//     // Process each option
//     $option_data = [];
//     foreach ($options as $option) {
//         $wpdb->insert($table_name, [
//             'question' => $option,
//             'parent_id' => $question_id,
//             'is_option' => 1,
//         ]);
//         $option_id = $wpdb->insert_id;
//         $option_data[] = ['id' => $option_id, 'text' => $option];
//     }

//     // Update the parent question with the response_data containing all options
//     $wpdb->update($table_name, [
//         'response_data' => json_encode($option_data) 
//     ], ['id' => $question_id]);
// }


	if (isset($_POST['submit'])) {
    $question = sanitize_text_field($_POST['question']);
    $response_type = sanitize_text_field($_POST['response_type']);
    $parent_ids = isset($_POST['parent_id']) ? json_decode(stripslashes($_POST['parent_id']), true) : [];
    $parent_ids_serialized = !empty($parent_ids) ? json_encode($parent_ids) : null;
    $response_data = '';

    // Check if response_type is "redirect" and save the link in response_data
    if ($response_type === 'redirect' && isset($_POST['redirect_link'])) {
        $response_data = sanitize_text_field($_POST['redirect_link']);
    } elseif ($response_type === 'options') {
        // If it's "options," gather the options data
        $options = isset($_POST['options']) ? array_map('sanitize_text_field', $_POST['options']) : [];
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

        $response_data = json_encode($option_data);
    }

    // Insert the question into the database
    $wpdb->insert($table_name, [
        'question' => $question,
        'response_type' => $response_type,
        'parent_id' => $parent_ids_serialized,
        'response_data' => $response_data,
        'is_option' => 0,
    ]);
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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

<div class="container my-5 d-flex">
	<div class="col-md-6">
    <h1 class="mb-4">Manage Chatbot Q&A</h1>
    <form method="post" class="border p-4 bg-light rounded">
        <div class="form-group">
            <label for="question">Question</label>
            <input type="text" name="question" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="response_type">Response Type</label>
            <select name="response_type" id="response_type" class="form-control" required onchange="toggleOptionsFields()">
                <option value="options">Options</option>
                <option value="redirect">Redirect</option>
            </select>
        </div>

        <!-- Dynamic Options Fields -->
        <div id="options-container">
    <label>Options</label>
    <div id="options-wrapper">
        <div class="option-field mb-2">
            <input type="text" name="options[]" class="form-control" placeholder="Option 1">
        </div>
    </div>
    <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addOptionField()">Add More Option</button>
</div>

        <!-- Text Area for 'Redirect' Type -->
        <div id="redirect-container" class="form-group" style="display: none;">
            <label>Redirect Link</label>
            <textarea name="redirect_link" class="form-control" placeholder="Enter redirect URL"></textarea>
        </div>

        <!-- Parent Options Multi-Select -->
        <div class="form-group">
            <label for="parent_id">Select Parent Options</label>
            <div id="parent-options-container">
                <input type="text" id="parent-search" class="form-control mb-2" placeholder="Type to search options...">
                <div id="options-list" class="border rounded p-2 bg-white" style="max-height: 150px; overflow-y: auto; display: none;">
                    <?php foreach ($options_records as $record): ?>
                        <div class="option-item" data-id="<?php echo esc_attr($record->id); ?>">
                            <?php echo esc_html($record->question); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="selected-parents" class="mt-2"></div>
                <input type="hidden" name="parent_id" id="parent_id">
            </div>
        </div>

        <button type="submit" name="submit" class="btn btn-primary">Add Q&A</button>
    </form>
</div>
	
	<div class="col-md-6">
    <h3 class="mb-4">Existing Questions</h3>
    <ul class="list-group">
    <?php
    // Fetch all questions and options for display
    $qa_records = $wpdb->get_results("SELECT * FROM $table_name WHERE is_option = 0");
    foreach ($qa_records as $record): ?>
       <li class="list-group-item d-flex justify-content-between align-items-center p-2">
    <span class="flex-grow-1 mr-2">
      <h6><?php echo esc_html($record->question); ?></h6>

    </span>
    <div class="button-group ml-auto" style="width: 120px; display: flex;
    justify-content: end; gap:10px;">
        <button type="button" class="btn btn-warning btn-sm" onclick="editQuestion(<?php echo $record->id; ?>)">Edit</button>
        <a href="?page=chatbot-qa&delete=<?php echo $record->id; ?>" onclick="return confirm('Are you sure?');" class="btn btn-danger btn-sm">Delete</a>
    </div>
</li>

    <?php endforeach; ?>
</ul>
</div>
	
	
	<!-- Overlay Background -->
<div id="overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 99;">
</div>
<!-- Edit Form Container -->
<div id="edit-form-container" class="mt-4" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 100; background-color: white; padding: 30px; width: 50%; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Edit Question</h3>
        <!-- Close Button -->
        <span onclick="closeEditForm()" style="cursor: pointer; font-size: 20px; font-weight: bold;">&times;</span>
    </div>
    <form method="post">
        <input type="hidden" name="edit_id" id="edit_id">
        <div class="form-group">
            <label for="edit_question">Question</label>
            <input type="text" name="edit_question" id="edit_question" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="edit_response_type">Response Type</label>
            <select name="edit_response_type" id="edit_response_type" class="form-control" onchange="toggleEditOptionsFields()" required>
                <option value="options">Options</option>
                <option value="redirect">Redirect</option>
            </select>
        </div>

        <div id="edit-options-container" class="form-group">
            <label>Options</label>
            <div id="edit-options-wrapper"></div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addEditOptionField()">Add More Option</button>
        </div>

        <div id="edit-redirect-container" class="form-group" style="display: none;">
            <label>Redirect Link</label>
            <textarea name="edit_redirect_link" id="edit_redirect_link" class="form-control" placeholder="Enter redirect URL"></textarea>
        </div>

        <button type="submit" name="update_question" class="btn btn-primary">Update Question</button>
    </form>
</div>


	<style>
#overlay {
    transition: opacity 0.3s ease;
}

#edit-form-container span {
    color: #333;
}

#edit-form-container span:hover {
    color: #ff0000;
    cursor: pointer;
}
</style>

	
	
	
	
</div>


   <script>
    function toggleOptionsFields() {
        var responseType = document.getElementById("response_type").value;
        var optionsContainer = document.getElementById("options-container");
        var redirectContainer = document.getElementById("redirect-container");

        optionsContainer.style.display = responseType === 'options' ? 'block' : 'none';
        redirectContainer.style.display = responseType === 'redirect' ? 'block' : 'none';
    }
    
function addOptionField() {
    var wrapper = document.getElementById("options-wrapper");
    var newField = document.createElement("div");
    newField.classList.add("option-field", "mb-2");
    newField.innerHTML = '<input type="text" name="options[]" class="form-control" placeholder="New Option">';
    wrapper.appendChild(newField); // Add new field to the end of the options wrapper
}



    document.addEventListener('DOMContentLoaded', function() {
        toggleOptionsFields();
        const searchInput = document.getElementById('parent-search');
        const optionsList = document.getElementById('options-list');
        const selectedParentsContainer = document.getElementById('selected-parents');
        const parentIdField = document.getElementById('parent_id');

        let selectedOptions = [];

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

        selectedParentsContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-option')) {
                const id = e.target.getAttribute('data-id');
                selectedOptions = selectedOptions.filter(opt => opt.id !== id);
                renderSelectedOptions();
                updateParentIdField();
            }
        });

        function renderSelectedOptions() {
            selectedParentsContainer.innerHTML = selectedOptions.map(opt =>
                `<span class="badge badge-primary mr-2">${opt.text} <button type="button" class="remove-option btn btn-sm btn-light ml-1" data-id="${opt.id}">x</button></span>`
            ).join(' ');
        }

        function updateParentIdField() {
            parentIdField.value = JSON.stringify(selectedOptions.map(opt => opt.id));
        }
    });
	   
	   
	   
	   
	document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.edit-btn');
        
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = button.getAttribute('data-id');
                editQuestion(id); // Call edit function with the question ID
            });
        });
    });

    function editQuestion(id) {
        <?php
        // Pass records to JavaScript
        $js_records = json_encode($qa_records);
        echo "const records = $js_records;";
        ?>

        // Find the question record by id
        const record = records.find(r => r.id == id);

        if (record) {
            // Populate the edit form
            document.getElementById("edit_id").value = record.id;
            document.getElementById("edit_question").value = record.question;
            document.getElementById("edit_response_type").value = record.response_type;

            // Show options or redirect link based on response type
            if (record.response_type === 'options') {
                document.getElementById("edit-options-container").style.display = 'block';
                document.getElementById("edit-redirect-container").style.display = 'none';
                
                const optionsWrapper = document.getElementById("edit-options-wrapper");
                optionsWrapper.innerHTML = '';
                const options = JSON.parse(record.response_data);
                
                options.forEach(opt => {
                    const optionField = document.createElement("div");
                    optionField.classList.add("mb-2");
                    optionField.innerHTML = `<input type="text" name="edit_options[]" class="form-control" value="${opt.text}">`;
                    optionsWrapper.appendChild(optionField);
                });
            } else if (record.response_type === 'redirect') {
                document.getElementById("edit-options-container").style.display = 'none';
                document.getElementById("edit-redirect-container").style.display = 'block';
                document.getElementById("edit_redirect_link").value = record.response_data;
            }

            // Show the edit form
            document.getElementById("edit-form-container").style.display = 'block';
			 document.getElementById("overlay").style.display = "block";
        } else {
            console.error('Record not found for editing');
        }
    }


	   
	   

function closeEditForm() {
    document.getElementById("overlay").style.display = "none";
    document.getElementById("edit-form-container").style.display = "none";
}
	   
</script>


    <?php
}



if (isset($_POST['update_question'])) {
    global $wpdb; // Ensure $wpdb is accessible
    $table_name = $wpdb->prefix . 'chatbot_flow'; // Define table name

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





// Activation hook to create the database table
register_activation_hook(__FILE__, 'create_chatbot_flow_table');
