<?php

/*
require_once( pb_backupbuddy::plugin_path() . '/classes/live.php' );
pb_backupbuddy_live::generate_queue();
*/

//pb_backupbuddy::$classes['core']->get_stable_options( 'xxx', 'test', 5 );
//die();


// Tutorial
//pb_backupbuddy::load_script( 'jquery.joyride-2.0.3.js' );
//pb_backupbuddy::load_script( 'modernizr.mq.js' );
//pb_backupbuddy::load_style( 'joyride.css' );
// BEGIN TOUR.
?>
<ol id="pb_backupbuddy_tour" style="display: none;">
	<li data-class="duo-button">Click a backup type to start a backup now...</li>
	<li data-id="pb_backupbuddy_backup_locations_tab_local">Backups stored on this server are listed here... You can view remote backups by clicking the button at the bottom of this page.</li>
</ol>
<script>
  jQuery(window).load(function() {
    /*
    jQuery("#pb_backupbuddy_tour").joyride({
    	tipLocation: 'top',
    });
  */
  });
</script>
<?php
// END TOUR.

$time_start = time();

//echo 'A:' . ( $time_start - time() ) . '<br>';

pb_backupbuddy::$classes['core']->versions_confirm();

//echo 'B:' . ( $time_start - time() ) . '<br>';

$alert_message = array();
$preflight_checks = pb_backupbuddy::$classes['core']->preflight_check();
foreach( $preflight_checks as $preflight_check ) {
	if ( $preflight_check['success'] !== true ) {
		//$alert_message[] = $preflight_check['message'];
		pb_backupbuddy::disalert( $preflight_check['test'], $preflight_check['message'] );
	}
}
if ( count( $alert_message ) > 0 ) {
	//pb_backupbuddy::alert( implode( '<hr style="border: 1px dashed #E6DB55; border-bottom: 0;">', $alert_message ) );
}

//echo 'C:' . ( $time_start - time() ) . '<br>';



$view_data['backups'] = pb_backupbuddy::$classes['core']->backups_list( 'default' );

//echo 'D:' . ( $time_start - time() ) . '<br>';

pb_backupbuddy::load_view( '_backup-home', $view_data );
?>