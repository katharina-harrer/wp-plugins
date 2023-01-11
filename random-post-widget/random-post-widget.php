<?php
/**
 * Plugin Name:  Random Post Widget
 * Plugin URI:  
 * Description:  Shows thumbnail and permalink of an almost random post
 * Version:      1.0
 * Author:       Katharina Harrer
 * Text Domain:  rp_widget
 * Domain Path:  /languages
*/

if ( ! defined( 'WPINC' ) ) die;

// register random post widget
add_action( 'widgets_init', function() { register_widget( 'Random_Post_Widget' ); } );

// Internationalize text
// usage of plugins_loaded to ensure, that widget name and description gets translated
add_action( 'plugins_loaded', array( 'Random_Post_Widget', 'widget_translation' ) );

class Random_Post_Widget extends \WP_Widget {

    // Text domain
    const RP_DOMAIN = 'rp_widget'; 

    // Localisation
    public static function widget_translation() {

        load_plugin_textdomain( self::RP_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    // Set widget name, etc.
    public function __construct() {

        add_action( 'admin_enqueue_scripts', array($this, 'load_assets') ); // style for backend 
        add_action( 'wp_enqueue_scripts', array($this, 'load_assets') ); // style for frontend

        $options = array( 
            'classname' => 'random_post_widget',
            'description' => __( 'This is a widget that displays an almost random post.', self::RP_DOMAIN )
            // "almost" because you can specify the post by post type, taxonomy, term
        );
        parent::__construct( 'rp_widget',  __( 'Random Posts', self::RP_DOMAIN ), $options );
    }

    function load_assets() {
        
        // register style
        wp_register_style( 'rp-widget-style', plugin_dir_url(__FILE__).'/css/style.css', array(), 1, 'all' );
        // enqueue style
        wp_enqueue_style( 'rp-widget-style' );
    }

    // echo widget content
    public function widget( $args, $instance ) {

        $q_args = array(
            'post_type' => esc_attr( $instance['post_type'] ), 
            'orderby' => 'rand',
            'posts_per_page' => esc_attr( $instance['number_of_posts'] ),
            'post_status' => 'publish',
        );

        // only posts from the taxonomy selected by the user should be displayed
        // e.g. $instance['taxonomy'] = taxonomyA=1,2&taxonomyB=5
        if ( !empty( $instance['taxonomy'] ) ) {

            parse_str( $instance['taxonomy'], $tax_args ); // parse_str() interprets the "=" and "&"
            // $tax_args would look like this now: $tax_args['taxonomyA'] -> 1,2 +  $tax_args['taxonomyB'] -> 5

            $taxonomy_query = array();
            foreach( array_keys( $tax_args ) as $key => $slug ) {

                // the IDs must be separated by the comma
                $termIds = explode( ',', $tax_args[ $slug ] ); 
                $taxonomy_query[] = array(
                    'taxonomy' => $slug,
                    'terms'    => $termIds,
                    'field'    => 'id'
                );
            }
            // use tax_query to filter for selected taxonomy/s and term/s
            $q_args['tax_query'] = $taxonomy_query;
        }

        $loop = new \WP_Query( $q_args );

        if ( $loop->have_posts() ) {
    
            echo $args['before_widget'];

            // show widget title 
            echo $args['before_title'];
            if( isset($instance['title']) )
                ?> <h4> <?php echo apply_filters( 'widget_title', esc_attr($instance['title']), $instance, $this->id_base ); ?> </h4> <?php
            echo $args['after_title'];
            
            // using a list because the user can choose to display multiple posts
            ?> <div> <ul class="unordered-list"> <?php

            // loop through posts
            while ( $loop->have_posts() ) : $loop->the_post(); 

                ?> <li> <?php

                // show post title as permalink to post
                the_title( '<b><a rel="bookmark" href="' . esc_url( get_permalink() ) . '"> ', '</a></b>' );

                // show thumbnail at the selected size
                if ( has_post_thumbnail() ) {
                    ?>
                    </br>
                    <a href="<?php the_permalink() ; ?>" title="<?php the_title();?>">
                        <?php the_post_thumbnail( esc_attr($instance['thumbnail_size']) ); ?>
                    </a>
                    <?php
                }

                // show excerpt at the selected size
                if ( $instance['excerpt'] ) {
                    ?> <br/> <?php
                    // shows content with selected word size and '...' at the end
                    echo wp_trim_words( apply_filters('rp_excerpt', get_the_excerpt()), (int)$instance['excerpt_length'], ' &hellip;' );
                }

                ?> </li ><br/> <?php

            endwhile;

            echo $args['after_widget'];
            ?></ul></div><?php
        } 
        wp_reset_postdata(); // Reset global $post
    }

    // Widget settings
    public function form( $instance ) {

        // merge user defined arguments into defaults array
		$instance = wp_parse_args( (array) $instance, array(
            'title' => esc_attr__( 'Look at this Post:', self::RP_DOMAIN ),
            'excerpt' => false,
            'excerpt_length' => 0,
            'number_of_posts' => 1,
            'post_type' => 'post',
            'taxonomy' => '',
            'sizes' => array( 'thumbnail', 'medium', 'large' ),
            'thumbnail_size' => 'thumbnail'
        ));

        // backend form
        ?>

        <!-- TITLE - TEXT INPUT -->
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">
                <?php _e( 'Title:', self::RP_DOMAIN ); ?>
            </label> 
            <input type="text" class="widefat"
                name="<?php echo $this->get_field_name( 'title' ); ?>" 	
                id="<?php echo $this->get_field_id( 'title' ); ?>" 
                value="<?php esc_attr_e( $instance['title'] ); ?>">
            </input>
        </p>

        <!-- EXCERPT - CHECKBOX -->
        <p>
            <input type="checkbox" <?php checked($instance['excerpt']); ?> 
                name="<?php echo $this->get_field_name('excerpt'); ?>"
                id="<?php echo $this->get_field_id('excerpt'); ?>">
            </input>
            <label for="<?php echo $this->get_field_id('excerpt'); ?>">
                <?php _e('Display Excerpt', self::RP_DOMAIN); ?>
            </label>
        </p>

        <!-- EXCERPT - NUMBER INPUT -->
        <p>
            <label for="<?php echo $this->get_field_id('excerpt_length'); ?>">
                <?php _e('Number of words that should be displayed:', self::RP_DOMAIN); ?>
            </label>
            <input type="number" step="1" min="0" id="number"
                name="<?php echo $this->get_field_name('excerpt_length'); ?>"
                id="<?php echo $this->get_field_id('excerpt_length'); ?>" 
                value="<?php echo (int)$instance['excerpt_length']; ?>">
            </input>
        </p>

        <!-- NUMBER OF POSTS - NUMBER INPUT -->
        <p>
            <label for="<?php echo $this->get_field_id( 'number_of_posts' ); ?>">
                <?php _e( 'Number of posts:', self::RP_DOMAIN ); ?>
            </label>
            <input type="number" step="1" min="-1" id="number"
                name="<?php echo $this->get_field_name( 'number_of_posts' ); ?>" 
                id="<?php echo $this->get_field_id( 'number_of_posts' ); ?>"
                value="<?php echo (int)$instance['number_of_posts']; ?>">
            </input>
            <small><?php _e( 'Select -1 to display all posts', self::RP_DOMAIN ); ?></small>
        </p>

        <!-- POST TYPE - DROPDOWN LIST -->
        <p>
            <label for="<?php echo $this->get_field_id( 'post_type' ); ?>">
                <?php _e( 'Post type:', self::RP_DOMAIN ); ?>
            </label>
            <select name="<?php echo $this->get_field_name( 'post_type' ); ?>"
                id="<?php echo $this->get_field_id( 'post_type' ); ?>">
                <!-- get all availible post types -->
                <?php foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $post_type ) { 
                    ?>
                    <option value="<?php esc_attr_e( $post_type->name ); ?>" 
                        <?php selected( $instance['post_type'], $post_type->name ); ?>>
                        <?php esc_html_e( $post_type->labels->name ); ?>
                    </option>
                    <?php 
                } ?>
            </select>
        </p>

        <!-- TAXONOMY - TEXT INPUT -->
        <p>
            <label for="<?php echo $this->get_field_id( 'taxonomy' ); ?>">
                <?php _e( 'Taxonomy:', self::RP_DOMAIN ); ?>
            </label>
            <input type="text" class="widefat" 
                name="<?php echo $this->get_field_name( 'taxonomy' ); ?>" 
                id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" 
                value="<?php esc_attr_e( $instance['taxonomy'] ); ?>" >
            </input>
            <small>
                <!-- show how user should specify input -->
                <?php _e( 'e.g.: *Taxonomy*=1,3&amp;*Taxonomy*=10', self::RP_DOMAIN );?>
                <br/> 
                <!-- show all availible taxonomies -->
                <b><?php _e( 'All Taxonomies: ', self::RP_DOMAIN );?></b> 
                <i><?php echo implode( ', ', get_taxonomies(array( 'public' => true ))); ?></i>
                <br />
                <!-- show all taxonomies dependent on selected post type -->
                <?php $availibleTax = implode( ', ', get_object_taxonomies( $instance['post_type'] )); ?>
                <b><?php _e( 'Taxonomies depending on selected post type: ', self::RP_DOMAIN );?></b> 
                <i><?php echo empty($availibleTax) ? __('none', self::RP_DOMAIN) : $availibleTax; ?></i>
                <br />
                <!-- show list of terms user could specify (*term number*: *term*) -->
                <?php
                    $taxonomies = get_object_taxonomies( $instance['post_type'] );
                    foreach ($taxonomies as $tax) {
                        $terms = get_terms( array('taxonomy' => $tax) ); 
                        if ( !empty($terms) && !is_wp_error($terms) ) {
                            echo ( __('You could select the Taxonomy ', self::RP_DOMAIN) . '<b><i>' . $tax . '</i></b>' . __(' with following terms:', self::RP_DOMAIN) );
                            echo '<ul>';
                            // show all terms of selected Taxonomy, that belong to at least one post
                            foreach ( $terms as $term ) {
                                echo '<li><i>' . $term->term_id . ': ' . $term->name . '</i></li>';
                            }
                            echo '</ul>';
                        }
                    }
                ?>
            </small>
        </p>

        <!-- THUMBNAIL SIZE - DROPDOWN LIST -->
        <p>
            <label for="<?php echo $this->get_field_id( 'thumbnail_size' ); ?>">
                <?php _e( 'Size of Thumbnail:', self::RP_DOMAIN ); ?>
            </label>
            <select name="<?php echo $this->get_field_name( 'thumbnail_size' ); ?>"
                id="<?php echo $this->get_field_id( 'thumbnail_size' ); ?>" >
                <?php foreach ( $instance['sizes'] as $thumbnail_size ) { ?> <!-- alternative to show ALL sizes: get_intermediate_image_sizes() -->
                    <option value="<?php esc_attr_e( $thumbnail_size ); ?>" 
                        <?php selected( $instance['thumbnail_size'], $thumbnail_size ); ?>>
                        <?php 
                            // switch statement for translation purposes
                            switch( $thumbnail_size ) {
                                case 'thumbnail' : esc_html_e( 'thumbnail', self::RP_DOMAIN ); break;
                                case 'medium' : esc_html_e( 'medium', self::RP_DOMAIN ); break;
                                case 'large' : esc_html_e( 'large', self::RP_DOMAIN ); break;
                                default: echo ''; break;
                            } 
                        ?>
                    </option>
                <?php }	?>
            </select>
        </p>
        
        <?php 
    }

    // update values selected in backend
    public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['excerpt'] = !isset($new_instance['excerpt']) ? false : (bool)$new_instance['excerpt'];
        $instance['excerpt_length'] = intval($new_instance['excerpt_length']);
        $instance['number_of_posts'] = (int)$new_instance['number_of_posts'];
		$instance['post_type'] = esc_attr( $new_instance['post_type'] );
        $instance['taxonomy'] = esc_attr( $new_instance['taxonomy'] );
		$instance['thumbnail_size'] = esc_attr( $new_instance['thumbnail_size'] );

		return $instance;
	}
}