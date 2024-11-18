<?php
function get_appearance_settings() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'customQY_appearance_settings';
    return $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");
}

$appearance_settings = get_appearance_settings();
$available_fonts = include plugin_dir_path(__FILE__) . 'customQY-fonts.php';

?>



<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

<div class="container my-5 d-flex" style="min-width: 100%;">
    <!-- Sidebar with Tabs -->
    <div class="col-md-3" >
        <div class="border p-4 rounded" style="min-height: 700px; height:100%; background-color: black;" >
            <h3 style="color: white;">Settings</h3>
            <ul class="nav nav-pills flex-column gap-3 pt-4" id="sidebar-tabs">
                <li class="nav-item">
                    <a class="nav-link active" style="color: white; background-color:inherit; font-size:20px; font-weight:600;" href="#add-new-tab" data-toggle="tab">Add New</a>
                </li>
                <li class="nav-item" >
                    <a class="nav-link" style="color: white; font-size:20px; font-weight:600; margin-top:10px;" href="#existing-questions-tab" data-toggle="tab">Existing Questions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" style="color: white; font-size:20px; font-weight:600; margin-top:10px;" href="#appearance-tab" data-toggle="tab">Appearance</a>
                </li>
				 <li class="nav-item">
                    <a class="nav-link" style="color: white; font-size:20px; font-weight:600; margin-top:10px;" href="#leads-tab" data-toggle="tab">Leads</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="col-md-9">
        <div class="tab-content">
            <!-- Add New Tab -->
            <div class="tab-pane fade show active" id="add-new-tab">
				<div class="border p-4 rounded mb-4" style="background-color: black;">
					 <h4 style="color: white;">Add New Question</h4>
				</div>
                <form method="post" class="border p-4 rounded">
                    <div class="form-group customQy-dashboard-form">
                        <label for="question">Question</label>
                        <input type="text" name="question" class="form-control" placeholder="How may I help you?" required>
                    </div>
                    <div class="form-group customQy-dashboard-form">
                        <label for="response_type">Response Type</label>
                        <select name="response_type" id="response_type" class="form-control customQy-dashboard-form" required onchange="toggleOptionsFields()">
                            <option value="options">Options</option>
                            <option value="redirect">Redirect</option>
							<option value="user-input">User Input</option>
                        </select>
                    </div>

                    <div id="options-container" class="customQy-dashboard-form">
                        <label>Options</label>
                        <div id="options-wrapper">
                            <div class="option-field mb-2 d-flex customQy-dashboard-form">
                                <input type="text" name="options[]" class="form-control" placeholder="Option">
                                
                            </div>
                        </div>
<!-- 						<button type="button" class="btn btn-danger btn-sm ml-2" onclick="this.parentNode.remove()">x</button> -->
                        <button type="button" class="btn btn-secondary btn-sm mb-2" onclick="addOptionField()">Add Option</button>
                    </div>

                    <div id="redirect-container" class="form-group customQy-dashboard-form" style="display: none;">
                        <label>Redirect Link</label>
                        <textarea name="redirect_link" class="form-control" placeholder="Enter redirect URL"></textarea>
                    </div>
					<div id="input-type-selector" class="form-group customQy-dashboard-form" style="display: none;">
                        <label>Select input type</label>
                        <select name="input-type" class="form-control" required>
							<option value="user-question">User Statement/Question</option>
							 <option value="user-name">User Name</option>
                            <option value="user-phone">User Phone</option>
							<option value="user-email">User Email</option>
						</select>
                    </div>

                    <div class="form-group customQy-dashboard-form">
                        <label for="parent_id">Select Parent Options</label>
                        <div id="parent-options-container">
                            <input type="text" id="parent-search" class="form-control mb-2" placeholder="Type to search options...">
                            <div id="options-list" class="border rounded p-2 bg-white" style="max-height: 150px; overflow-y: auto; display: none;">
                                <?php $has_null_parent = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE parent_id IS NULL");
                                $parent_disabled_class = $has_null_parent > 0 ? 'disabled-option' : '';
                                $parent_disabled_text = $has_null_parent > 0 ? ' (Already a parent)' : ''; ?>
                                <div class="option-item <?php echo esc_attr($parent_disabled_class); ?>" data-id="null" data-parent="true" <?php echo $has_null_parent > 0 ? 'data-disabled="true"' : ''; ?>>Parent Question<?php echo $parent_disabled_text; ?></div>

                                <?php foreach ($options_records as $record):
                                    $is_parent = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE parent_id LIKE %s AND is_option = 0", '%"'.esc_sql($record->id).'"%'));
                                    $disabled_class = $is_parent > 0 ? 'disabled-option' : '';
                                    $disabled_text = $is_parent > 0 ? ' (Already a parent)' : ''; ?>
                                    <div class="option-item <?php echo esc_attr($disabled_class); ?>" data-id="<?php echo esc_attr($record->id); ?>" <?php echo $is_parent > 0 ? 'data-disabled="true"' : ''; ?>>
                                        <?php echo esc_html($record->id) . ' : ' . esc_html($record->question) . $disabled_text; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="selected-parents" class="mt-2"></div>
                            <input type="hidden" name="parent_id" id="parent_id">
                        </div>
                    </div>

                    <button type="submit" name="submit" class="btn btn-primary customQy-form-btn">Add Question</button>
                </form>
            </div>

            <!-- Existing Questions Tab -->
            <div class="tab-pane fade" id="existing-questions-tab">
               <div class="border p-4 rounded mb-4" style="background-color: black;">
					 <h4 style="color: white;">Existing Questions</h4>
				</div>
                <ul class="list-group border p-2 rounded" style="max-height: 590px; overflow-y:scroll;">
                    <?php $qa_records = $wpdb->get_results("SELECT * FROM $table_name WHERE is_option = 0");
                    foreach ($qa_records as $record): ?>
                        <li class="list-group-item p-3 border-0" style="border-bottom: 1px solid rgba(0, 0, 0, .3) !important;">
                           		<div class="d-flex justify-content-between align-items-center">
									<h6><?php echo esc_html($record->question); ?></h6>
									<div class="button-group  ml-auto" style="display: flex; justify-content: end; gap:10px;">
                                <button type="button" class="btn btn-warning btn-sm" onclick="editQuestion(<?php echo $record->id; ?>)">Edit</button>
                                <a href="?page=chatbot-qa&delete=<?php echo $record->id; ?>" onclick="return confirm('Are you sure?');" class="btn btn-danger btn-sm">Delete</a>
                            </div>
							</div>
                                
								<div class="row p-2">
									
							
                                <?php if (is_string($record->response_data) && is_array(json_decode($record->response_data, true))): $child_options = json_decode($record->response_data);
                                    foreach ($child_options as $option) {
                                        if (isset($option->id) && isset($option->text)) {
                                            echo '<div class="border rounded bg-light p-2 column m-1" ><span><b>ID:</b> ' . esc_html($option->id) . ', <b>Text:</b> ' . esc_html($option->text) . '</span></div><br>';
                                        } elseif (isset($option->text)) {
                                            echo '<div class="border rounded bg-light p-2 column m-1"><span><b>Text:</b> ' . esc_html($option->text) . '</span></div><br>';
                                        }
                                    }
                                else:
                                    echo '<div class="border rounded bg-light p-2 column m-1"><span><b>URL:</b> ' . esc_html($record->response_data) . '</span></div><br>';
                                endif; ?>
                            
									</div>
                            
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
			
			
				<!-- Overlay Background -->
<div id="customQY-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 99;">
</div>
<!-- Edit Form Container -->
<div id="customQY-edit-form-container" class="mt-4" style="display: none; position: fixed; top: 50%; left: 60%; transform: translate(-50%, -50%); z-index: 100; background-color: white; padding: 30px; width: 50%; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Edit Question</h3>
        <!-- Close Button -->
        <span onclick="closeEditForm()" style="cursor: pointer; font-size: 20px; font-weight: bold;">&times;</span>
    </div>
    <form method="post">
        <input type="hidden" name="edit_id" id="edit_id">
        <div class="form-group customQy-dashboard-form">
            <label for="edit_question">Question</label>
            <input type="text" name="edit_question" id="edit_question" class="form-control" required>
        </div>

        <div class="form-group customQy-dashboard-form">
            <label for="edit_response_type">Response Type</label>
            <select name="edit_response_type" id="edit_response_type" class="form-control" onchange="toggleEditOptionsFields()" required>
                <option value="options">Options</option>
                <option value="redirect">Redirect</option>
				<option value="user-input">User Input</option>
            </select>
        </div>
		
		

        <div id="edit-options-container" class="form-group customQy-dashboard-form">
            <label>Options</label>
            <div id="edit-options-wrapper"></div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addEditOptionField()">Add More Option</button>
        </div>

        <div id="edit-redirect-container" class="form-group customQy-dashboard-form" style="display: none;">
            <label>Redirect Link</label>
            <textarea name="edit_redirect_link" id="edit_redirect_link" class="form-control" placeholder="Enter redirect URL"></textarea>
        </div>

        <button type="submit" name="update_question" class="btn btn-primary customQy-form-btn">Update Question</button>
    </form>
</div>

            <!-- Appearance Tab -->
          <div class="tab-pane fade" id="appearance-tab">
    <div class="border p-4 rounded mb-4" style="background-color: black;">
        <h4 style="color: white;">Set Appearance</h4>
    </div>
    <form id="appearance-form" method="post" class="border p-4 rounded">
        <input type="hidden" name="appearance_form_submit" value="1">

        <!-- Title Field -->
        <div class="form-group customQy-dashboard-form">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" class="form-control" placeholder="Site Name"
                   value="<?php echo esc_attr($appearance_settings->title ?? ''); ?>">
        </div>

        <!-- Logo Field -->
        <div class="form-group customQy-dashboard-form">
            <label for="logo">Logo</label>
            <input type="text" id="logo" name="logo" class="form-control" hidden readonly
                   value="<?php echo esc_attr($appearance_settings->logo_url ?? ''); ?>"><br>
            <button id="logo-picker-btn" type="button" class="button">Select Logo</button>
            <div id="logo-preview" style="margin-top: 10px;">
                <?php if (!empty($appearance_settings->logo_url)) : ?>
                    <img src="<?php echo esc_url($appearance_settings->logo_url); ?>" alt="Logo Preview"
                         style="max-height: 80px;">
                <?php endif; ?>
            </div>
        </div>

        <!-- Font Selection Field -->
       <div class="form-group customQy-dashboard-form">
    <label for="font">Font Selection</label>
    <select id="font" name="font" class="form-control">
        <?php
        foreach ($available_fonts as $css_font => $display_font) {
            $selected = ($appearance_settings->font ?? '') === $css_font ? 'selected' : '';
            echo "<option value='" . esc_attr($css_font) . "' $selected>$display_font</option>";
        }
        ?>
    </select>
</div>

        <!-- Primary Color Field -->
        <div class="form-group customQy-dashboard-form">
            <label for="primary-color">Primary Color</label><br>
            <input type="text" id="primary-color" name="primary-color" class="form-control"
                   value="<?php echo esc_attr($appearance_settings->primary_color ?? ''); ?>">
        </div>

        <!-- Secondary Color Field -->
        <div class="form-group customQy-dashboard-form">
            <label for="secondary-color">Secondary Color</label><br>
            <input type="text" id="secondary-color" name="secondary-color" class="form-control"
                   value="<?php echo esc_attr($appearance_settings->secondary_color ?? ''); ?>">
        </div>

        <!-- Logo Size Field -->
        <div class="form-group customQy-dashboard-form">
            <label for="logo-size">Logo Size</label><br>
            <input type="number" id="logo-size" name="logo-size" class="form-control" placeholder="e.g., 50"
                   value="<?php echo esc_attr($appearance_settings->logo_size ?? ''); ?>">
        </div>
		
		
		
		 <!-- Auto Collapse Toggle Field -->
		<div class="form-group customQy-dashboard-form">
		<label class="switch customQy-dashboard-switch">
  <input type="checkbox"id="auto-collapse" name="auto-collapse" 
                   <?php echo isset($appearance_settings->auto_collapse) && $appearance_settings->auto_collapse ? 'checked' : ''; ?>>
  <span class="slider round customQy-switch-slider"></span>
</label>
		</div>	
		
		

        <!-- Collapse Delay Field (only shown if auto-collapse is checked) -->
        <div class="form-group customQy-dashboard-form" id="collapse-delay-container" style="display: none;">
            <label for="collapse-delay">Collapse Delay (ms)</label>
            <input type="number" id="collapse-delay" name="collapse-delay" class="form-control"
                   placeholder="e.g., 5000" 
                   value="<?php echo esc_attr($appearance_settings->collapse_delay ?? ''); ?>">
        </div>
		

        <!-- Save Button -->
        <button type="submit" class="btn btn-primary customQy-form-btn">Save Appearance Settings</button>
    </form>
</div>
			
			
			
			 <!-- Leads Tab -->
           <div class="tab-pane fade" id="leads-tab">
    <div class="border p-4 rounded mb-4 d-flex justify-content-between " style="background-color: black;">
        <h4 style="color: white;">Leads</h4>
		 <form method="POST" action="">
            <button type="submit" name="export_csv" class="btn btn-success">Export to CSV</button>
        </form>
    </div>
    <div class="border p-4 rounded" style="max-height: 590px; overflow-y: scroll;">
        <?php 
        global $wpdb;
        $leads_records = $wpdb->get_results("SELECT * FROM wp_customQY_user_inputs ORDER BY created_at DESC");
        if ($leads_records && count($leads_records) > 0): ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>User Question</th>
                        <th>User Name</th>
                        <th>User Email</th>
                        <th>User Phone</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads_records as $record): ?>
                        <tr>
                            <td><?php echo esc_html($record->user_statement); ?></td>
                            <td><?php echo esc_html($record->user_name); ?></td>
                            <td><?php echo esc_html($record->user_email); ?></td>
                            <td><?php echo esc_html($record->user_phone); ?></td>
                            <td><?php echo esc_html($record->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-white">No leads found.</p>
        <?php endif; ?>
    </div>
</div>


        </div>
    </div>
	
	
	
	
	<style>
		
		#customQY-overlay {
    transition: opacity 0.3s ease;
}

#customQY-edit-form-container span {
    color: #333;
}

#customQY-edit-form-container span:hover {
    color: #ff0000;
    cursor: pointer;
}
		
	.disabled-option {
    pointer-events: none;
    color: #ccc;
}
		.customQy-dashboard-form label{
			font-weight:600;
		}
		.customQy-dashboard-form input{
			border: 1px solid #ccc;
			width: 100%;
		}
		.customQy-dashboard-form select{
			border: 1px solid #ccc;
			width: 100%;
		}
		.customQy-form-btn, .customQy-form-btn:hover{
			background-color:black;
			border:none;
		}
		.custom-QY-active-tab-style{
			color: black !important;
    		background-color: white !important;
    		font-weight: 600;
			border:none;
		}
		a:focus{
			border:none;
			box-shadow:none;
		}
	
		.customQy-form-btn:focus{
			background-color:black;
			border:none;
			box-shadow:none;
		}
		.customQy-form-btn:not(:disabled):not(.disabled).active, .customQy-form-btn:not(:disabled):not(.disabled):active, .show>.customQy-form-btn.dropdown-toggle{
			background-color:black;
			border:none;
			box-shadow:none;
		}
		
		/* The switch - the box around the slider */
.customQy-dashboard-switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 34px;
}

/* Hide default HTML checkbox */
.customQy-dashboard-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

/* The slider */
.customQy-switch-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  -webkit-transition: .4s;
  transition: .4s;
}

.customQy-switch-slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}

input:checked + .customQy-switch-slider {
  background-color: #2196F3;
}

input:focus + .customQy-switch-slider {
  box-shadow: 0 0 1px #2196F3;
}

input:checked + .customQy-switch-slider:before {
  -webkit-transform: translateX(26px);
  -ms-transform: translateX(26px);
  transform: translateX(26px);
}

/* Rounded sliders */
.customQy-switch-slider.round {
  border-radius: 34px;
}

.customQy-switch-slider.round:before {
  border-radius: 50%;
}
		
</style>
</div>





<script>
	   
	  <!-- Tab Switching Script -->
document.addEventListener('DOMContentLoaded', function() {
    // Initially apply active styling to the default active tab
    const defaultActiveTab = document.querySelector('#sidebar-tabs a.active');
    if (defaultActiveTab) {
        defaultActiveTab.classList.add('custom-QY-active-tab-style');
        document.querySelector(defaultActiveTab.getAttribute('href')).classList.add('show', 'active');
    }

    document.querySelectorAll('#sidebar-tabs a').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active styling from all tabs
            document.querySelectorAll('#sidebar-tabs a').forEach(function(otherTab) {
                otherTab.classList.remove('custom-QY-active-tab-style');
            });

            // Hide all tab panes and remove active class
            document.querySelectorAll('.tab-pane').forEach(function(content) {
                content.classList.remove('show', 'active');
            });

            // Show the selected tab pane and add active class
            document.querySelector(tab.getAttribute('href')).classList.add('show', 'active');

            // Apply active styling to the clicked tab
            tab.classList.add('custom-QY-active-tab-style');
        });
    });
});



	   
	   
	   
	   
    function toggleOptionsFields() {
        var responseType = document.getElementById("response_type").value;
        var optionsContainer = document.getElementById("options-container");
        var redirectContainer = document.getElementById("redirect-container");
		var inputTypeContainer = document.getElementById("input-type-selector");

        optionsContainer.style.display = responseType === 'options' ? 'block' : 'none';
        redirectContainer.style.display = responseType === 'redirect' ? 'block' : 'none';
		inputTypeContainer.style.display = responseType === 'user-input' ? 'block' : 'none';
    }
    
function addOptionField() {
    const wrapper = document.getElementById("options-wrapper");
    const newField = document.createElement("div");
    newField.classList.add("option-field", "mb-2", "d-flex");

    newField.innerHTML = `
        <input type="text" name="options[]" class="form-control " placeholder="New Option">
        <button type="button" class="btn btn-danger btn-sm ml-2 remove-option-btn customQy-form-btn">&times;</button>
    `;

    // Append new field to options wrapper
    wrapper.appendChild(newField);

    // Add event listener to remove the new field
    newField.querySelector(".remove-option-btn").addEventListener("click", function() {
        wrapper.removeChild(newField);
    });
}

// On form submission, exclude empty options
document.querySelector('form').addEventListener('submit', function(e) {
    // Remove any option fields with empty input values
    document.querySelectorAll("input[name='options[]']").forEach(input => {
        if (input.value.trim() === "") {
            input.parentElement.remove();
        }
    });
});




   document.addEventListener('DOMContentLoaded', function() {
    // Initial setup
    const searchInput = document.getElementById('parent-search');
    const optionsList = document.getElementById('options-list');
    const selectedParentsContainer = document.getElementById('selected-parents');
    const parentIdField = document.getElementById('parent_id');
    const form = document.querySelector('form');
    let selectedOptions = [];

    // Prevent manual input, enforce selection from dropdown
    searchInput.addEventListener('focus', function() {
        searchInput.blur();  // Disable manual typing
        optionsList.style.display = 'block';  // Show options when focused
    });

    // Search functionality for filtering options
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

    // Add event listeners to option items for selection
document.querySelectorAll('.option-item').forEach(option => {
    if (option.hasAttribute('data-disabled')) return;
	 
	option.style.cursor = 'pointer';
    option.addEventListener('click', function() {
        const id = option.getAttribute('data-id') === "null" ? null : option.getAttribute('data-id');
        const text = option.textContent;
	
        // Add to selected options if not already selected
        if (!selectedOptions.some(opt => opt.id === id)) {
            selectedOptions.push({ id, text });
            renderSelectedOptions();
            updateParentIdField();
        }
	 
        // Clear search input and hide options list after selection
        searchInput.value = '';
        optionsList.style.display = 'none';
    });
});


    // Handle removal of selected options
    selectedParentsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-option')) {
            const id = e.target.getAttribute('data-id');
            selectedOptions = selectedOptions.filter(opt => opt.id !== id);
            renderSelectedOptions();
            updateParentIdField();
        }
    });

    // Render selected options in the container
    function renderSelectedOptions() {
        selectedParentsContainer.innerHTML = selectedOptions.map(opt =>
            `<span class="badge badge-primary mr-2 mt-1">${opt.text} <button type="button" class="remove-option btn btn-sm btn-light ml-1" data-id="${opt.id}">x</button></span>`
        ).join(' ');
    }

    // Update the hidden input field for selected parent IDs
    function updateParentIdField() {
        parentIdField.value = selectedOptions.length ? JSON.stringify(selectedOptions.map(opt => opt.id)) : '';
    }

    // Validate form submission to ensure a valid parent option is selected
    form.addEventListener('submit', function(e) {
        if (!selectedOptions.length) {
            e.preventDefault();
            alert('Please select a valid parent option from the list.');
        }
    });
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
            document.getElementById("customQY-edit-form-container").style.display = 'block';
			 document.getElementById("customQY-overlay").style.display = "block";
        } else {
            console.error('Record not found for editing');
        }
    }


	   
	   

function closeEditForm() {
    document.getElementById("customQY-overlay").style.display = "none";
    document.getElementById("customQY-edit-form-container").style.display = "none";
}
	   



document.addEventListener('DOMContentLoaded', function () {
    const logoPickerBtn = document.getElementById('logo-picker-btn');
    const logoInput = document.getElementById('logo');
    const logoPreview = document.getElementById('logo-preview');

    // Function to open WordPress media uploader
    function openMediaUploader() {
        const mediaUploader = wp.media({
            title: 'Select Logo',
            button: { text: 'Use this logo' },
            multiple: false
        });

        mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            logoInput.value = attachment.url;
            displayLogoPreview(attachment.url);
        });

        mediaUploader.open();
    }

    // Function to display logo preview with a remove icon
    function displayLogoPreview(url) {
        logoPreview.innerHTML = `
            <img src="${url}" alt="Logo Preview" style="max-height: 80px;"/>
            <span id="remove-logo" style="position: absolute; left: 12%; cursor: pointer; font-size: 30px; color: black;">&times;</span>
        `;

        // Add event listener to the remove icon
        document.getElementById('remove-logo').addEventListener('click', function () {
            logoInput.value = ''; // Clear the input field
            logoPreview.innerHTML = ''; // Clear the preview area
        });
    }

    // Open the media uploader on button click
    logoPickerBtn.addEventListener('click', function (e) {
        e.preventDefault();
        openMediaUploader();
    });

    // Initialize WordPress Color Picker for color fields
    jQuery(document).ready(function ($) {
        $('#primary-color, #secondary-color').wpColorPicker();
    });
});


	
	 // JavaScript to handle the show/hide of the collapse delay field
    document.addEventListener('DOMContentLoaded', function () {
        const autoCollapseCheckbox = document.getElementById('auto-collapse');
        const collapseDelayContainer = document.getElementById('collapse-delay-container');

        // Show/hide collapse delay based on the checkbox state
        function toggleCollapseDelay() {
            collapseDelayContainer.style.display = autoCollapseCheckbox.checked ? 'block' : 'none';
        }

        // Initialize visibility on page load
        toggleCollapseDelay();

        // Add event listener to toggle visibility on checkbox change
        autoCollapseCheckbox.addEventListener('change', toggleCollapseDelay);
    });

	
	   
</script>

