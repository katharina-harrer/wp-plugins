<?php

class Recipe_MetaBoxes {

    // constants: metabox keys
    const META_INFO = '_info_meta_value_key';
    const META_DIFFICULTY = '_difficulty_meta_value_key';
    const META_QUANTITY = '_quantity_meta_value_key';
    const META_INGREDIENTS = '_ingredients_meta_value_key';
    const META_PREPARATION = '_preparation_meta_value_key';

    public function __construct() {
        
        add_action( 'add_meta_boxes', array( $this, 'add_recipe_metaboxes' ) );
        add_action( 'save_post', array( $this, 'save_recipe_metaboxes_data' ) );
    }

    /**
     * register meta boxes / add them with callback functions
    */
    function add_recipe_metaboxes() {

        add_meta_box(
            'info_metabox', 
            __('Additional information', Recipe_CPT::RECIPE_DOMAIN), 
            array( $this, 'render_info_custombox'),
            [ 'cpt_recipe_form' ]
        );
        add_meta_box(
            'difficulty_metabox', 
            __('Difficulty level', Recipe_CPT::RECIPE_DOMAIN), 
            array( $this, 'render_difficulty_custombox'),
            [ 'cpt_recipe_form' ]
        );
        add_meta_box(
            'quantity_metabox', 
            __('Portions', Recipe_CPT::RECIPE_DOMAIN), 
            array( $this, 'render_quantity_custombox'),
            [ 'cpt_recipe_form' ]
        );
        add_meta_box(
            'ingredients_metabox', 
            __('Ingredients', Recipe_CPT::RECIPE_DOMAIN), 
            array( $this, 'render_ingredients_custombox'),
            [ 'cpt_recipe_form' ]
        );
        add_meta_box(
            'preparation_metabox', 
            __('Preparation', Recipe_CPT::RECIPE_DOMAIN), 
            array( $this, 'render_preparation_custombox'),
            [ 'cpt_recipe_form' ]
        );
    }

    /**
     * display meta boxes to user
    */
    // meta box display callback
    function render_info_custombox( $post ) {

        wp_nonce_field( 'recipe_info_nonce', 'recipe_info_nonce_field' );

        // call up values from database (table wp_postmeta -> meta_key & meta_value)
        $value = get_post_meta( $post->ID, self::META_INFO, true );

        ?> 
            <input id="info_field" name="info_field" value="<?php esc_attr_e( $value ); ?>" class="widefat"/>
        <?php
    }

    function render_difficulty_custombox( $post ) {

        $value = get_post_meta( $post->ID, self::META_DIFFICULTY, true );
        $verySimple = __('very simple', Recipe_CPT::RECIPE_DOMAIN);
		$simple = __('simple', Recipe_CPT::RECIPE_DOMAIN);
		$normal = __('normal', Recipe_CPT::RECIPE_DOMAIN);
		$difficult = __('difficult', Recipe_CPT::RECIPE_DOMAIN);
		$veryDifficult = __('very difficult', Recipe_CPT::RECIPE_DOMAIN);

        ?>
            <select name="difficulty_field" id="difficulty_field">
                <option value="" disabled selected hidden><?php _e('Choose a difficulty level', Recipe_CPT::RECIPE_DOMAIN) ?></option>
                <option value="verySimple" <?php selected( $value, "verySimple" ); ?>><?php echo $verySimple ?></option>
                <option value="simple" <?php selected( $value, "simple" ); ?>><?php echo $simple ?></option>
                <option value="normal" <?php selected( $value, "normal" ); ?>><?php echo $normal ?></option>
                <option value="difficult" <?php selected( $value, "difficult" ); ?>><?php echo $difficult ?></option>
                <option value="veryDifficult" <?php selected( $value, "veryDifficult" ); ?>><?php echo $veryDifficult ?></option>
            </select>
        <?php
    }

    function render_quantity_custombox( $post ) {

        $value = get_post_meta( $post->ID, self::META_QUANTITY, true );
        ?>
            <input type="number" id="quantity_field" name="quantity_field" placeholder="0" min="0" value="<?php esc_attr_e( $value ); ?>"/>
        <?php
    }

    function render_ingredients_custombox( $post ) {

        $value = get_post_meta( $post->ID, self::META_INGREDIENTS, true );
        ?>
            <textarea rows="10" id="ingredients_field" name="ingredients_field" class="widefat"><?php esc_attr_e( $value ); ?></textarea>
        <?php
    }

    function render_preparation_custombox( $post ) {

        $value = get_post_meta( $post->ID, self::META_PREPARATION, true );
        ?>
            <textarea rows="10" id="preparation_field" name="preparation_field" class="widefat"><?php esc_attr_e( $value ); ?></textarea>
        <?php
    }

    /**
     *  save meta box content
    */
    function save_recipe_metaboxes_data( $post_id ) {

        // skip update post metas in case of autosaving
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
            
        // save the entered values
        if ( array_key_exists( 'info_field', $_POST ) ) {
            update_post_meta(
                $post_id,
                self::META_INFO,
                sanitize_text_field( $_POST['info_field'] )
            );
        }
        if ( array_key_exists( 'difficulty_field', $_POST ) ) {
            update_post_meta(
                $post_id,
                self::META_DIFFICULTY,
                sanitize_text_field( $_POST['difficulty_field'] )
            );
        }
        if ( array_key_exists( 'quantity_field', $_POST ) ) {
            update_post_meta(
                $post_id,
                self::META_QUANTITY,
                sanitize_text_field( $_POST['quantity_field'] )
            );
        }
        if ( array_key_exists( 'ingredients_field', $_POST ) ) {
            update_post_meta(
                $post_id,
                self::META_INGREDIENTS,
                // usage of sanitize_textarea_field() to preserve new lines and other whitespace
                sanitize_textarea_field( $_POST['ingredients_field'] ) 
            );
        }
        if ( array_key_exists( 'preparation_field', $_POST ) ) {
            update_post_meta(
                $post_id,
                self::META_PREPARATION,
                sanitize_textarea_field( $_POST['preparation_field'] )
            );
        }
    }
}
new Recipe_MetaBoxes();