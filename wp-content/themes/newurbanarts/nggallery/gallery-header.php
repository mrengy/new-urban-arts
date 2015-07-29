<?php 

$pt = get_post_type();


if(is_page()) { //get parent and get graphic from gallery
	$t = get_the_ID();
	$p = get_parent($t);

	if($t == 33) { //campaign
		$p = $t;	
	}

} else if(is_single() ) {

	if($pt == "mentors" | $pt == "staff" | $pt == "board-members" | $pt == "fellows" ) {
		$p = 19;
	} else if($pt == "exchange") {
		$p = 27;
	} else {
		$p = 25;	
	}
	
} else if(is_search() ) {
	$p=25;

} else {
	$p = 25;	
	if($pt == "exchange") { //this is for exchange group items
		$p = 27;
	}
}

if (!defined ('ABSPATH')) die ('No direct access allowed'); ?><?php if (!empty ($gallery)) : ?>

	<?php foreach ( $images as $image ) : ?>
		<?php if($image->ngg_custom_fields["Parent ID"] == $p) { ?>
<div id="pageheader">
	<img src="<?php echo $image->imageURL ?>" width="860" height="271">
</div>
	
		<?php } ?>
 	<?php endforeach; ?>
<?php endif; ?>