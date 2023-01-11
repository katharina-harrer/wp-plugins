<?php

class Tips_Export {

    public function __construct() {

        add_action( 'admin_menu', array( $this, 'add_tips_export_submenu' ) );
        add_action('admin_enqueue_scripts', array($this, 'load_script'));
    }

	public function load_script() {

		wp_register_script( 'export-script', plugin_dir_url(__FILE__).'../js/copy-export.js');
        wp_enqueue_script( 'export-script' );
    }

    // Add submenu page to the custom post type
    public function add_tips_export_submenu() {

        add_submenu_page(
            'edit.php?post_type=tipps', 
            __('Export', Tip::TIPPSDOMAIN), 
            __('Export', Tip::TIPPSDOMAIN), 
            'activate_plugins', 
            'tips-export', 
            array($this, 'print_tipps_export')
        );
    }

    // Export page callback
    public function print_tipps_export() {

        ?>  

        <div class="wrap">
            <h1> <?php esc_html_e( __('Tips &rsaquo; Export', Tip::TIPPSDOMAIN) ); ?> </h1>
            <form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                <?php
                    submit_button( __('Export JSON', Tip::TIPPSDOMAIN) );
                ?>
            </form>
        </div>

        <?php

        // after button is clicked -> display textarea with json
        if ( isset($_POST['submit']) ) {
            $args = array( 
                // display all published posts from cpt 'tipps'
                'post_type' => 'tipps', 
                'post_status' => 'publish', 
                'posts_per_page' => -1 
            );
            $query = new WP_Query( $args );
            $posts = $query->get_posts();   // $posts contains the post objects
            
            $output = array();

            foreach( $posts as $post ) { 

                $output[] = 
                array( 
                    'id' => $post->ID, 

                    // just get all names of selected terms
                    'categories' => wp_list_pluck( (wp_get_post_terms( $post->ID, 'tipps_category' )), 'name' ), 
                    
                    // title and tip may contain multiple spaces and line breaks -> replace them with a single space
                    'title' => trim( preg_replace( '/\s\s+/', ' ', $post->post_title ) ), 
                    'tip' => trim( preg_replace( '/\s\s+/', ' ', $post->post_content ) )
                );
            }
            ?>
                <!-- JSON_UNESCAPED_UNICODE -> to display umlauts correctly -->
                <!-- JSON_PRETTY_PRINT -> prettified data with newline characters -->
                <textarea id="tips_export" rows="25" cols="125"><?php esc_html_e( json_encode( $output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ); ?></textarea><br/>
                <button onclick="copy()"><?php _e('Copy', Tip::TIPPSDOMAIN) ?></button> <!-- copy button to select json code -->
            <?php
        }
    }
}
new Tips_Export();