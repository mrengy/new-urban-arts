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
#mainnav .cssmb ul li a.nav-exchange {
	color: white;
	text-decoration: none;
	background-color: #f26f28;
}
</style>

<?php echo do_shortcode("[sce-page-header]");?>


		<div id="primary" class="exchange">
			<div id="content" role="main">

				<?php while ( have_posts() ) : the_post(); ?>

					<nav id="nav-single" style="display:none;">
						<h3 class="assistive-text"><?php _e( 'Post navigation', 'twentyeleven' ); ?></h3>
						<span class="nav-previous"><?php previous_post_link( '%link', __( 'Previous', 'twentyeleven' ) ); ?></span>
						<span class="nav-next">| <?php next_post_link( '%link', __( 'Next', 'twentyeleven' ) ); ?></span>
					</nav><!-- #nav-single -->

<?php
$p = $post->ID;
$terms = wp_get_post_terms( $p, 'exchange-group');
foreach ($terms as $term) {
$url = get_term_link( $term->slug, $term->taxonomy );
echo "<h3 class='term'><a href='".$url."'>".$term->name."</a></h3>";
//print_r($term);


}
?>
					<?php echo do_shortcode("[sce-get-featured]");?>
					<?php get_template_part( 'content', 'exchange' ); ?>

					<?php //comments_template( '', true ); ?>

				<?php endwhile; // end of the loop. ?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_sidebar('exchange'); ?>
<?php get_footer(); ?>