<?php

if ( ! defined( 'ABSPATH' ) ) die;

class Recipe_Hooks {

    static function recipe_plugin_activate() {

        // security check
        // make sure that user has permissions to perform activation and that action is coming from right page
        if ( ! current_user_can( 'activate_plugins' ) )
            return;

        // trigger function that registers the custom post type
        $obj = new Recipe_CPT();
        $obj->create_recipe_custom_post_type(); 

        // clear permalinks after post type has been registered -> user don't have to do it manually
        flush_rewrite_rules(); 
    }

    static function recipe_plugin_deactivate() {
       
        if ( ! current_user_can( 'activate_plugins' ) )
            return;

        // unregister post type -> rules are no longer in memory
        unregister_post_type( 'cpt_recipe_form' );
        
        // clear permalinks to remove post types rules from database
        flush_rewrite_rules();
    }
}