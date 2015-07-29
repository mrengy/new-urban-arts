<?php
/**
 * The template for displaying Archive pages.
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
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

		<section id="primary" class="exchange-archive">
		
			<div id="content" role="main">
			

			<?php
			
			if ( have_posts() ) : ?>

<?php
		$term =	$wp_query->queried_object;
echo '<h3 class="term">'.$term->name.'</h3>';
		?>
				<?php twentyeleven_content_nav( 'nav-above' ); ?>


				<?php /* Start the Loop */ ?>
			<article id="post-<?php the_ID(); ?>">
				<?php 
				$i = 1; 
				while ( have_posts() ) : the_post(); 
				$last = ($i%2 ? '' : 'last');
				?>


			<div class="exgroup <?php echo $last;?>">
				<a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'twentyeleven' ), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark" ><?php echo do_shortcode("[sce-exchangethumb]");?></a><p><a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'twentyeleven' ), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark" ><?php the_title(); ?></a></p>
				</div>
			<?php
			
if($i==1) {
 $i++;
} else {
  echo "<div style='clear:both;'></div>";
  $i=1;
}
?>
				<?php endwhile; ?>
			</article><!-- #post-<?php the_ID(); ?> -->

				<?php twentyeleven_content_nav( 'nav-below' ); ?>

			<?php else : ?>

				<article id="post-0" class="post no-results not-found">
					<header class="entry-header">
						<h1 class="entry-title"><?php _e( 'Nothing Found', 'twentyeleven' ); ?></h1>
					</header><!-- .entry-header -->

					<div class="entry-content">
						<p><?php _e( 'Apologies, but no results were found for the requested archive. Perhaps searching will help find a related post.', 'twentyeleven' ); ?></p>
						<?php get_search_form(); ?>
					</div><!-- .entry-content -->
				</article><!-- #post-0 -->

			<?php endif; ?>
				<div style="clear:both;"></div>
			</div><!-- #content -->
		</section><!-- #primary -->

<?php get_sidebar('exchange'); ?>
<?php get_footer(); ?>