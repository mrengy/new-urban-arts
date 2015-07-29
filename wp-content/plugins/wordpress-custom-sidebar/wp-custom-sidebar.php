<?php
/*
Plugin Name: WordPress Custom Sidebar
Plugin URI: http://www.destio.de/tools/wordpress-custom-sidebar/
Description: With this plugin you can handle sidebar contents like posts and assign them from a dropdown menu.
Author: Designstudio, Philipp Speck
Version: 2.2
Author URI: http://www.destio.de/
*/

if ( !class_exists ('wp_custom_sidebar_plugin')) {
	class wp_custom_sidebar_plugin {
	
	function custom_sidebar_textdomain() {
		load_plugin_textdomain( 'wpcsp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	
	function custom_sidebar_addcolumn() {
		global $wpdb;
		if (false === $wpdb->query("SELECT post_sidebar FROM $wpdb->posts LIMIT 0")) {
			$wpdb->query("ALTER TABLE $wpdb->posts ADD COLUMN post_sidebar varchar(200)");
		}
	}
	 
	function custom_sidebar_insert_post($pID) {
		global $wpdb;
		extract($_POST);
		$wpdb->query("UPDATE $wpdb->posts SET post_sidebar = '$post_sidebar' WHERE ID = $pID");
	}

	function custom_sidebar_post_type() {
		$labels = array(
			'name' => __('Sidebars', 'wpcsp'),
			'singular_name' => __('Sidebar', 'wpcsp'),
			'add_new' => __('Add New', 'wpcsp'),
			'add_new_item' => __('Add New Sidebar', 'wpcsp'),
			'edit_item' => __('Edit Sidebar', 'wpcsp'),
			'new_item' => __('New Sidebar', 'wpcsp'),
			'all_items' => __('All Sidebars', 'wpcsp'),
			'view_item' => __('View Sidebars', 'wpcsp'),
			'search_items' => __('Search Sidebars', 'wpcsp'),
			'not_found' =>  __('No Sidebars found', 'wpcsp'),
			'not_found_in_trash' => __('No Sidebars found in Trash', 'wpcsp'), 
			'parent_item_colon' => '',
			'menu_name' =>  __('Sidebars', 'wpcsp')
			);
		
		$args = array(
			'labels' => $labels,
			'show_ui' => true,
			'public' => false,
			'publicly_queryable' => false,
			'capability_type' => 'post',
			'hierarchical' => true,
			'menu_position' => 20,
			'menu_icon' => plugins_url( 'sidebar.png', __FILE__ ),
			'supports' => array( 'title', 'editor', 'revisions' ),
		);
	
		register_post_type('sidebar',$args);
	}
 
	function custom_sidebar_dropdown_box($post) {
		global $post;
		$post_sidebar = $post->post_sidebar;
			$sidebars = wp_dropdown_pages(array(
			'post_type' => 'sidebar',
			'selected' => $post->post_sidebar, 
			'name' => 'post_sidebar',
			'sort_column' => 'menu_order, post_date',
			));
			?>
			<p><a href="<?php echo site_url(); ?>/wp-admin/post-new.php?post_type=sidebar"><?php _e('Create new sidebar', 'wpcsp') ?></a></p>
			<?php
	}
	
	function custom_sidebar_meta_box() {
		add_meta_box('custom_sidebar', __('Sidebar', 'wpscp'), array('wp_custom_sidebar_plugin','custom_sidebar_dropdown_box'), 'post', 'side', 'low');
		add_meta_box('custom_sidebar', __('Sidebar', 'wpscp'), array('wp_custom_sidebar_plugin','custom_sidebar_dropdown_box'), 'page', 'side', 'low');
	}
		
	function query_custom_sidebar() {
			global $post;
			$parent = get_post($post->post_parent);
					
			if ( is_single() || is_page() && $post->post_sidebar ) {
				$sidebar_id = $post->post_sidebar;
				$args = array(
					'post_type' => 'sidebar',
					'p' => $sidebar_id
					);
			}
			
			elseif ( is_single() || is_page() && $parent->post_sidebar ) {			
				$sidebar_id = $parent->post_sidebar;
				$args = array(
					'post_type' => 'sidebar',
					'p' => $sidebar_id
					);
			}
			
			else {
			$args = array(
				'post_type' => 'sidebar',
				'posts_per_page' => 1,
				'order' => 'ASC',			
				);
			}		
	
			// The Loop
			$the_query = new WP_Query( $args );
			while ( $the_query->have_posts() ) : $the_query->the_post();
				the_content();
			endwhile;
			// Reset Post Data
			wp_reset_postdata();
	}
	
	function custom_sidebar_register_widget() {
		register_widget( 'custom_sidebar_widget' );
	}

	function custom_sidebar_default_entry() {
		global $wpdb;
		$args = array(
			'post_type' => 'sidebar',
			'post_title' => 'Default Sidebar',
			'post_content' => '<h2>WordPress Custom Sidebar 2.1</h2>This plugin is a development of <a title="Designstudio, Philipp Speck" href="http://www.destio.de" target="_blank">Designstudio, Philipp Speck &raquo;</a>',
			'post_status' => 'publish'
			);	

		$columns = get_posts('post_type=sidebar');
		// check if not exsist then insert default entry
		if (empty($columns)) {
				wp_insert_post($args);
		}
	} // end function custom_sidebar_default_entry
	
	} // end class wp_custom_sidebar_plugin
} // end class_exists check

if ( !class_exists ('custom_sidebar_widget')) {
	class custom_sidebar_widget extends WP_Widget {
	
		function custom_sidebar_widget() {
			$options = array( 'description' => __('To assign custom sidebars inside post or pages drag the widget to an area of your choice.', 'wpcsp') );
			parent::WP_Widget( false, __('Custom Sidebar', 'wpcsp'), $options );
		}
	
		function widget( $args, $instance ) {
			extract( $args );
			$title = apply_filters( 'widget_title', $instance['title'] );
			echo $before_widget;
			if ( $title )
			echo $before_title . $title . $after_title;
			wp_custom_sidebar_plugin::query_custom_sidebar();
			echo $after_widget;
		}
		
	} // close custom_sidebar_widget class
} // end class_exists check

add_action('init', array('wp_custom_sidebar_plugin','custom_sidebar_textdomain'));
add_action('init', array('wp_custom_sidebar_plugin','custom_sidebar_addcolumn'));
add_action('init', array('wp_custom_sidebar_plugin','custom_sidebar_post_type'));
add_action('plugins_loaded', array('wp_custom_sidebar_plugin','custom_sidebar_default_entry'));
add_action('widgets_init', array('wp_custom_sidebar_plugin','custom_sidebar_register_widget'));
add_action('admin_menu', array('wp_custom_sidebar_plugin','custom_sidebar_meta_box'));
add_action('wp_insert_post', array('wp_custom_sidebar_plugin','custom_sidebar_insert_post'));
?>