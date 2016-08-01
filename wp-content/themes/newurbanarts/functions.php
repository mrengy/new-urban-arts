<?php

add_action('admin_menu','bhhidenag');
function bhhidenag()
{
remove_action( 'admin_notices', 'update_nag', 3 );
}


function is_ancestor($post_id) {
	global $wp_query;
	$ancestors = $wp_query->post->ancestors;
	if ( in_array($post_id, $ancestors) ) {
		$return = true;
	} else {
		$return = false;
	}
	return $return;
}


function get_parent($post_id) {
$pp = get_post($post_id);

if ($pp->post_parent)	{
//	$ancestors=get_post_ancestors($post->ID);
	$ancestors=get_post_ancestors($post_id);
	$root=count($ancestors)-1;
	$parent = $ancestors[$root];
} else {
//	$parent = $post->ID;
	$parent = $post_id;

}

	return $parent;

}

// clean up shortcode

function parse_shortcode_content( $content ) {

   /* Parse nested shortcodes and add formatting. */
    $content = trim( do_shortcode( shortcode_unautop( $content ) ) );

    /* Remove '' from the start of the string. */
    if ( substr( $content, 0, 4 ) == '' )
        $content = substr( $content, 4 );

    /* Remove '' from the end of the string. */
    if ( substr( $content, -3, 3 ) == '' )
        $content = substr( $content, 0, -3 );

    /* Remove any instances of ''. */
    $content = str_replace( array( '<p></p>' ), '', $content );
    $content = str_replace( array( '<p>  </p>' ), '', $content );

    return $content;
}

// move wpautop filter to AFTER shortcode is processed

remove_filter( 'the_content', 'wpautop' );
add_filter( 'the_content', 'wpautop' , 99);
add_filter( 'the_content', 'shortcode_unautop',100 );


// example of cleaning of the shortcode

function test_shortcode($atts, $content) {
	extract(shortcode_atts(array(
		'id' => '284882215' // default
	), $atts));

 	$content =  parse_shortcode_content($content);
        return $content;
}

add_shortcode('test','test_shortcode');


/* ADD CUSTOM META BOXES
********************************* */
function add_media_metaboxes() {
    add_meta_box('wpt_person_current', 'Currently Active', 'wpt_person_current', 'mentors', 'side', 'core');
    add_meta_box('wpt_person_current', 'Currently Active', 'wpt_person_current', 'fellows', 'side', 'core');
    add_meta_box('wpt_person_current', 'Currently Active', 'wpt_person_current', 'board-members', 'side', 'core');
    add_meta_box('wpt_person_current', 'Currently Active', 'wpt_person_current', 'staff', 'side', 'core');
}
add_action( 'add_meta_boxes', 'add_media_metaboxes' );


function wpt_person_current() {
    global $post;
    global $wpdb;
 
    // Noncename needed to verify where the data originated
    echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
    wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
 
    // Get the location data if its already been entered
    $c = get_post_meta($post->ID, '_current1', true);
    $s = ($c === 'yes' ? "checked='checked'" : '');
  
    // Echo out the field
	echo '<input type="checkbox" value="yes" name="_current1" '.$s.'> Active';
}

function wpt_save_media_meta($post_id, $post) {
 
    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if ( !wp_verify_nonce( $_POST['eventmeta_noncename'], plugin_basename(__FILE__) )) {
    return $post->ID;
    }
 
    // Is the user allowed to edit the post or page?
    if ( !current_user_can( 'edit_post', $post->ID ))
        return $post->ID;
 
    // OK, we're authenticated: we need to find and save the data
    // We'll put it into an array to make it easier to loop though.
 
    $events_meta['_current1'] = $_POST['_current1'];
 
    // Add values of $events_meta as custom fields
 
    foreach ($events_meta as $key => $value) { // Cycle through the $events_meta array!
        if( $post->post_type == 'revision' ) return; // Don't store custom data twice
        $value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
        if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
            update_post_meta($post->ID, $key, $value);
        } else { // If the custom field doesn't have a value
            add_post_meta($post->ID, $key, $value);
        }
        if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
    }
 
}
 
add_action('save_post', 'wpt_save_media_meta', 1, 2); // save the custom fields
/* END CUSTOM META BOXES
********************************* */



//extend custom side bars to support custom post types
function custom_sidebar_meta_box2() {
		add_meta_box('custom_sidebar', __('Sidebar', 'wpscp'), array('wp_custom_sidebar_plugin','custom_sidebar_dropdown_box'), 'mentors', 'side', 'low');
		add_meta_box('custom_sidebar', __('Sidebar', 'wpscp'), array('wp_custom_sidebar_plugin','custom_sidebar_dropdown_box'), 'fellows', 'side', 'low');
		add_meta_box('custom_sidebar', __('Sidebar', 'wpscp'), array('wp_custom_sidebar_plugin','custom_sidebar_dropdown_box'), 'board-members', 'side', 'low');
		add_meta_box('custom_sidebar', __('Sidebar', 'wpscp'), array('wp_custom_sidebar_plugin','custom_sidebar_dropdown_box'), 'staff', 'side', 'low');
		add_meta_box('custom_sidebar', __('Sidebar', 'wpscp'), array('wp_custom_sidebar_plugin','custom_sidebar_dropdown_box'), 'exchange', 'side', 'low');
}
add_action('admin_menu', 'custom_sidebar_meta_box2');


//remove media boxes
function remove_media_library_tab($tabs) {
    unset($tabs['type'],$tabs['type_url'],$tabs['gallery'],$tabs['library']);
    return $tabs;
}
add_filter('media_upload_tabs', 'remove_media_library_tab');

//remove meta box for "format"
function my_remove_meta_boxes() {
	remove_meta_box( 'formatdiv', 'post', 'side' );
}
add_action( 'admin_menu', 'my_remove_meta_boxes' );

//reorder mentor previous / next links to alphabetical order
function filter_next_post_sort($sort) {
    if (get_post_type($post) == 'mentor') {
        $sort = "ORDER BY p.post_title ASC LIMIT 1";
    }
    else{
        $sort = "ORDER BY p.post_date ASC LIMIT 1";
    }
    return $sort;
}
function filter_next_post_where($where) {
    global $post, $wpdb;

    //get posts with term (current year)
    $nested_posts_with_term = $wpdb->get_results("SELECT object_id FROM wp_term_relationships WHERE term_taxonomy_id = 42", ARRAY_N);

        //convert nested array into flat array
        $posts_with_term = array();
        foreach ($nested_posts_with_term as &$value) {
            array_push($posts_with_term, $value[0]);
        }

    if (get_post_type($post) == 'mentor') {
        return $wpdb->prepare("WHERE p.post_title > '%s' AND p.post_type = '". get_post_type($post)."' AND p.post_status = 'publish' AND FIND_IN_SET(p.post_id, ".$posts_with_term.") > 0",$post->post_title);
    }
    else{
        return $wpdb->prepare( "WHERE p.post_date > '%s' AND p.post_type = '". get_post_type($post)."' AND p.post_status = 'publish'", $post->post_date, $post->post_type );
    }
}

function filter_previous_post_sort($sort) {
    if (get_post_type($post) == 'mentor') {
        $sort = "ORDER BY p.post_title DESC LIMIT 1";
    }
    else{
        $sort = "ORDER BY p.post_date DESC LIMIT 1";
    }
    return $sort;
}
function filter_previous_post_where($where) {
    global $post, $wpdb;
    if (get_post_type($post) == 'mentor') {
        return $wpdb->prepare("WHERE p.post_title < '%s' AND p.post_type = '". get_post_type($post)."' AND p.post_status = 'publish'",$post->post_title);
    }
    else{
        return $wpdb->prepare( "WHERE p.post_date < '%s' AND p.post_type = '". get_post_type($post)."' AND p.post_status = 'publish'", $post->post_date, $post->post_type );
    }
}


add_filter('get_next_post_sort',   'filter_next_post_sort');
add_filter('get_next_post_where',  'filter_next_post_where');

add_filter('get_previous_post_sort',  'filter_previous_post_sort');
add_filter('get_previous_post_where', 'filter_previous_post_where');


?>