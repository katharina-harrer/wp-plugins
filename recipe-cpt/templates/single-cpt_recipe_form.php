<?php
/*
Template Name: Custom Single Template for Recipe Plugin
Template Post Type: cpt_recipe_form
*/
?>

<!DOCTYPE html>
<head>
<?php
	require_once('parts/recipe-content.php');
	
	// enqueue style for template 
	wp_enqueue_style('single-style');        
	wp_styles()->do_item('single-style'); 
?>
</head>

<?php  ?>
<?php get_header(); ?>
<section class="page-wrap">
<div id="cont">
<main id="main" class="site-main" role="main">

	<?php

	while ( have_posts() ) {

		the_post(); 
		?>
		<!-- display date and author -->
		<div class="meta_head">
		<?php 
			_e('Posted on ', Recipe_CPT::RECIPE_DOMAIN) . the_date() .
			_e(' by ', Recipe_CPT::RECIPE_DOMAIN) . the_author_posts_link()
		?>
		</div>
		<!-- display title, metaboxes, thumbnail-->
		<?php
			Recipe_Content::render_recipe_content();
		?>
		<!-- display link to previous and next post -->
		<div class="meta_foot">
			<div class="alignleft">
				<?php previous_post_link(); ?>
			</div>
			<div class="alignright">
				<?php next_post_link(); ?>	
			</div>
			</br>
		</div> 
		<?php
	} 
	
	?>

</main>
</div>
</section>
<?php 
	get_sidebar();
	get_footer();
?>