<?php 
/**
Template Page for the gallery overview

Follow variables are useable :

	$gallery     : Contain all about the gallery
	$images      : Contain all images, path, title
	$pagination  : Contain the pagination content

 You can check the content when you insert the tag <?php var_dump($variable) ?>
 If you would like to show the timestamp of the image ,you can use <?php echo $exif['created_timestamp'] ?>
**/
?>
<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?><?php if (!empty ($gallery)) : ?>

<style type="text/css">
#page .slideshow {
	position:relative;
	z-index:90;
	margin: 0;
	text-align: left;
}
#page .entry-content img {
max-width: 100%;
}
</style>

<!-- include Cycle plugin -->
<script type="text/javascript" src=" <?php echo bloginfo('stylesheet_directory'); ?>/scripts/jquery.cycle.all.2.74.js"></script>
<!--  initialize the slideshow when the DOM is ready -->
<script type="text/javascript">
jQuery(document).ready(function() {
    jQuery('.slideshow').cycle({
		fx: 'fade', // choose your transition type, ex: fade, scrollUp, shuffle, etc...
		speed:1000,
		timeout:10000
	});
});
</script>

<div class="slideshow">
	<?php foreach ( $images as $image ) : ?>
				<?php if ( !$image->hidden ) { ?>
				<img title="<?php echo $image->alttext ?>" alt="<?php echo $image->alttext ?>" src="<?php echo $image->imageURL ?>" />
				<?php } ?>
 	<?php endforeach; ?>
  	
</div>

<?php endif; ?>