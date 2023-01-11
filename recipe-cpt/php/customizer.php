<?php

// Setup Customizer settings + controls
add_action( 'customize_register' , array( 'Template_Customizer' , 'recipe_post_customize_register' ) );

// Output custom CSS
add_action( 'wp_head' , array( 'Template_Customizer' , 'recipe_pt_customize_css' ) );

class Template_Customizer {

    // select sanitization function
    function slug_sanitize_select( $input, $setting ) {
    
        $choices = $setting->manager->get_control( $setting->id )->choices; // get list of possible select options
        $input = sanitize_key( $input ); // ensure input is slug

        // if not valid return default
        if( !array_key_exists( $input, $choices ) )
            return $setting->default; 
        // if valid return input
        else
            return $input;
    }

    // Customizer content
    function recipe_post_customize_register( $wp_customize ) {

        // Add a theme section
        $wp_customize->add_section( 'cpt_template_settings_section',
        array(
            'title'=> __( 'Recipe Template Settings', Recipe_CPT::RECIPE_DOMAIN ),
            'priority' => 30,
        ));

        // Add theme setting for title transformation
        $wp_customize->add_setting( 'cpt_post_title_transformation',
        array(
            'default' => 'none',
            'type' => 'theme_mod',
            'capability' => 'edit_theme_options', // permission for accessing setting
            'transport' => 'refresh',
            'sanitize_callback' => array( new Template_Customizer(), 'slug_sanitize_select' )
        ));

        // Add control to theme section
        $wp_customize->add_control( 'cpt_post_title_transformation', 
        array(
            'label' => esc_html__( 'Title Transformation', Recipe_CPT::RECIPE_DOMAIN ),
            'section' => 'cpt_template_settings_section',
            'description' => __( 'Using this option you can transform the recipename', Recipe_CPT::RECIPE_DOMAIN ),
            'type' => 'select',
            'choices' => array(
                'none' => esc_html__( 'None', Recipe_CPT::RECIPE_DOMAIN ),
                'capitalize' => esc_html__( 'Capitalize', Recipe_CPT::RECIPE_DOMAIN ),
                'uppercase' => esc_html__( 'Uppercase', Recipe_CPT::RECIPE_DOMAIN ),
                'lowercase' => esc_html__( 'Lowercase', Recipe_CPT::RECIPE_DOMAIN )
            )
        ));

        // Add theme setting for headline color
        $wp_customize->add_setting( 'cpt_post_heading_color',
        array(
            'default' => '#e9c46a',
            'type' => 'theme_mod',
            'capability' => 'edit_theme_options', 
            'transport' => 'refresh',
            'sanitize_callback' => 'sanitize_hex_color' // returns 3 or 6 digit hex color with #, or nothing
        ));

        // Add control to theme section
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'cpt_post_heading_color_control', 
        array(
            'label' => __( 'Headline Color', Recipe_CPT::RECIPE_DOMAIN ),
            'section' => 'cpt_template_settings_section',
            'settings' => 'cpt_post_heading_color'
        )));

        // Add theme setting for background color
        $wp_customize->add_setting( 'cpt_post_background_color',
        array(
            'default' => '#ffffe0',
            'type' => 'theme_mod',
            'capability' => 'edit_theme_options', 
            'transport' => 'refresh',
            'sanitize_callback' => 'sanitize_hex_color'
        ));

        // Add control to theme section
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'cpt_post_background_color_control', 
        array(
            'label' => __( 'Background Color', Recipe_CPT::RECIPE_DOMAIN ),
            'section' => 'cpt_template_settings_section',
            'settings' => 'cpt_post_background_color'
        )));
    }

    // CSS Customize CSS
    function recipe_pt_customize_css() {
        ?>
        <!-- Code to output the css -->
        <style type="text/css">

            #recipename {
                text-transform: <?php echo get_theme_mod('cpt_post_title_transformation'); ?>;
            }

            .recipe-heading {
                background-color: <?php echo get_theme_mod('cpt_post_heading_color', '#e9c46a'); ?>;
            }

            .metabox-content {
                background-color: <?php echo get_theme_mod('cpt_post_background_color', '#ffffe0'); ?>;
            }

        </style>
        <?php
    }
}