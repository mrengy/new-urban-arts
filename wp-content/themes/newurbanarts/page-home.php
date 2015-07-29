<?php
/**
 * Template Name: Page - Home
 */


get_header(); ?>

<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_directory' ); ?>/style-home.css" />

<?php 
if (is_mobile()) { 
//get random image for page background
global $nggdb;
$gallery = $nggdb->get_gallery(9, 'sortorder', 'ASC', true, 0, 0);

// print_r($gallery);

$rand = array_rand ( $gallery, 1);
$imgURL= $gallery[$rand]->imageURL;	
//$imgURL = $gallery[1]->imageURL;

?>
<style>
#page {
	background:url(<?php bloginfo( 'stylesheet_directory' ); ?>/images/bg-home.png);
}
#main {
	background:url(<?php echo $imgURL;?>) no-repeat 0px 0px;
}
</style>
<?php } ?>
		<div id="primary">
			<div id="content" role="main">
			
			<?php echo do_shortcode("[sce-capitalhome]");?>
			<?php //echo do_shortcode("[nggallery id=3 template=home]");?>
			
				
				<?php while ( have_posts() ) : the_post(); ?>


<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="entry-content"></div><!-- .entry-content -->
</article><!-- #post-<?php the_ID(); ?> -->

					<?php //comments_template( '', true ); ?>

				<?php endwhile; // end of the loop. ?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_footer(); ?>