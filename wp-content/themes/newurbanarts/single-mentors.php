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
				<?php
					//not sure about this
					
        			global $wpdb;
        			$nested_posts_with_term = $wpdb->get_results("SELECT object_id FROM wp_term_relationships WHERE term_taxonomy_id = 42", ARRAY_N);

        			//convert nested array into flat array
        			$posts_with_term = array();
        			foreach ($nested_posts_with_term as &$value) {
        				array_push($posts_with_term, $value[0]);
        			}
					echo('<pre>');
					print_r($posts_with_term);
					echo('</pre>');
        			
				?>

				<?php while ( have_posts() ) : the_post(); ?>
					<?php
					 	// only show previous / next navigation if this item is 'currently active'
						if (is_array(get_post_custom_values('_current1'))){
							if (in_array('yes', get_post_custom_values('_current1'))) { 
								//includes function to define non-current years to exclude from previous / next links
								include('function_define_excluded_years.php');
					?>
								<nav id="nav-single">
									<h3 class="assistive-text"><?php _e( 'Post navigation', 'twentyeleven' ); ?></h3>
									<!-- Restricted next and previous link (next in same year) as per codex https://codex.wordpress.org/Template_Tags/next_post_link#Examples -->
									<span class="nav-previous"><?php previous_post_link( '%link', __( 'Previous', 'twentyeleven' ), TRUE, $nua_exclude, 'years' ); ?></span>
									<span class="nav-next">| <?php next_post_link( '%link', __( 'Next', 'twentyeleven' ), TRUE, $nua_exclude, 'years' ); ?></span>

								</nav><!-- #nav-single -->
					<?php 
							} //end if current 
						} //end if is array
					?>

					
					<?php echo do_shortcode("[sce-get-featured]");?>
					<?php get_template_part( 'content', 'single' ); ?>

					<?php comments_template( '', true ); ?>

				<?php endwhile; // end of the loop. ?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>