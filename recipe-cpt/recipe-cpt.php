<?php
/*
 * Plugin Name:  Recipe Plugin
 * Plugin URI: 
 * Description:  Plugin for a recipe form and the display of these recipes
 * Version:      1.0
 * Author:       Katharina Harrer, Natalie Engert
 * Text Domain:  cpt-recipe
 * Domain Path:  /languages
*/

if ( ! defined( 'WPINC' ) ) die;

require_once('php/hooks.php');
require_once('php/settings.php');
require_once('php/customizer.php');
require_once('php/meta-boxes.php');
require_once('php/form.php');
    
if ( is_admin() ) // just admin is allowed to edit settings
    new Recipe_Settings();

// Internationalize text
add_action( 'plugins_loaded', array( 'Recipe_CPT', 'recipe_plugin_translation' ) );

class Recipe_CPT {

    const RECIPE_DOMAIN = 'cpt-recipe';

    // Localisation
    public static function recipe_plugin_translation() {

        load_plugin_textdomain( self::RECIPE_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    function __construct() {

        add_action('after_switch_theme', array('Recipe_Hooks', 'recipe_plugin_activate'));

        // Run this on plugin activation
        register_activation_hook(__FILE__, array('Recipe_Hooks', 'recipe_plugin_activate'));

        // Run this on plugin deactivation
        register_deactivation_hook( __FILE__, array('Recipe_Hooks', 'recipe_plugin_deactivate'));

        // Filter the template by type 'single'
        add_filter('single_template', array($this, 'get_cpt_single_template'));

        // Filter the template by type 'archive'
        add_filter('archive_template', array($this, 'get_cpt_archive_template'));

        add_action('init', array($this, 'register_cpt_single_style'));
        add_action('init', array($this, 'set_default_options'));
        add_action('init', array($this, 'create_recipe_custom_post_type'));
        add_action('init', array($this, 'create_recipe_taxonomy'));
    }

    function get_cpt_single_template( $single_template ) {

        global $post;

        $file = dirname(__FILE__) .'/templates/single-'. $post->post_type .'.php';

        // Checks for single template by post type
        if( file_exists( $file ) ) $single_template = $file;

        return $single_template;
    }

    function get_cpt_archive_template( $archive_template ) {

        global $post;

        $file = dirname(__FILE__) .'/templates/archive-'. $post->post_type .'.php';

        // Checks for archive template by post type
        if( file_exists( $file ) ) $archive_template = $file;

        return $archive_template;
    }

    // usage of register the script so it is not output unless it is enqueued later
    function register_cpt_single_style() {

        wp_register_style( 'single-style', plugin_dir_url(__FILE__).'css/single-style.css' );
    }

    // set default values for settings
    function set_default_options() {
        
        if ( !get_option( Recipe_Settings::SETTING_MAX_IMAGE_SIZE ) )
            add_option(Recipe_Settings::SETTING_MAX_IMAGE_SIZE, Recipe_Settings::SETTING_DEFAULT_MAX_IMAGE_SIZE);
        if ( !get_option( Recipe_Settings::SETTING_UNINSTALL ) )
            add_option(Recipe_Settings::SETTING_UNINSTALL, Recipe_Settings::SETTING_DEFAULT_UNINSTALL);
    }

    function create_recipe_custom_post_type() {

        // set UI labels for Custom Post Type
        $recipe_cpt_labels = array(
            'name' => __('Recipes', self::RECIPE_DOMAIN), 
            'singular_name' => __('Recipe', self::RECIPE_DOMAIN),
            'add_new' => __('Add Recipe', self::RECIPE_DOMAIN),
            'add_new_item' => __('Add Recipe', self::RECIPE_DOMAIN), 
            'edit_item' => __('Edit Recipe', self::RECIPE_DOMAIN), 
            'new_item' => __('Add Recipe', self::RECIPE_DOMAIN), 
            'all_items' => __('All Recipes', self::RECIPE_DOMAIN), 
            'view_item' => __('Show Recipe', self::RECIPE_DOMAIN), 
            'search_items' => __('Search Recipes', self::RECIPE_DOMAIN), 
            'not_found' => __('No Recipe found', self::RECIPE_DOMAIN),
            'not_found_in_trash' => __('No Recipes found in trash ', self::RECIPE_DOMAIN), 
            'parent_item_colon' => __('Parent Recipes:', self::RECIPE_DOMAIN), 
            'menu_name' => __('Recipes', self::RECIPE_DOMAIN) 
        );

        // CPT options
        $recipe_cpt_args = array(
            'labels' => $recipe_cpt_labels,
            'public' => true,
            'query_var' => true,
            'rewrite' => true,
            'has_archive' => true, 
            'menu_icon' => 'dashicons-media-text',
            'supports' => array('title', 'author', 'thumbnail', 'revisions') // don't need 'editor', got metaboxes
        );

        register_post_type('cpt_recipe_form', $recipe_cpt_args);
    }

    function create_recipe_taxonomy() {

        // set UI labels for Taxonomy
        $recipe_category_labels = array(
            'name' => __('Recipe Categories', self::RECIPE_DOMAIN), 
            'singular_name' => __('Recipe Category',self::RECIPE_DOMAIN), 
            'search_items' =>  __('Search Recipe Categories', self::RECIPE_DOMAIN),
            'all_items' => __('All Recipe Categories', self::RECIPE_DOMAIN),
            'parent_item' => __('Parent Recipe Category', self::RECIPE_DOMAIN),
            'parent_item_colon' => __('Parent Recipe Category:', self::RECIPE_DOMAIN),
            'edit_item' => __('Edit Recipe Category', self::RECIPE_DOMAIN), 
            'update_item' => __('Update Recipe Category', self::RECIPE_DOMAIN), 
            'add_new_item' => __('Add New Recipe Category', self::RECIPE_DOMAIN),
            'new_item_name' => __('New Recipe Category', self::RECIPE_DOMAIN),
            'menu_name' => __('Recipe Categories', self::RECIPE_DOMAIN)
        );  
        
        // Taxonomy options
        $recipe_category_args = array(
            'hierarchical' => true,
            'labels' => $recipe_category_labels,
            'query_var' => true,
            'rewrite' => array( 'slug' => 'user_recipe_category' ),
            'show_admin_column' => true, 
            'show_ui' => true
        );
          
        register_taxonomy('tax_recipe_category', array('cpt_recipe_form'), $recipe_category_args);

        // set default Terms for Taxonomy 
        $default_recipe_cats = array(
            __('Appetizer', self::RECIPE_DOMAIN), 
            __('Main course', self::RECIPE_DOMAIN), 
            __('Dessert', self::RECIPE_DOMAIN) 
        );

        foreach($default_recipe_cats as $cat) {  
            // insert term if it doesn't exist
            if( !term_exists($cat, 'tax_recipe_category') )
                $term = wp_insert_term($cat, 'tax_recipe_category');
            if( !is_wp_error($term) && isset($term['term_id']) )    
                $term_id = $term['term_id'];
        }
    }
}
new Recipe_CPT;