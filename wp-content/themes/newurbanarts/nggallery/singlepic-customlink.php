<?php 
/**
Template Page for the single pic

Follow variables are useable :

	$image : Contain all about the image 
	$meta  : Contain the raw Meta data from the image 
	$exif  : Contain the clean up Exif data from file
	$iptc  : Contain the clean up IPTC data from file 
	$xmp   : Contain the clean up XMP data  from file
	$db    : Contain the clean up META data from the database (should be imported during upload)

Please note : A Image resize or watermarking operation will remove all meta information, exif will in this case loaded from database 

 You can check the content when you insert the tag <?php var_dump($variable) ?>
 If you would like to show the timestamp of the image ,you can use <?php echo $exif['created_timestamp'] ?>
**/
?>
<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?><?php if (!empty ($image)) : ?>
<a href="<?php $p = $image->ngg_custom_fields["Page ID"]; echo get_permalink($p); ?>" title="<?php echo $image->linktitle ?>" <?php echo $image->thumbcode ?> ><img class="aligncenter customlink" src="<?php echo $image->imageURL ?>" alt="<?php echo $image->alttext ?>" title="<?php echo $image->alttext ?>" /></a>
<?php endif; ?>