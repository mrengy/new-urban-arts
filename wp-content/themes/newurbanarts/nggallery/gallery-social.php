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

<div id="socialicons">

	<?php foreach ( $images as $image ) : ?>
	
	<div id="ngg-image-<?php echo $image->pid ?>">
			<a href="<?php echo $image->description ?>" target="_blank" title="<?php echo $image->alttext ?>" class="socialthumb" style="background-image:url(<?php echo $image->imageURL ?>);">
			</a>
	</div>
	

 	<?php endforeach; ?>
  	
</div>

<?php endif; ?>