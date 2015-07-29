<?php
/**
 * The main template file.
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
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

		<div id="primary">
			<div id="content" role="main">

			<?php if ( have_posts() ) : ?>

				<?php twentyeleven_content_nav( 'nav-above' ); ?>


					<?php //get_template_part( 'content', get_post_format() ); ?>

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
?><a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'twentyeleven' ), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark"><?php echo do_shortcode("[singlepic id=$id template=exchangethumb]");?></a><?php


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
		</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>