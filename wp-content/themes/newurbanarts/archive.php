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
#mainnav .cssmb ul li a.nav-whatsnew {
	color: white;
	text-decoration: none;
	background-color: #f58425;
}
</style>

<?php echo do_shortcode("[sce-page-header]");?>

		<section id="primary">
			<div id="content" role="main">

			<?php if ( have_posts() ) : ?>

				<header class="page-header">
					<h1 class="page-title">
						<?php if ( is_day() ) : ?>
							<?php printf( __( 'Daily Archives: %s', 'twentyeleven' ), '<span>' . get_the_date() . '</span>' ); ?>
						<?php elseif ( is_month() ) : ?>
							<?php printf( __( 'Archives: %s', 'twentyeleven' ), '<span>' . get_the_date( _x( 'F Y', 'monthly archives date format', 'twentyeleven' ) ) . '</span>' ); ?>
						<?php elseif ( is_year() ) : ?>
							<?php printf( __( 'Yearly Archives: %s', 'twentyeleven' ), '<span>' . get_the_date( _x( 'Y', 'yearly archives date format', 'twentyeleven' ) ) . '</span>' ); ?>
						<?php else : ?>
							<?php _e( 'Blog Archives', 'twentyeleven' ); ?>
						<?php endif; ?>
					</h1>
				</header>

				<?php twentyeleven_content_nav( 'nav-above' ); ?>

<?php
$i=0;
?>

					<?php
						/* Include the Post-Format-specific template for the content.
						 * If you want to overload this in a child theme then include a file
						 * called content-___.php (where ___ is the Post Format name) and that will be used instead.
						 */
//get_template_part( 'content', get_post_format() );


 
?>
	<article id="post-<?php the_ID(); ?>" <?php //post_class(); ?>>
		<div class="entry-content">

				<?php while ( have_posts() ) : the_post(); 
				
 $image = get_post_thumbnail_id( $post->ID ); 
  $id = str_replace("ngg-","",$image);
  $id = trim($id);
  $last = ($i==0 ? '' : 'last');
  $id = ($id !=='' ? $id : 43);
				
?>
	

<?php 
echo "<div class='exgroup ".$last."'>";
$d = $post->post_date;
echo "<span class='whatsdate'>".date("l, j F Y",strtotime($d))."</span>";
echo do_shortcode("[singlepic id=$id template=exchangethumb]");


$categories = get_the_category($ppost->ID);
if($categories){
echo "<span class='whatscat'>";
$c=0;
	foreach($categories as $category) {
if($c>0 && $c < count($categories)) {
echo ", ";
}
echo $category->cat_name;

$c++;
     }
echo "</span><br>";
}
?>
			
			<p><a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'twentyeleven' ), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark"><?php the_title(); ?></a></p>
			
<?php

echo "</div>";

if($i==0) {
 $i++;
} else {
  echo "<div style='clear:both;'></div>";
  $i=0;
}
?>
				<?php endwhile; ?>
		</div><!-- .entry-content -->
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

			</div><!-- #content -->
		</section><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>