<?php

if ( ! defined( 'WPINC' ) ) die;

// value of the option is default (500) or set by user
define('MAX_UPLOAD_SIZE', get_option( Recipe_Settings::SETTING_MAX_IMAGE_SIZE )); 

// just accept images in jpeg or png format
define('TYPE_WHITELIST', serialize(array('image/jpeg', 'image/png')));

class Recipe_Form {

    function __construct() {
		
        add_action('wp_enqueue_scripts', array($this, 'load_assets'));

		// register send_form() function to handle the AJAX request, action called when button pressed
		add_action('wp_ajax_send_form', array($this, 'ajax_send_form')); // for authenticated users
		add_action('wp_ajax_nopriv_send_form', array($this, 'ajax_send_form')); // for unauthenticated users

		add_shortcode('recipe_form', array($this, 'load_recipe_form_shortcode'));
    }

	function load_assets() {

		// register style / script 
		wp_register_style( 'cpt-form-style', plugin_dir_url(__FILE__).'../css/form-style.css', array(), 1, 'all' );
		wp_register_script( 'cpt-form-script', plugin_dir_url(__FILE__).'../js/form-ajax.js', array('jquery'), 1, true );

		// enqueue style / script 
        wp_enqueue_style( 'cpt-form-style' );
        wp_enqueue_script( 'cpt-form-script' );

		// export variables (needed for js)
		wp_localize_script( 
			'cpt-form-script', 
			'recipe_vars', 
			array( 
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'send_form_nonce' => wp_create_nonce( 'send_form_nonce' ) 
			)
		);
    }

	function load_recipe_form_shortcode() {

		wp_get_current_user();

		// check if user is logged in
		if( !is_user_logged_in() ) {
			$content = '<div>' . wp_loginout($_SERVER['REQUEST_URI'], false) . '</div>'; 
	 		$content .= '<h3>' . __('Log in to share a recipe with us!', Recipe_CPT::RECIPE_DOMAIN) . '</h3>'; 
	 		return $content;  
		}
		else {
			// display form if user is logged in
			global $current_user;

			$form =
			'<form id="recipe_form" method="POST" action="">' . 
				wp_nonce_field( plugin_basename( __FILE__ ), "recipe_form_nonce_field", true, false) .

				'<div id="form-heading">' .
				'<h2>' . __('Recipe creation', Recipe_CPT::RECIPE_DOMAIN) . '<h2>'.
				'<h3>' . __('Hello ', Recipe_CPT::RECIPE_DOMAIN) . esc_html($current_user->user_login) . ', teile dein Rezept mit uns!</h2>
				</div>
				
                <br /> <br /> 
	
				<label class="form-label">' . __('Recipe name', Recipe_CPT::RECIPE_DOMAIN) . '
					<span class="form-label-span">*</span>
				</label> 
				<br />
				<input type="text" id="recipe_name" name="recipe_name" class="input-field" required="" 
					placeholder="' . __("e.g. Lamb's lettuce with balsamic vinaigrette", Recipe_CPT::RECIPE_DOMAIN) . '"' .
					// form validation
					'oninvalid="this.setCustomValidity(\'' . __('Please enter a recipe name.', Recipe_CPT::RECIPE_DOMAIN) . '\')" 
					oninput="setCustomValidity(\'\')" /> 
				<br /> <br />

				<label class="form-label" for="info_field">' . __('Additional information', Recipe_CPT::RECIPE_DOMAIN) . '</label> 
				<br />
				<input type="text" id="info_field" name="info_field" placeholder="' . __("e.g. vegan, glutenfree or lactosefree", Recipe_CPT::RECIPE_DOMAIN) . '" class="input-field"/> 
				<br /> <br />

                <label class="form-label" for="difficulty_field">' . __('Difficulty level', Recipe_CPT::RECIPE_DOMAIN) . '</label> 
				<br />
                <select name="difficulty_field" id="difficulty_field">
					<option value="" disabled selected hidden>' . __("Choose a difficulty level", Recipe_CPT::RECIPE_DOMAIN) . '</option> 
                    <option value="verySimple">' . __('very simple', Recipe_CPT::RECIPE_DOMAIN) . '</option>
                    <option value="simple">' . __('simple', Recipe_CPT::RECIPE_DOMAIN) . '</option>
                    <option value="normal">' . __('normal', Recipe_CPT::RECIPE_DOMAIN) . '</option>
                    <option value="difficult">' . __('difficult', Recipe_CPT::RECIPE_DOMAIN) . '</option>
                    <option value="veryDifficult">' . __('very difficult', Recipe_CPT::RECIPE_DOMAIN) . '</option>
                </select>
				<br /> <br />

				<label class="form-label" for="tax_recipe_category">' . __('Recipe Category', Recipe_CPT::RECIPE_DOMAIN) . '</label><br/>' .
				$this->get_recipe_categories_dropdown($_POST['tax_recipe_category']) . 
				'<br /> <br /> 

				<label class="form-label" for="food_image_file">' . __('Select a suitable picture (maximum ', Recipe_CPT::RECIPE_DOMAIN) . MAX_UPLOAD_SIZE . ' KB)
					<span class="form-label-span">*</span>
				</label><br/> 
				<input type="file" size="60" id="food_image_file" name="food_image_file" required="" 
					oninvalid="this.setCustomValidity(\'' . __('Please upload a photo.', Recipe_CPT::RECIPE_DOMAIN) . '\')" 
					oninput="setCustomValidity(\'\')" />
				<br /> <br /> 

				<label class="form-label" for="quantity_field">' . __('Portions', Recipe_CPT::RECIPE_DOMAIN) . '</label> 
				<br />
				<span>' . __('The recipe is designed for', Recipe_CPT::RECIPE_DOMAIN) . '</span> 
				<input type="number" id="quantity_field" name="quantity_field" placeholder="0" min="0" style="width:100px"/>
				<span>' . __('persons / portions.', Recipe_CPT::RECIPE_DOMAIN) . '</span> 
				<br /> <br />

				<label class="form-label" for="ingredients_field">' . __('Ingredients and quantities', Recipe_CPT::RECIPE_DOMAIN) . '
					<span class="form-label-span">*</span>
				</label>
				<br />
				<textarea rows="10" id="ingredients_field" name="ingredients_field" required="" 
					placeholder="' . __('List the ingredients and quantities here!', Recipe_CPT::RECIPE_DOMAIN) . '" 
					oninvalid="this.setCustomValidity(\'' . __('Please enter the ingredients.', Recipe_CPT::RECIPE_DOMAIN) . '\')" 
					oninput="setCustomValidity(\'\')"></textarea> 
				<br /> <br />

				<label class="form-label" for="preparation_field">' . __('Preparation', Recipe_CPT::RECIPE_DOMAIN) . '
					<span class="form-label-span">*</span>
				</label> 
				<br />
				<textarea rows="10" id="preparation_field" name="preparation_field" required=""
					placeholder="' .  __('Describe how to prepare your recipe!', Recipe_CPT::RECIPE_DOMAIN) . '"
					oninvalid="this.setCustomValidity(\''. __('Please enter the preparation.', Recipe_CPT::RECIPE_DOMAIN) . '\')" 
					oninput="setCustomValidity(\'\')"></textarea>
				<br /> <br />

				<input type="hidden" name="action" value="send_form">
				<button type="submit" class="submitBtn">' . __('Submit recipe', Recipe_CPT::RECIPE_DOMAIN) . '</button> 
				<br /><br />

				<div id="AjaxResponse"></div>
			</form>';

			return $form;
		}
	}

	// return dropdown list with user selected value
	function get_recipe_categories_dropdown($selected) {

		return  wp_dropdown_categories( array(
			'taxonomy' => 'tax_recipe_category', 
			'name' => 'tax_recipe_category', 
			'selected' => $selected, 
			'hide_empty' => false, 
			'echo' => 0,
			'show_option_none' => __('Choose a Category', Recipe_CPT::RECIPE_DOMAIN)
		));
	}

	function ajax_send_form() {

		global $current_user; 

		check_ajax_referer('send_form_nonce', 'nonce'); // check ajax nonce

		if('POST' == $_SERVER['REQUEST_METHOD'] && $_POST['action'] == "send_form") {

			// check form nonce
			if ( !wp_verify_nonce( $_POST['recipe_form_nonce_field'], plugin_basename( __FILE__ ) ) || !isset( $_POST['recipe_form_nonce_field']) ) {  
				return;
			} 
			else {

				$response = "";

				// check if there was an error uploading the image
				$result = $this->foodPicture_parse_file_errors( $_FILES['food_image_file'] );

				// if an error occured (e.g. format or size is not valid) -> display error message 
				if ( $result['error'] ) { 
					$response .= "<div class='error'>" . $result['error'] . "</div>";
				} 
				// no error -> insert post
				else {
					$user_recipe_data = array(
						'post_title' => sanitize_text_field($_POST['recipe_name']), // cleanup recipe name
						'post_type' => 'cpt_recipe_form',
						'post_author' => $current_user->ID,
						'post_status' => 'pending' // Post needs to be reviewed by an editor before it can be published
					);

					if ($post_id = wp_insert_post($user_recipe_data)) {

						$this->process_foodPicture('food_image_file', $post_id); // insert media attachement
						wp_set_object_terms($post_id, (int)$_POST['tax_recipe_category'], 'tax_recipe_category');
						$response .= "<div class='success'>" . __('Thanks! Your recipe has been saved.', Recipe_CPT::RECIPE_DOMAIN) . "</div>";
					}
				}
				echo $response;
			}
		}
		wp_die();
	}

	function foodPicture_parse_file_errors($file = '') {

		$result = array();
		$result['error'] = 0;

		// check if no file was selected (or unspecified error occured)
		if($file['error']) {
			$result['error'] = __('An upload error may have occurred.', Recipe_CPT::RECIPE_DOMAIN);
		  	return $result;
		}
	  
		// calculation of size: Bytes -> KiB 
		$image_data = getimagesize($file['tmp_name']);
		$image_size = intval($file['size']/1024); 
		
		// check if uploaded image has jpeg or png format
		if ( !in_array($image_data['mime'], unserialize(TYPE_WHITELIST)) ) {
		  	$result['error'] = __('You are only allowed to upload pictures in jpeg or png format.', Recipe_CPT::RECIPE_DOMAIN);
		}
		// check if size of uploaded image is larger than the maximum upload size
		else if ( $image_size > MAX_UPLOAD_SIZE ) {
			$result['error'] = __('Your Image is ', Recipe_CPT::RECIPE_DOMAIN) . $image_size . 
								__(' KiB! It cannot exceed ', Recipe_CPT::RECIPE_DOMAIN) . MAX_UPLOAD_SIZE . 
								__(' KiB.', Recipe_CPT::RECIPE_DOMAIN);
		}  
		return $result;
	}

	// insert media attachement
	function process_foodPicture($file, $post_id) {
 
		// load files 
		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
		require_once(ABSPATH . "wp-admin" . '/includes/media.php');
	   
		// save file submitted from POST request + create attachment post
		$attachment_id = media_handle_upload($file, $post_id);
	   
		// update post meta field if no error occured
        if( !is_wp_error($post_id) )
		    update_post_meta($post_id, '_thumbnail_id', $attachment_id); 
	  
		$attachment_data = array(
			'ID' => $attachment_id,
			'post_excerpt' => sanitize_text_field($_POST['recipe_name'])
		);
		
		// update post due to attachement
		wp_update_post($attachment_data);

		return $attachment_id;
	}
}
new Recipe_Form;