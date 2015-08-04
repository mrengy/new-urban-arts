<?php
/**
 * The Template for displaying all single posts.
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

get_header(); ?>
<style>
#mainnav .cssmb ul li a.nav-people {
	color: white;
	text-decoration: none;
	background-color: #f58425;
}
</style>

<?php echo do_shortcode("[sce-page-header]");?>
<!--single-mentors.php-->

		<div id="primary" class="mentors">
			<div id="content" role="main">

				<?php while ( have_posts() ) : the_post(); ?>
					<?php
					 	// only show previous / next navigation if this item is 'currently active'
						if (is_array(get_post_custom_values('_current1'))){
							if (in_array('yes', get_post_custom_values('_current1'))) { 
					?>
						<nav id="nav-single">
							<h3 class="assistive-text"><?php _e( 'Post navigation', 'twentyeleven' ); ?></h3>
							<span class="nav-previous"><?php previous_post_link( '%link', __( 'Previous', 'twentyeleven' ) ); ?></span>
							<span class="nav-next">| <?php next_post_link( '%link', __( 'Next', 'twentyeleven' ) ); ?></span>
						
						</nav><!-- #nav-single -->
					<?php 
							} //end if current 
						} //end if is array
					?>
					
					<?php
					 	//debugging custom fields
						/*
						echo '<pre>';
						// the_taxonomies( $post->ID ); 
						// print_r(get_post_custom_keys($post->ID));
						print_r(get_post_custom_values('_current1'));
						//print_r(get_post_custom($post->ID));
						echo '</pre>';
						*/
					?>
					
					<?php echo do_shortcode("[sce-get-featured]");?>
					<?php get_template_part( 'content', 'single' ); ?>

					<?php comments_template( '', true ); ?>

				<?php endwhile; // end of the loop. ?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>