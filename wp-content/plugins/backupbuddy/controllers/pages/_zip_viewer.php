<?php
if ( !current_user_can( 'activate_plugins' ) ) {
	die( 'Access Denied. Error 445543454754.' );
}


pb_backupbuddy::load_script( 'filetree.js' );
pb_backupbuddy::load_style( 'filetree.css' );

$serial = pb_backupbuddy::$classes['core']->get_serial_from_file( pb_backupbuddy::_GET( 'zip_viewer' ) );
?>


<script type="text/javascript">
	jQuery(document).ready(function() {
		
		
		
		jQuery('#pb_backupbuddy_file_browser').fileTree(
			{
				root: '',
				multiFolder: false,
				script: '<?php echo pb_backupbuddy::ajax_url( 'file_tree' ); ?>&serial=<?php echo $serial; ?>&zip_viewer=<?php echo pb_backupbuddy::_GET( 'zip_viewer' ); ?>'
			},
			function(file) {
				if ( ( file == 'wp-config.php' ) ) {
					alert( '<?php _e('You cannot exclude wp-config.php.', 'it-l10n-backupbuddy' );?>' );
				} else {
					jQuery('#pb_backupbuddy_excludes').val( file + "\n" + jQuery('#pb_backupbuddy_excludes').val() );
				}
			},
			function(directory) {
				if ( ( directory == '/wp-content/' ) || ( directory == '/wp-content/uploads/' ) || ( directory == '<?php echo pb_backupbuddy::$options['backup_directory']; ?>' ) || ( directory == '/wp-content/uploads/backupbuddy_temp/' ) ) {
					alert( '<?php _e('You cannot exclude /wp-content/, /wp-content/uploads/, or BackupBuddy directories.  However, you may exclude subdirectories within these. BackupBuddy directories such as backupbuddy_backups are automatically excluded and cannot be added to exclusion list.', 'it-l10n-backupbuddy' );?>' );
				} else {
					jQuery('#pb_backupbuddy_excludes').val( directory + "\n" + jQuery('#pb_backupbuddy_excludes').val() );
				}
			}
		);
		
		
		
		// Show options on hover.
		jQuery( '.jqueryFileTree > li a' ).live('mouseover mouseout', function(event) {
			if ( event.type == 'mouseover' ) {
				jQuery(this).children( '.pb_backupbuddy_treeselect_control' ).css( 'visibility', 'visible' );
			} else {
				jQuery(this).children( '.pb_backupbuddy_treeselect_control' ).css( 'visibility', 'hidden' );
			}
		});
		
		
		
	});
</script>

<?php

// Sanitize a bit.
$_GET['zip_viewer'] = str_replace( '\\', '', pb_backupbuddy::_GET( 'zip_viewer' ) );
$_GET['zip_viewer'] = str_replace( '/', '', pb_backupbuddy::_GET( 'zip_viewer' ) );


// Set up zipbuddy.
if ( !isset( pb_backupbuddy::$classes['zipbuddy'] ) ) {
	require_once( pb_backupbuddy::plugin_path() . '/lib/zipbuddy/zipbuddy.php' );
	pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( pb_backupbuddy::$options['backup_directory'] );
}

pb_backupbuddy::$ui->title( 'View Backup Contents' );

// Get file listing.
$results = get_transient( 'pb_backupbuddy_zipviewer-' . $serial );
if ( $results === false ) {
	//$start_memory = memory_get_usage();
	$results = pb_backupbuddy::$classes['zipbuddy']->get_file_list( pb_backupbuddy::$options['backup_directory'] . pb_backupbuddy::_GET( 'zip_viewer' ) );
	//echo ( memory_get_usage() - $start_memory ) / 1024;
	set_transient( 'pb_backupbuddy_zipviewer-' . $serial, $results, ( 60*60 ) );
}


if ( $results === false ) {
	pb_backupbuddy::alert( __( 'Error #628855. Error reading ZIP file.', 'it-l10n-backupbuddy' ), true );
} else {
	
	
	// Prettify data.
	echo '<div class="jQueryOuterTree" id="pb_backupbuddy_file_browser" style="position: absolute; width: 95%; height: 100%;">
		<ul class="jqueryFileTree"></ul></div>';
}


// Give a link back to backups.
echo '<br><br><br><br style="clear: both;">';
echo pb_backupbuddy::$ui->button( pb_backupbuddy::page_url(), '&larr; back to backups' );
?>




<style type="text/css">
	/* Core Styles - USED BY DIRECTORY EXCLUDER */
	.jqueryFileTree LI.directory { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/directory.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.expanded { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/folder_open.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.file { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/file.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.wait { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/spinner.gif') 6px 12px no-repeat; }
	/* File Extensions*/
	.jqueryFileTree LI.ext_3gp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_afp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_afpa { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_asp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_aspx { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_avi { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_bat { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/application.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_bmp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_c { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_cfm { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_cgi { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_com { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/application.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_cpp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_css { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/css.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_doc { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/doc.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_exe { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/application.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_gif { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_fla { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/flash.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_h { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_htm { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/html.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_html { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/html.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_jar { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/java.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_jpg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_jpeg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_js { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/script.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_lasso { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_log { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/txt.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_m4p { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/music.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_mov { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_mp3 { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/music.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_mp4 { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_mpg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_mpeg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_ogg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/music.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_pcx { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_pdf { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/pdf.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_php { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/php.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_png { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_ppt { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ppt.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_psd { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/psd.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_pl { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/script.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_py { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/script.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_rb { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ruby.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_rbx { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ruby.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_rhtml { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ruby.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_rpm { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/linux.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_ruby { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ruby.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_sql { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/db.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_swf { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/flash.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_tif { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_tiff { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_txt { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/txt.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_vb { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_wav { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/music.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_wmv { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_xls { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/xls.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_xml { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 12px no-repeat; }
	.jqueryFileTree LI.ext_zip { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/zip.png') 6px 12px no-repeat; }
	
	
	
	
	
	.jQueryOuterTree {
		padding: 8px;
		width: auto;
	}
	.jqueryFileTree li a {
		font-size: 13px;
	}
	
	.pb_backupbuddy_fileinfo {
		font-size: 10px;
	}
	
	.pb_backupbuddy_fileinfo .description {
		font-size: 10px;
	}
	
	ul.jqueryFileTree a {
		padding: 10px;
		padding-left: 28px;
		padding-right: 6px;
	}
	
	.pb_backupbuddy_col1 {
		display: inline-block;
		position: absolute;
		right: 300px;
	}
	.pb_backupbuddy_col2 {
		display: inline-block;
		position: absolute;
		right: 50px;
	}
	
	.pb_backupbuddy_treeselect_control > img {
		margin-top: 8px;
	}
</style>
