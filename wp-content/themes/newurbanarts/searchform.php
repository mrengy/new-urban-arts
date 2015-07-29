<?php
/**
 * The template for displaying search forms in Twenty Eleven
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */
?>
<style>
.searchblock {
	margin: 0;
	padding: 20px 0 40px 0;	
}
#secondary input#s {
font: 11px/13px Arial, Helvetica, sans-serif;
color: #666;
border: none;
padding: 8px;
margin: 0;
background: #E6E6E6;
width: 135px;
height: 3px;
float: left;
-moz-box-shadow: inset 0 0px 0px rgba(0,0,0,0);
-webkit-box-shadow: inset 0 0px 0px transparent;
box-shadow: inset 0 0px 0px transparent;
}
#secondary input[type="text"] {
background: #E6E6E6;
-moz-box-shadow: inset 0 0px 0px rgba(0,0,0,0);
-webkit-box-shadow: inset 0 0px 0px rgba(0, 0, 0, 0);
box-shadow: inset 0 0px 0px rgba(0, 0, 0, 0);
border: 0px solid #DDD;
color: #888;
}
#secondary input#searchsubmit {
display: block;
float:left;
padding:2px 0px 0px 3px;
}
</style>
<div class="searchblock">
<p class="sidebar-title">Search</p>
	<form method="get" id="searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>"><input type="text" class="field" name="s" id="s" placeholder="<?php esc_attr_e( 'Search', 'twentyeleven' ); ?>" /><input type="image" src="<?php bloginfo('stylesheet_directory');?>/images/go.jpg" name="submit" id="searchsubmit" value="<?php esc_attr_e( 'Search', 'twentyeleven' ); ?>" /><div style="clear:both;"></div>
	</form>
</div>