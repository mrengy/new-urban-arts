<?php
/**
 * The Sidebar containing the main widget area.
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

$options = twentyeleven_get_theme_options();
$current_layout = $options['theme_layout'];

if ( 'content' != $current_layout ) :
?>
		<div id="secondary" class="widget-area" role="complementary">
<aside id="custom_sidebar_widget-2" class="widget widget_custom_sidebar_widget">
<?php
$side = 102;
$e = get_post($side);
echo apply_filters("the_content",$e->post_content);

?>
</aside>
		</div><!-- #secondary .widget-area -->
<?php endif; ?>