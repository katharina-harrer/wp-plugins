<?php

if ( !defined('ABSPATH') ) { exit; }

// die if uninstall constant is not defined
if ( !defined('WP_UNINSTALL_PLUGIN') ) { die; }

// security check
if ( !current_user_can('activate_plugins') ) { return; }

// to be able to access the constants of settings.php, this file must be reloaded
require_once('php/settings.php'); 

// get the value of the uninstall option
$checked = get_option( Recipe_Settings::SETTING_UNINSTALL );

// delete posts, featured images, terms, taxonomy if user has selected (in settings) that he wants to delete them on uninstallation 
if ( $checked == 1 ) {

    Uninstall_Recipe::deletePostsAndImgs();
    Uninstall_Recipe::deleteTaxonomy();
}

// clear up plugin settings
Uninstall_Recipe::deleteSettings();

class Uninstall_Recipe {

    function deletePostsAndImgs() {

        // delete custom post type posts
        $recipe_cpt_args = array(
            'post_type' => 'cpt_recipe_form', 
            'posts_per_page' => -1,
            // 'any' retrieves any status except those from post statuses with 'exclude_from_search' set to true 
            // to delete ALL Posts, I need to provide trash and auto-draft explicit
            'post_status' => 'any, trash, auto-draft'
        ); 
        $recipe_posts = get_posts($recipe_cpt_args);

        if ( $recipe_posts ) {
            foreach ($recipe_posts as $post) {

                // delete featured image
                wp_delete_attachment( get_post_thumbnail_id( $post->ID ) ); 
                
                // delete post
                wp_delete_post( $post->ID );
            }
        }
    }

    function deleteTaxonomy() {

        // Plugin is deactivated -> taxonomy is not registered -> no use of get_terms(), etc. -> SQL-Query
        global $wpdb;
        $recipe_terms = $wpdb->terms;
        $recipe_taxonomy = $wpdb->term_taxonomy;

        // Delete terms; access database via SQL
        $wpdb->query( 
            "DELETE FROM
            {$recipe_terms}
            WHERE term_id IN
            ( SELECT * FROM (
                SELECT {$recipe_terms}.term_id
                FROM {$recipe_terms}
                JOIN {$recipe_taxonomy}
                ON {$recipe_taxonomy}.term_id = {$recipe_terms}.term_id
                WHERE taxonomy = 'tax_recipe_category'
            ) as T
            );
        ");

        // Delete taxonomies
        $wpdb->query( "DELETE FROM {$recipe_taxonomy} WHERE taxonomy = 'tax_recipe_category'" );
    }

    // Delete settings
    function deleteSettings() {

        $settingOptions = array( 
            Recipe_Settings::SETTING_MAX_IMAGE_SIZE, 
            Recipe_Settings::SETTING_UNINSTALL
        );
        foreach ( $settingOptions as $setting ) {
            if ( get_option($setting) ) {
                delete_option( $setting );
            }
        }
    }
}

?>