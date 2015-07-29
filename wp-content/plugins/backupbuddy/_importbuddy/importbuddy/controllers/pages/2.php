<?php
if ( ! defined( 'PB_IMPORTBUDDY' ) || ( true !== PB_IMPORTBUDDY ) ) {
	die( '<html></html>' );
}

Auth::require_authentication(); // Die if not logged in.

$data = array(
	'step'		=>		'2',
);



pb_backupbuddy::set_greedy_script_limits( true );



parse_options();







/**
 *	parse_options()
 *
 *	Parses various submitted options and settings from step 1.
 *
 *	@return		null
 */
function parse_options() {
	// Set advanced debug options if user set any.
	if ( ( isset( $_POST['skip_files'] ) ) && ( $_POST['skip_files'] == 'on' ) ) { pb_backupbuddy::$options['skip_files'] = true; }
	if ( ( isset( $_POST['skip_htaccess'] ) ) && ( $_POST['skip_htaccess'] == 'on' ) ) { pb_backupbuddy::$options['skip_htaccess'] = true; }
	if ( ( isset( $_POST['force_compatibility_medium'] ) ) && ( $_POST['force_compatibility_medium'] == 'on' ) ) { pb_backupbuddy::$options['force_compatibility_medium'] = true; }
	if ( ( isset( $_POST['force_compatibility_slow'] ) ) && ( $_POST['force_compatibility_slow'] == 'on' ) ) { pb_backupbuddy::$options['force_compatibility_slow'] = true; }
	if ( ( isset( $_POST['force_high_security'] ) ) && ( $_POST['force_high_security'] == 'on' ) ) { pb_backupbuddy::$options['force_high_security'] = true; }
	if ( ( isset( $_POST['show_php_warnings'] ) ) && ( $_POST['show_php_warnings'] == 'on' ) ) { pb_backupbuddy::$options['show_php_warnings'] = true; }
	if ( ( isset( $_POST['file'] ) ) && ( $_POST['file'] != '' ) ) { pb_backupbuddy::$options['file'] = $_POST['file']; }
	if ( ( isset( $_POST['log_level'] ) ) && ( $_POST['log_level'] != '' ) ) { pb_backupbuddy::$options['log_level'] = $_POST['log_level']; }
	
	// Set ZIP id (aka serial).
	if ( ! isset( pb_backupbuddy::$options['file'] ) ) {
		die( 'No backup zip file specified to process. Go back and make sure you selected a ZIP file to extract and restore on Step 1.' );
	}
	pb_backupbuddy::$options['zip_id'] = pb_backupbuddy::$classes['core']->get_serial_from_file( pb_backupbuddy::$options['file'] );
}



/* generate_maintenance_files()
 *
 * Generated a .maintenance file to inform WordPress not to allow access to the site.
 * This file is removed on Step 5.
 *
 */
function generate_maintenance_file() {
	if ( ! file_exists( ABSPATH . '.maintenance' ) ) {
		$maintenance_result = @file_put_contents( ABSPATH . '.maintenance', "<?php die( 'Site undergoing maintenance.' ); ?>" );
		if ( false === $maintenance_result ) {
			pb_backupbuddy::status( 'warning', '.maintenance file unable to be generated to prevent viewing partially migrated site. This is not a fatal error.' );
		} else {
			pb_backupbuddy::status( 'details', '.maintenance file generated to prevent viewing partially migrated site.' );
		}
	} else {
		pb_backupbuddy::status( 'details', '.maintenance file already exists. Skipping creation.' );
	}
} // end generate_maintenance_file().


/**
 *	extract()
 *
 *	Extract backup zip file.
 *
 *	@return		array		True if the extraction was a success OR skipping of extraction is set.
 */
function extract_files() {
	if ( true === pb_backupbuddy::$options['skip_files'] ) { // Option to skip all file updating / extracting.
		pb_backupbuddy::status( 'message', 'Skipped extracting files based on debugging options.' );
		return true;
	} else {
		pb_backupbuddy::set_greedy_script_limits();
		
		pb_backupbuddy::status( 'message', 'Unzipping into `' . ABSPATH . '`' );
		
		$backup_archive = ABSPATH . pb_backupbuddy::$options['file'];
		$destination_directory = ABSPATH;
		
		// Set compatibility mode if defined in advanced options.
		$compatibility_mode = false; // Default to no compatibility mode.
		if ( pb_backupbuddy::$options['force_compatibility_medium'] != false ) {
			$compatibility_mode = 'ziparchive';
		} elseif ( pb_backupbuddy::$options['force_compatibility_slow'] != false ) {
			$compatibility_mode = 'pclzip';
		}
		
		// Zip & Unzip library setup.
		require_once( ABSPATH . 'importbuddy/lib/zipbuddy/zipbuddy.php' );
		pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( ABSPATH, array(), 'unzip' );
		
		// Extract zip file & verify it worked.
		if ( true !== ( $result = pb_backupbuddy::$classes['zipbuddy']->unzip( $backup_archive, $destination_directory, $compatibility_mode ) ) ) {
			pb_backupbuddy::status( 'error', 'Failed unzipping archive.' );
			pb_backupbuddy::alert( 'Failed unzipping archive.', true );
			return false;
		} else { // Reported success; verify extraction.
			
			// Handle meta data in comment.
			$comment = pb_backupbuddy::$classes['zipbuddy']->get_comment( $backup_archive );
			$comment = pb_backupbuddy::$classes['core']->normalize_comment_data( $comment );
			$comment_text = print_r( $comment, true );
			$comment_text = str_replace( array( "\n", "\r" ), '; ', $comment_text );
			pb_backupbuddy::status( 'details', 'Backup meta data: `' . $comment_text . '`.' );
			
			// Use meta to find DAT file (if possible). BB v3.3+.
			$dat_file = '';
			if ( '' != $comment['dat_path'] ) { // Specific DAT location is known.
				if ( file_exists( ABSPATH . $comment['dat_path'] ) ) {
					$dat_file = ABSPATH . $comment['dat_path'];
					pb_backupbuddy::status( 'details', 'DAT file found based on meta path.' );
				}
			}
			
			// Deduce DAT file location based on backup filename. BB < v3.3.
			if ( '' == $dat_file ) {
				pb_backupbuddy::status( 'details', 'Scanning for DAT file based on backup file name.' );
				$_backupdata_file = ABSPATH . 'wp-content/uploads/temp_' . pb_backupbuddy::$options['zip_id'] . '/backupbuddy_dat.php'; // OLD 1.x FORMAT. Full backup dat file location.
				$_backupdata_file_new = ABSPATH . 'wp-content/uploads/backupbuddy_temp/' . pb_backupbuddy::$options['zip_id'] . '/backupbuddy_dat.php'; // Full backup dat file location
				$_backupdata_file_dbonly = ABSPATH . 'backupbuddy_dat.php'; // DB only dat file location
				
				if ( file_exists( $_backupdata_file ) ) {
					$dat_file = $_backupdata_file;
				} elseif ( file_exists( $_backupdata_file_new ) ) {
					$dat_file = $_backupdata_file_new;
				} elseif( file_exists( $_backupdata_file_dbonly ) ) {
					$dat_file = $_backupdata_file_dbonly;
				} else { // DAT not found.
					$error_message = 'Error #9004: Key files missing. The unzip process reported success but the backup data file, backupbuddy_dat.php was not found in the extracted files. The unzip process either failed to fully complete, you renamed the backup ZIP file (rename it back to correct this), or the zip file is not a proper BackupBuddy backup.';
					pb_backupbuddy::status( 'error', $error_message );
					pb_backupbuddy::alert( $error_message, true, '9004' );
					return false;
				}
				pb_backupbuddy::status( 'details', 'Successfully found DAT file based on backup file name: `' . $dat_file . '`.' );
			}
			
			// Get DAT file contents & save into options..
			pb_backupbuddy::$options['dat_file'] = pb_backupbuddy::$classes['import']->get_dat_file_array( $dat_file );
			pb_backupbuddy::save();
			
			// Report success.
			pb_backupbuddy::status( 'details', 'Success extracting Zip File "' . ABSPATH . pb_backupbuddy::$options['file'] . '" into "' . ABSPATH . '".' );
			return true;
		}
	}
} // End extract_files().



/*	rename_htaccess_temp()
 *	
 *	Renames .htaccess to .htaccess.bb_temp until last ImportBuddy step to avoid complications.
 *	
 *	@return		null
 */
function rename_htaccess_temp() {
	
	if ( !file_exists( ABSPATH . '.htaccess' ) ) {
		pb_backupbuddy::status( 'details', 'No .htaccess file found. Skipping temporary file rename.' );
	}
	
	$result = @rename( ABSPATH . '.htaccess', ABSPATH . '.htaccess.bb_temp' );
	if ( $result === true ) { // Rename succeeded.
		pb_backupbuddy::status( 'message', 'Renamed `.htaccess` file to `.htaccess.bb_temp` until final ImportBuddy step.' );
	} else { // Rename failed.
		pb_backupbuddy::status( 'warning', 'Unable to rename `.htaccess` file to `.htaccess.bb_temp`. Your file permissions may be too strict. You may wish to manually rename this file and/or check permissions before proceeding.' );
	}
	
} // End rename_htaccess_temp().







pb_backupbuddy::load_view( 'html_2', $data );
?>