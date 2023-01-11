<?php

if ( ! defined('ABSPATH') ) exit;

class Recipe_Settings {

    // constants: setting names and default values
    const SETTING_MAX_IMAGE_SIZE = 'max_upload_size_input';
    const SETTING_DEFAULT_MAX_IMAGE_SIZE = 500;
    const SETTING_MIN_IMAGE_SIZE = 200;

    const SETTING_UNINSTALL = 'uninstall_plugin_radiobtn';
    const SETTING_DEFAULT_UNINSTALL = 0;

    public function __construct() {

        add_action( 'admin_init', array( $this, 'sub_menu_page_init' ) );
        add_action( 'admin_menu', array( $this, 'add_recipe_settings_submenu_page' ) );
    }

    // Add sub menu page to the custom post type
    public function add_recipe_settings_submenu_page() {

        add_submenu_page(
            'edit.php?post_type=cpt_recipe_form',
            __('Recipe Settings', Recipe_CPT::RECIPE_DOMAIN),
            __('Settings', Recipe_CPT::RECIPE_DOMAIN),
            'manage_options',
            'recipe-cpt-settings',
            array($this, 'display_settings_backend')
        );
    }
    
    // Options page callback
    public function display_settings_backend() {

        // security
        if ( !current_user_can( 'manage_options') ) {
            wp_die( __('You are not authorized to adjust the options.', Recipe_CPT::RECIPE_DOMAIN));
        }
        
        ?>  
        <div class="wrap">
            <h1> <?php esc_html_e('Recipes &rsaquo; Settings', Recipe_CPT::RECIPE_DOMAIN ); ?> </h1>
            <?php settings_errors(); ?> <!-- "settings saved" message -->
            <form method="POST" action="options.php">
                <?php
                    settings_fields( 'recipe-cpt-settings' );
                    do_settings_sections( 'recipe-settings-page' );
                    submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    // Register and add settings
    public function sub_menu_page_init() {
        
        // add settings section to option group 'recipe-cpt-settings'
        add_settings_section(
            'recipe_settings_section', // ID
            __('Settings', Recipe_CPT::RECIPE_DOMAIN), // Title
            array( $this, 'print_section_info' ), // Callback
            'recipe-settings-page' // Page
        );
    
        // define max upload size setting
        register_setting(
            'recipe-cpt-settings', // Option group
            self::SETTING_MAX_IMAGE_SIZE // Option name
        );

        // define uninstall setting
        register_setting(
            'recipe-cpt-settings', 
            self::SETTING_UNINSTALL
        );

        // define input field for maximum upload size
        add_settings_field(
            self::SETTING_MAX_IMAGE_SIZE, 
            __('Maximum upload size for recipe pictures in KiB', Recipe_CPT::RECIPE_DOMAIN), // Title
            array( $this, 'max_upload_size_callback' ), // Callback
            'recipe-settings-page', // Page
            'recipe_settings_section' // Section
        );      

        // define radiobuttons for uninstall setting
        add_settings_field(
            self::SETTING_UNINSTALL, 
            __('Uninstall Settings', Recipe_CPT::RECIPE_DOMAIN), 
            array( $this, 'uninstall_settings_callback' ), 
            'recipe-settings-page', 
            'recipe_settings_section' 
        );
    }

    // print the section text
    public function print_section_info() {

        _e('Settings affecting the form and uninstallation of the Recipe Plugin', Recipe_CPT::RECIPE_DOMAIN);
    }

    // let user choose what maximum upload size they want
    public function max_upload_size_callback() {

        echo "<input type='number' min='" . strval(self::SETTING_MIN_IMAGE_SIZE) . "' step='10' id='max-input' 
            name='" . self::SETTING_MAX_IMAGE_SIZE . "' 
            value='" . get_option( self::SETTING_MAX_IMAGE_SIZE ) . "' /> <br/>
            <small><i>" . __('minimum: ', Recipe_CPT::RECIPE_DOMAIN) . strval(self::SETTING_MIN_IMAGE_SIZE) . ", " . 
            __('default: ', Recipe_CPT::RECIPE_DOMAIN) . strval(self::SETTING_DEFAULT_MAX_IMAGE_SIZE) . "</i></small>";
    }

    // let user choose if they want posts, featured images, terms and taxonomy deleted or not
    public function uninstall_settings_callback() {

        $value = get_option( self::SETTING_UNINSTALL );
        $uninstallMsg = __('Select whether you really want to delete all posts, featured images, terms and the taxonomy of the Custom Post Type during the uninstallation process.', Recipe_CPT::RECIPE_DOMAIN);
        $noMsg = __('No, the data should not be deleted', Recipe_CPT::RECIPE_DOMAIN); 
        $yesMsg = __('Yes, the data should be deleted', Recipe_CPT::RECIPE_DOMAIN); 

        echo '<p>' . $uninstallMsg . '</p> <br/>';
        printf(
            '<input type="radio" name="'. self::SETTING_UNINSTALL .'" value="0"%s /> ' . $noMsg . ' </br> </br>
            <input type="radio" name="'. self::SETTING_UNINSTALL .'" value="1"%s /> ' . $yesMsg . '',
            checked( 0, $value, false ), // "checked" attr when "No"
            checked( 1, $value, false ) // "checked" attr when "Yes"
        );      
    }
}