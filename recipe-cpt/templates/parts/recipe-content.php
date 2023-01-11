<?php

if ( ! defined( 'WPINC' ) ) die;

new Recipe_Content();

class Recipe_Content {

    function __construct() {

		add_action('wp_enqueue_scripts', array($this, 'load_style'));
    }

    function load_style() {

		// register style
		wp_register_style( 'recipe-content-style', plugin_dir_url(__FILE__).'../../css/recipe-content-style.css', array(), 1, 'all' );

		// enqueue style
        wp_enqueue_style( 'recipe-content-style' );
    }

    // this function serves the purpose of a template part (since this is a plugin, template parts cannot be used)
    function render_recipe_content() {

        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="entry-content">	 
        
            <?php 

            // get the current post ID
            $post_id = get_the_ID(); 

            // check empty value
            if ( !empty($post_id) ) {

                // retrieve the meta box values
                $cats = get_the_term_list( $post_id, 'tax_recipe_category', '', ', ' ); // get all terms and concatenate them with a comma
                $categories = strip_tags($cats); // remove link
                $info = get_post_meta($post_id, '_info_meta_value_key', true );
                $difficultyValue = get_post_meta( $post_id, '_difficulty_meta_value_key', true );
                $quantity = get_post_meta( $post_id, '_quantity_meta_value_key', true );
                $ingredients = get_post_meta( $post_id, '_ingredients_meta_value_key', true );
                $preparation = get_post_meta( $post_id, '_preparation_meta_value_key', true );

                $generalInfosSet = ( !empty($categories) || !empty($quantity) || !empty($difficultyValue) || !empty($info) );
                $impostantInfosSet = ( !empty($ingredients) || !empty($preparation) );
                $allImpostantInfosSet = ( !empty($ingredients) && !empty($preparation) );
                
                // div Box should only be displayed if there is something to show
                if ( !empty(get_the_title()) || has_post_thumbnail() || $generalInfosSet || $impostantInfosSet ) {
                    
                    ?> <div id='recipe-content-<?php the_ID(); ?>' class='metabox-content'> <?php

                    // display title
                    ?>  <h1 id='recipename'> <?php the_title(); ?> </h1> <?php

                    ?> <div id='img-container'> <?php

                    // display thumbnail
                    if ( has_post_thumbnail() ) {
                        ?>
                            <div class='img-container-div'>
                                <img id='recipe-thumbnail' title='<?php the_title(); ?>' alt='<?php the_title(); ?>'
                                    src='<?php echo wp_get_attachment_url( get_post_thumbnail_id() ); ?>'>
                            </div>
                        <?php
                    }

                    // display meta box values: Category, Portions, Difficulty, Infos
                    if ( $generalInfosSet ) {
                        
                        ?> 
                        <div class='general-container-div'>
                        <h5 class='recipe-heading'> <?php _e('General information about the recipe', Recipe_CPT::RECIPE_DOMAIN) ?> </h5>
                        <?php

                        if ( !empty($categories) && !is_wp_error($categories) ) {

                            ?> 
                            <p><b> <?php _e('Category: ', Recipe_CPT::RECIPE_DOMAIN) ?> </b> <?php esc_html_e($categories) ?> </p> 
                            <?php
                        }

                        if ( !empty($quantity) ) {
                            ?> 
                            <p><b> <?php _e('Portions: ', Recipe_CPT::RECIPE_DOMAIN) ?> </b> <?php esc_html_e($quantity) ?> </p> 
                            <?php
                        }

                        if ( !empty($difficultyValue) ) {

                            // switch statement to get value not key
                            switch( esc_html($difficultyValue) ) 
                            {
                                case "verySimple" : $difficulty = __('very simple', Recipe_CPT::RECIPE_DOMAIN); break;
                                case "simple" : $difficulty = __('simple', Recipe_CPT::RECIPE_DOMAIN); break;
                                case "normal" : $difficulty = __('normal', Recipe_CPT::RECIPE_DOMAIN); break;
                                case "difficult" : $difficulty = __('difficult', Recipe_CPT::RECIPE_DOMAIN); break;
                                case "veryDifficult" : $difficulty = __('very difficult', Recipe_CPT::RECIPE_DOMAIN); break;
                                default: $difficulty = ""; break;
                            }

                            ?> 
                            <p><b> <?php _e('Difficulty level: ', Recipe_CPT::RECIPE_DOMAIN) ?> </b> <?php echo $difficulty ?> </p> 
                            <?php
                        }

                        if ( !empty($info) ) {
                            ?> 
                            <p><b> <?php _e('Additional information: ', Recipe_CPT::RECIPE_DOMAIN) ?> </b> <?php esc_html_e($info) ?> </p> 
                            <?php
                        }

                        ?> 
                        </div> <!-- general-container-div  -->
                        <?php
                    }
                    ?> 
                    </div> </br> <!-- img-container  -->
                    <?php

                    // display meta box textarea values: Ingredients, Preparation
                    // textareas required in recipe form, but not in backend
                    if ( $impostantInfosSet ) {

                        // just need separation in two columns if ingredients AND preparation are set
                        if ( $allImpostantInfosSet ) { ?> <div id='text-container'> <div class='text-container-div'> <?php }

                        if ( !empty($ingredients) ) {
                            ?> 
                            <h5 class='recipe-heading'> <?php _e('Ingredients and quantities', Recipe_CPT::RECIPE_DOMAIN) ?> </h5>
                            <p> <?php echo nl2br(sanitize_textarea_field( $ingredients) ) ?> </p> <!-- nl2br() to display new lines of textarea -->
                            <?php
                        }

                        if ( $allImpostantInfosSet ) { 
                            ?> 
                            </div> <!-- text-container-div -->
                            <?php 
                        } 

                        if ( $allImpostantInfosSet ) { ?> <div class='text-container-div'> <?php }

                        if ( !empty($preparation) ) {
                            ?> 
                            <h5 class='recipe-heading'> <?php _e('Preparation', Recipe_CPT::RECIPE_DOMAIN) ?> </h5>
                            <p> <?php echo nl2br(sanitize_textarea_field( $preparation) ) ?> </p>
                            <?php
                        }

                        if ( $allImpostantInfosSet ) { 
                            ?> 
                            </div> </div> <!-- text-container, text-container-div -->
                            <?php 
                        } 
                    }
                    
                    if( !is_single() ) { 
                        $content =
	                    "<form method='post'> </br>
	                        <input type='reset' id='button_" . $post_id . "_export' name='recipe_export' onclick='jsonexport(this," . $post_id . ");' value='" . __('Export', Recipe_CPT::RECIPE_DOMAIN) . "'>
                            <input type='reset' id='button_" . $post_id . "_print'name='print_recipe' onclick='printRecipe(" . $post_id . ")' value='" . __('print', Recipe_CPT::RECIPE_DOMAIN) . "'>
                            <input type='hidden' name='post_id' value='" . $post_id . "'>
	                    </form>";
                        echo $content;
                    }
                    ?> 
                    </div> <!-- recipe-content -->
                    <?php
                }
            }
        ?>
        </div> <!-- entry-content -->
        </article> 
        <?php
    }
}