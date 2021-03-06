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

					echo('<pre>posts_with_term id of 42<br/>');
					print_r($posts_with_term);
					echo('</pre>');
				?>

				<?php while ( have_posts() ) : the_post(); ?>
					<?php
						//not sure about this
						$nua_current_post_id = get_the_ID();

						echo('<pre>current post id <br />');
						print_r($nua_current_post_id);
						echo('</pre>');

						echo('<pre>post terms for years<br />');
 +						print_r(wp_get_post_terms($nua_current_post_id,'years'));
  						echo('</pre>');

						echo('<pre>has term id 42?<br />');
						if(has_term( 42, 'years', $nua_current_post_id)){
							print('yes');
						} else{
							print('no');
						}
						echo('</pre>');

						$current_post_if_has_term = $wpdb->get_results("SELECT object_id FROM wp_term_relationships WHERE FIND_IN_SET(".$nua_current_post_id.", ".$posts_with_term.") > 0");

	        			echo('<pre>should show current_post_if_has_term id of 42<br/>');
						print_r($current_post_if_has_term);
						echo('</pre>');

					 	// only show previous / next navigation if this item is 'currently active'
						if (is_array(get_post_custom_values('_current1'))){
							if (in_array('yes', get_post_custom_values('_current1'))) { 
					?>
								<nav id="nav-single">
									<h3 class="assistive-text"><?php _e( 'Post navigation', 'twentyeleven' ); ?></h3>
									<!-- Restricted next and previous link (next in same year) as per codex https://codex.wordpress.org/Template_Tags/next_post_link#Examples -->
									<span class="nav-previous"><?php previous_post_link( '%link', __( 'Previous', 'twentyeleven' )); ?></span>
									<span class="nav-next">| <?php next_post_link( '%link', __( 'Next', 'twentyeleven' )); ?></span>

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