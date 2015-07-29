<?php

class pb_backupbuddy_ajax extends pb_backupbuddy_ajaxcore {
	
	
	
	// Used for recent backup listing.
	public function backup_step_status() {
		$serial = pb_backupbuddy::_GET( 'serial' );
		$serial = str_replace( '/\\', '', $serial );
		pb_backupbuddy::load();
		pb_backupbuddy::$ui->ajax_header();
		
		echo '<h3>Backup Process Technical Details</h3>';
		echo '<b>Step Details:</b><br>';
		echo '<textarea style="width: 100%; height: 120px;" wrap="off">';
		
		require_once( pb_backupbuddy::plugin_path() . '/classes/fileoptions.php' );
		$backup_options = new pb_backupbuddy_fileoptions( pb_backupbuddy::$options['log_directory'] . 'fileoptions/' . $serial . '.txt' );
		if ( true !== ( $result = $backup_options->is_ok() ) ) {
			pb_backupbuddy::alert( __('Unable to access fileoptions data file.', 'it-l10n-backupbuddy' ) . ' Error: ' . $result );
		}
		
		foreach( $backup_options->options['steps'] as $step ) {
			if ( $step['function'] == 'send_remote_destination' ) {
				foreach( $step['args'] as $destination ) {
					echo "Remote destinations for this backup:\n`" . pb_backupbuddy::$options['remote_destinations'][$destination]['title'] . "` of type `" . pb_backupbuddy::$options['remote_destinations'][$destination]['type'] . "` with ID `{$destination}`.\n\n";
				}
			}
		}
		
		echo "Step details:\n";
		print_r( $backup_options->options );
		
		echo '</textarea><br><br>';
		
		// Output status log if it exists.
		$log_directory = WP_CONTENT_DIR . '/uploads/pb_' . pb_backupbuddy::settings( 'slug' ) . '/';
		$serial_file = $log_directory . 'status-' . $serial . '_' . pb_backupbuddy::$options['log_serial'] . '.txt';
		if ( file_exists( $serial_file ) ) {
			echo '<b>Log Details:</b><br>';
			echo '<textarea style="width: 100%; height: 120px;" wrap="off">';
			echo file_get_contents( $serial_file );
			echo '</textarea><br><br>';
		}
		
		echo 'This information is primarily used for troubleshooting when working with support. If you are encountering problems providing this information to support may assist in troubleshooting.';
		
		pb_backupbuddy::$ui->ajax_footer();
		die();
		
	} // End backup_step_status().
	
	
	
	// IMPORTANT: MUST provide 3rd param, backup serial ID, when using pb_backupbuddy::status() within this function for it to show for this backup.
	public function backup_status() {
		$serial = trim( pb_backupbuddy::_POST( 'serial' ) );
		$serial = str_replace( '/\\', '', $serial );
		
		
		if ( true == get_transient( 'pb_backupbuddy_stop_backup-' . $serial ) ) {
			pb_backupbuddy::status( 'message', 'Backup STOPPED. Post backup cleanup step has been scheduled to clean up any temporary files.', $serial );
			
			require_once( pb_backupbuddy::plugin_path() . '/classes/fileoptions.php' );
			$fileoptions_file = pb_backupbuddy::$options['log_directory'] . 'fileoptions/' . $serial . '.txt';
			$backup_options = new pb_backupbuddy_fileoptions( $fileoptions_file, false, $ignore_lock = true );
			
			if ( true !== ( $result = $backup_options->is_ok() ) ) {
				pb_backupbuddy::status( 'error', 'Unable to access fileoptions file `' . $fileoptions_file . '`.', $serial );
			}
			
			// Wipe backup file.
			if ( isset( $backup_options->options['archive_file'] ) && file_exists( $backup_options->options['archive_file'] ) ) { // Final zip file.
				$unlink_result = @unlink( $backup_options->options['archive_file'] );
				if ( true === $unlink_result ) {
					pb_backupbuddy::status( 'details', 'Deleted stopped backup ZIP file.', $serial );
				} else {
					pb_backupbuddy::status( 'error', 'Unable to delete stopped backup file. You should delete it manually as it may be damaged from stopping mid-backup. File to delete: `' . $backup_options->options['archive_file'] . '`.', $serial );
				}
			} else {
				pb_backupbuddy::status( 'details', 'Archive file not found. Not deleting.', $serial );
			}
			
			// NOTE: fileoptions file will be wiped by periodic cleanup. We need to keep this for now...
			
			delete_transient( 'pb_backupbuddy_stop_backup-' . $serial );
			pb_backupbuddy::status( 'details', 'Backup stopped. Any remaining processes or files will time out and be cleaned up by scheduled housekeeping functionality.', $serial );
			pb_backupbuddy::status( 'action', 'halt_script', $serial ); // Halt JS on page.
		}
		
		pb_backupbuddy::status( 'details', 'Welcome!' );
		// Make sure the serial exists.
		if ( $serial != '' ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/fileoptions.php' );
			$fileoptions_file = pb_backupbuddy::$options['log_directory'] . 'fileoptions/' . $serial . '.txt';
			$backup_options = new pb_backupbuddy_fileoptions( $fileoptions_file, $read_only = true, $ignore_lock = true );
			$backup = &$backup_options->options;
			if ( true !== ( $result = $backup_options->is_ok() ) ) {
				pb_backupbuddy::status( 'error', 'Error #8329754.  Error retrieving fileoptions file `' . $fileoptions_file . '`. Error details `' . $result . '`.', $serial );
				pb_backupbuddy::status( 'action', 'halt_script', $serial );
				die();
			}
		}
		if ( ( $serial == '' ) || ( !is_array( $backup ) ) ) {
			pb_backupbuddy::status( 'error', 'Error #9031. Invalid backup serial (' . htmlentities( $serial ) . '). Please check directory permissions for your wp-content/uploads/ directory recursively, your PHP error_log for any errors, and that you have enough free disk space. If seeking support please provide this full status log and PHP error log. Fatal error. Verify this fileoptions file exists `' . $fileoptions_file . '`', $serial );
			pb_backupbuddy::status( 'action', 'halt_script', $serial );
			die();
		} else {
			
			// Verify init completed.
			if ( false === $backup['init_complete'] ) {
				pb_backupbuddy::status( 'error', 'Error #9033: The pre-backup initialization for serial `' . $serial . '` was unable save pre-backup initialization options (init_complete===false) possibly because the pre-backup initialization step did not complete. If the log indicates the pre-backup procedure did indeed complete then something prevented BackupBuddy from updating the database such as an misconfigured caching plugin. Check for any errors above or in logs. Verify permissions & that there is enough server memory. See the BackupBuddy "Server Information" page to help assess your server.', $serial );
				pb_backupbuddy::status( 'action', 'halt_script', $serial );
			}
			
			//***** Begin outputting status of the current step.
			foreach( $backup['steps'] as $step ) {
				if ( ( $step['start_time'] != -1 ) && ( $step['start_time'] != 0 ) && ( $step['finish_time'] == 0 ) ) { // A step isnt mark to skip, has begun but has not finished. This should not happen but the WP cron is funky. Wait a while before continuing.
					
					// For database dump step output the SQL file current size.
					if ( $step['function'] == 'backup_create_database_dump' ) {
						$sql_file = $backup['temp_directory'] . 'db_1.sql';
						if ( file_exists( $sql_file ) ) {
							$sql_filesize = pb_backupbuddy::$format->file_size( filesize( $sql_file ) );
						} else { // No SQL file yet.
							$sql_filesize = '[SQL file not found yet]';
						}
						pb_backupbuddy::status( 'details', 'Current SQL database dump file size: ' . $sql_filesize . '.', $serial );
					}
					
					pb_backupbuddy::status( 'details', 'Waiting for function `' . $step['function'] . '` to complete. Started ' . ( time() - $step['start_time'] ) . ' seconds ago.', $serial );
					if ( ( time() - $step['start_time'] ) > 300 ) {
						pb_backupbuddy::status( 'warning', 'The function `' . $step['function'] . '` is taking an abnormally long time to complete (' . ( time() - $step['start_time'] ) . ' seconds). The backup may have stalled.', $serial );
					}
				} elseif ( $step['start_time'] == 0 ) { // Step that has not started yet.
					// Do nothing.
				} elseif ( $step['start_time'] == -1 ) { // Step marked for skipping (backup stop button hit).
					// Do nothing.
				} else { // Last case: Finished. Skip.
					// Do nothing.
				}
			}
			//***** End outputting status of the current step.
			
			
			//***** Begin output of temp zip file size.
			$temporary_zip_directory = pb_backupbuddy::$options['backup_directory'] . 'temp_zip_' . $serial . '/';
			if ( file_exists( $temporary_zip_directory ) ) { // Temp zip file.
				$directory = opendir( $temporary_zip_directory );
				while( $file = readdir( $directory ) ) {
					if ( ( $file != '.' ) && ( $file != '..' ) && ( $file != 'exclusions.txt' ) && ( !preg_match( '/.*\.txt/', $file ) ) && ( !preg_match( '/pclzip.*\.gz/', $file) ) ) {
						$stats = stat( $temporary_zip_directory . $file );
						//$return_status .= '!' . pb_backupbuddy::$format->localize_time( time() ) . '|~|' . round ( microtime( true ) - pb_backupbuddy::$start_time, 2 ) . '|~|' . round( memory_get_peak_usage() / 1048576, 2 ) . '|~|details|~|' . __('Temporary ZIP file size', 'it-l10n-backupbuddy' ) .': ' . pb_backupbuddy::$format->file_size( $stats['size'] ) . "\n";;
						pb_backupbuddy::status( 'details', __('Temporary ZIP file size', 'it-l10n-backupbuddy' ) .': ' . pb_backupbuddy::$format->file_size( $stats['size'] ), $serial );
						//$return_status .= '!' . pb_backupbuddy::$format->localize_time( time() ) . '|~|' . round ( microtime( true ) - pb_backupbuddy::$start_time, 2 ) . '|~|' . round( memory_get_peak_usage() / 1048576, 2 ) . '|~|action|~|archive_size^' . pb_backupbuddy::$format->file_size( $stats['size'] ) . "\n";
						pb_backupbuddy::status( 'action', 'archive_size^' . pb_backupbuddy::$format->file_size( $stats['size'] ), $serial );
					}
				}
				closedir( $directory );
				unset( $directory );
			}
			//***** End output of temp zip file size.
			
			
			// Output different stuff to the browser depending on whether backup is finished or not.
			if ( $backup['finish_time'] > 0 ) { // BACKUP FINISHED.
				
				// OUTPUT COMPLETED ZIP FINAL SIZE.
				if( file_exists( $backup['archive_file'] ) ) { // Final zip file.
					$stats = stat( $backup['archive_file'] );
					//$return_status .= '!' . pb_backupbuddy::$format->localize_time( time() ) . '|~|' . round ( microtime( true ) - pb_backupbuddy::$start_time, 2 ) . '|~|' . round( memory_get_peak_usage() / 1048576, 2 ) . '|~|details|~|' . __('Completed backup final ZIP file size', 'it-l10n-backupbuddy' ) . ': ' . pb_backupbuddy::$format->file_size( $stats['size'] ) . "\n";;
					pb_backupbuddy::status( 'details', '--- ' . __( 'New PHP process.' ), $serial );
					pb_backupbuddy::status( 'details', __('Completed backup final ZIP file size', 'it-l10n-backupbuddy' ) . ': ' . pb_backupbuddy::$format->file_size( $stats['size'] ), $serial );
					//$return_status .= '!' . pb_backupbuddy::$format->localize_time( time() ) . '|~|' . round ( microtime( true ) - pb_backupbuddy::$start_time, 2 ) . '|~|' . round( memory_get_peak_usage() / 1048576, 2 ) . '|~|action|~|archive_size^' . pb_backupbuddy::$format->file_size( $stats['size'] ) . "\n";
					pb_backupbuddy::status( 'action', 'archive_size^' . pb_backupbuddy::$format->file_size( $stats['size'] ), $serial );
					$backup_finished = true;
				} else {
					pb_backupbuddy::status( 'error', __( 'Backup reports success but unable to access final ZIP file. Verify permissions and ownership. If the error persists insure that server is properly configured with suphp and proper ownership & permissions.', 'it-l10n-backupbuddy' ), $serial );
				}
				pb_backupbuddy::status( 'message', __('Backup successfully completed in ', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::$format->time_duration( $backup['finish_time'] - $backup['start_time'] ) . '.', $serial );
				pb_backupbuddy::status( 'action', 'finish_backup', $serial );
			} else { // NOT FINISHED
				//$return_status .= '!' . pb_backupbuddy::$format->localize_time( time() ) . "|~|0|~|0|~|ping\n";
				pb_backupbuddy::status( 'message', __( 'Ping. Waiting for server . . .', 'it-l10n-backupbuddy' ), $serial );
			}
			
			
			//***** Begin getting status log information.
			$return_status = '';
			$status_lines = pb_backupbuddy::get_status( $serial, true, false, true ); // Clear file, dont unlink file (pclzip cant handle files unlinking mid-zip), dont show getting status message.
			if ( $status_lines !== false ) { // Only add lines if there is status contents.
				foreach( $status_lines as $status_line ) {
					//$return_status .= '!' . $status_line[0] . '|' . $status_line[3] . '|' . $status_line[4] . '( ' . $status_line[1] . 'secs / ' . $status_line[2] . 'MB )' . "\n";
					$return_status .= '!' . implode( '|~|', $status_line ) . "\n";
				}
			}
			//***** End getting status log information.
			
			
			echo $return_status; // Return messages.
		}
		
		
		die();
	} // End backup_status().
	
	
	
	/* importbuddy()
	 *
	 * Compile ImportBuddy and stream download to browser.
	 *
	 */
	public function importbuddy() {
		
		$pass_hash = '';
		
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		
		if ( pb_backupbuddy::_GET( 'p' ) != '' ) {
			$pass_hash = md5( pb_backupbuddy::_GET( 'p' ) );
			if ( pb_backupbuddy::$options['importbuddy_pass_hash'] == '' ) { // if no default pass is set then we set this as default.
				pb_backupbuddy::$options['importbuddy_pass_hash'] = $pass_hash;
				pb_backupbuddy::$options['importbuddy_pass_length'] = strlen( pb_backupbuddy::_GET( 'p' ) ); // length of pass pre-hash.
				pb_backupbuddy::save();
			}
		}
		
		pb_backupbuddy::$classes['core']->importbuddy( '', $pass_hash ); // Outputs importbuddy to browser for download.
		
		die();
	} // End importbuddy().
	
	
	
	/* repairbuddy()
	 *
	 * Compile RepairBuddy and stream download to browser.
	 *
	 */
	public function repairbuddy() {
		
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		pb_backupbuddy::$classes['core']->repairbuddy(); // Outputs repairbuddy to browser for download.
		
		die();
	} // End repairbuddy().
	
	
	
	public function hash() {
		pb_backupbuddy::load();
		
		pb_backupbuddy::$ui->ajax_header();
		
		require_once( 'ajax/_hash.php' );
		
		pb_backupbuddy::$ui->ajax_footer();
		die();
		
	} // End destination_picker().
	
	
	
	/* destination_picker()
	 *
	 * iframe remote destination selector page.
	 *
	 */
	public function destination_picker() {
		pb_backupbuddy::load();
		
		pb_backupbuddy::$ui->ajax_header();
		
		$mode = 'destination';
		require_once( 'ajax/_destination_picker.php' );
		
		pb_backupbuddy::$ui->ajax_footer();
		die();
		
	} // End destination_picker().
	
	
	
	/* migration_picker()
	 *
	 * Same as destination picker but in migration mode (only limited destinations are available).
	 *
	 */
	public function migration_picker() {
		pb_backupbuddy::load();
		
		pb_backupbuddy::$ui->ajax_header();
		
		$mode = 'migration';
		require_once( 'ajax/_destination_picker.php' );
		
		pb_backupbuddy::$ui->ajax_footer();
		die();
		
	} // End migration_picker().
	
	
	
	/*	remote_send()
	 *	
	 *	Send backup archive to a remote destination manually. Optionally sends importbuddy.php with files.
	 *	Sends are scheduled to run in a cron and are passed to the cron.php remote_send() method.
	 *	
	 *	@return		null
	 */
	public function remote_send() {
		if ( defined( 'PB_DEMO_MODE' ) ) {
			die( 'Access denied in demo mode.' );
		}
		
		$success_output = false; // Set to true onece a leading 1 has been sent to the javascript to indicate success.
		$destination_id = pb_backupbuddy::_POST( 'destination_id' );
		if ( pb_backupbuddy::_POST( 'file' ) != 'importbuddy.php' ) {
			$backup_file = pb_backupbuddy::$options['backup_directory'] . pb_backupbuddy::_POST( 'file' );
			if ( ! file_exists( $backup_file ) ) { // Error if file to send did not exist!
				$error_message = 'Unable to find file `' . $backup_file . '` to send. File does not appear to exist. You can try again in a moment or turn on full error logging and try again to log for support.';
				pb_backupbuddy::status( 'error', $error_message );
				pb_backupbuddy::alert( $error_message, true );
				die();
			}
		} else {
			$backup_file = '';
		}
		
		
		if ( pb_backupbuddy::_POST( 'send_importbuddy' ) == '1' ) {
			$send_importbuddy = true;
			pb_backupbuddy::status( 'details', 'Cron send to be scheduled with importbuddy sending.' );
		} else {
			$send_importbuddy = false;
			pb_backupbuddy::status( 'details', 'Cron send to be scheduled WITHOUT importbuddy sending.' );
		}
		
		
		// For Stash we will check the quota prior to initiating send.
		if ( pb_backupbuddy::$options['remote_destinations'][$destination_id]['type'] == 'stash' ) {
			// Pass off to destination handler.
			require_once( pb_backupbuddy::plugin_path() . '/destinations/bootstrap.php' );
			$send_result = pb_backupbuddy_destinations::get_info( 'stash' ); // Used to kick the Stash destination into life.
			$stash_quota = pb_backupbuddy_destination_stash::get_quota( pb_backupbuddy::$options['remote_destinations'][$destination_id], true );
			//print_r( $stash_quota );
			
			if ( $backup_file != '' ) {
				$backup_file_size = filesize( $backup_file );
			} else {
				$backup_file_size = 50000;
			}
			if ( ( $backup_file_size + $stash_quota['quota_used'] ) > $stash_quota['quota_total'] ) {
				echo "You do not have enough Stash storage space to send this file. Please upgrade your Stash storage or delete files to make space.\n\n";
				
				echo 'Attempting to send file of size ' . pb_backupbuddy::$format->file_size( $backup_file_size ) . ' but you only have ' . $stash_quota['quota_available_nice'] . ' available. ';
				echo 'Currently using ' . $stash_quota['quota_used_nice'] . ' of ' . $stash_quota['quota_total_nice'] . ' (' . $stash_quota['quota_used_percent'] . '%).';
				die();
			} else {
				if ( isset( $stash_quota['quota_warning'] ) && ( $stash_quota['quota_warning'] != '' ) ) {
					echo '1Warning: ' . $stash_quota['quota_warning'] . "\n\n";
					$success_output = true;
				}
			}
			
		}
		
		
		$schedule_result = pb_backupbuddy::$classes['core']->schedule_single_event( time(), pb_backupbuddy::cron_tag( 'remote_send' ), array( $destination_id, $backup_file, pb_backupbuddy::_POST( 'trigger' ), $send_importbuddy ) );
		if ( $schedule_result === FALSE ) {
			echo 'Error scheduling file transfer. Please check your BackupBuddy error log for details. A plugin may have prevented scheduling or the database rejected it.';
		}
		spawn_cron( time() + 150 ); // Adds > 60 seconds to get around once per minute cron running limit.
		update_option( '_transient_doing_cron', 0 ); // Prevent cron-blocking for next item.
		
		// SEE cron.php remote_send() for sending function that we pass to via the cron above.
		
		if ( $success_output === false ) {
			echo 1;
		}
		die();
	} // End remote_send().
	
	
	
	/*	migrate_status()
	 *	
	 *	Gives the current migration status. Echos.
	 *	
	 *	@return		null
	 */
	function migrate_status() {
		
		$step = pb_backupbuddy::_POST( 'step' );
		$backup_file = pb_backupbuddy::_POST( 'backup_file' );
		$url = trim( pb_backupbuddy::_POST( 'url' ) );
		
		switch( $step ) {
			case 'step1': // Make sure backup file has been transferred properly.
				// Find last migration.
				$last_migration_key = '';
				foreach( pb_backupbuddy::$options['remote_sends'] as $send_key => $send ) { // Find latest migration send for this file.
					if ( basename( $send['file'] ) == $backup_file ) {
						if ( $send['trigger'] == 'migration' ) {
							$last_migration_key = $send_key;
						}
					}
				} // end foreach.
				if ( '' == $last_migration_key ) {
					die( json_encode( array(
						'status_code' 		=>		'failure',
						'status_message'	=>		'Status: Error #54849545. Unable to determine which backup is migrating. Please try again.',
						'next_step'			=>		'0',
					) ) );
				}
				$migrate_send_status = pb_backupbuddy::$options['remote_sends'][$last_migration_key]['status'];
				
				if ( $migrate_send_status == 'timeout' ) {
					$status_message = 'Status: Waiting for backup to finish uploading to server...';
					$next_step = '1';
				} elseif ( $migrate_send_status == 'failure' ) {
					$status_message = 'Status: Sending backup to server failed.';
					$next_step = '0';
				} elseif ( $migrate_send_status == 'success' ) {
					$status_message = 'Status: Success sending backup file.';
					$next_step = '2';
				}
				die( json_encode( array(
					'status_code' 		=>		$migrate_send_status,
					'status_message'	=>		$status_message,
					'next_step'			=>		$next_step,
				) ) );
				
				break;
				
			case 'step2': // Hit importbuddy file to make sure URL is correct, it exists, and extracts itself fine.
				
				$url = rtrim( $url, '/' ); // Remove trailing slash if its there.
				if ( strpos( $url, 'importbuddy.php' ) === false ) { // If no importbuddy.php at end of URL add it.
					$url .= '/importbuddy.php';
				}
				
				if ( ( false === strstr( $url, 'http://' ) ) && ( false === strstr( $url, 'https://' ) ) ) { // http or https is missing; prepend it.
					$url = 'http://' . $url;
				}
				
				$response = wp_remote_get( $url . '?api=ping', array(
						'method' => 'GET',
						'timeout' => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking' => true,
						'headers' => array(),
						'body' => null,
						'cookies' => array()
					)
				);
				
				
				if( is_wp_error( $response ) ) {
					die( json_encode( array(
						'status_code' 		=>		'failure',
						'status_message'	=>		'Status: HTTP error checking for importbuddy.php at `' . $url . '`. Error: `' . $response->get_error_message() . '`.',
						'next_step'			=>		'0',
					) ) );
				}
				
				
				if ( trim( $response['body'] ) == 'pong' ) { // Importbuddy found.
					die( json_encode( array(
						'import_url'		=>		$url . '?display_mode=embed&file=' . pb_backupbuddy::_POST( 'backup_file' ) . '&v=' . pb_backupbuddy::$options['importbuddy_pass_hash'],
						'status_code' 		=>		'success',
						'status_message'	=>		'Sucess verifying URL is valid importbuddy.php location. Continue migration below.',
						'next_step'			=>		'0',
					) ) );
				} else { // No importbuddy here.
					die( json_encode( array(
						'status_code' 		=>		'failure',
						'status_message'	=>		'<b>Error</b>: The importbuddy.php file uploaded was not found at <a href="' . $url . '">' . $url . '</a>. Please verify the URL properly matches & corresponds to the upload directory entered for this destination\'s settings.<br><br><b>Tip:</b> This error is only caused by URL not properly matching, permissions on the destination server blocking the script, or other destination server error. You may manually verify that the importbuddy.php scripts exists in the expected location on the destination server and that the script URL <a href="' . $url . '">' . $url . '</a> properly loads the ImportBuddy tool. You may manually upload importbuddy.php and the backup ZIP file to the destination server & navigating to its URL in your browser for an almost-as-quick alternative.',
						'next_step'			=>		'0',
					) ) );
				}
				
				break;
				
			default:
				echo 'Invalid migrate_status() step: `' . pb_backupbuddy::_POST( 'step' ) . '`.';
				break;
		} // End switch on action.
		
		die();
		
	} // End migrate_status().
	
	
	
	/*	icicle()
	 *	
	 *	Builds and returns graphical directory size listing. Echos.
	 *	
	 *	@return		null
	 */
	public function icicle() {
		pb_backupbuddy::set_greedy_script_limits(); // Building the directory tree can take a bit.
		
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		$response = pb_backupbuddy::$classes['core']->build_icicle( ABSPATH, ABSPATH, '', -1 );
		
		echo $response[0];
		die();
	} // End icicle().
	
	
	
	
	
	public function remote_delete() {
		
		pb_backupbuddy::verify_nonce(); // Security check.
		
		// Destination ID.
		$destination_id = pb_backupbuddy::_GET( 'pb_backupbuddy_destinationid' );
		
		// Delete the destination.
		require_once( pb_backupbuddy::plugin_path() . '/destinations/bootstrap.php' );
		$delete_response = pb_backupbuddy_destinations::delete_destination( $destination_id, true );
		
		// Response.
		if ( $delete_response !== true ) { // Some kind of error so just echo it.
			echo 'Error #544558: `' . $delete_response . '`.';
		} else { // Success.
			echo 'Destination deleted.';
		}
		
		die();
			
	} // End remote_delete().
	
	
	
	/*	remote_test()
	 *	
	 *	Remote destination testing. Echos.
	 *	
	 *	@return		null
	 */
	function remote_test() {
		
		if ( defined( 'PB_DEMO_MODE' ) ) {
			die( 'Access denied in demo mode.' );
		}
		
		global $pb_backupbuddy_destination_errors;
		$pb_backupbuddy_destination_errors = array();
		
		
		require_once( pb_backupbuddy::plugin_path() . '/destinations/bootstrap.php' );
		
		$form_settings = array();
		foreach( pb_backupbuddy::_POST() as $post_id => $post ) {
			if ( substr( $post_id, 0, 15 ) == 'pb_backupbuddy_' ) {
				$id = substr( $post_id, 15 );
				if ( $id != '' ) {
					$form_settings[$id] = $post;
				}
			}
		}
		
		$test_result = pb_backupbuddy_destinations::test( $form_settings );
		
		if ( $test_result === true ) {
			echo 'Test successful.';
		} else {
			echo "Test failed.\n\n";
			echo $test_result;
			foreach( $pb_backupbuddy_destination_errors as $pb_backupbuddy_destination_error ) {
				echo $pb_backupbuddy_destination_error . "\n";
			}
		}
		
		die();
		
	} // End remote_test().
	
	
	
	/*	remote_save()
	 *	
	 *	Remote destination saving.
	 *	
	 *	@return		null
	 */
	public function remote_save() {
		
		pb_backupbuddy::verify_nonce();
		
		
		require_once( pb_backupbuddy::plugin_path() . '/destinations/bootstrap.php' );
		$settings_form = pb_backupbuddy_destinations::configure( array( 'type' => pb_backupbuddy::_POST( 'pb_backupbuddy_type' ) ), 'save' );
		$save_result = $settings_form->process();
		
		
		$destination_id = trim( pb_backupbuddy::_GET( 'pb_backupbuddy_destinationid' ) );
		

		if ( count( $save_result['errors'] ) == 0 ) {
			
			if ( $destination_id == 'NEW' ) { // ADD NEW.
			
				// Dropbox Kludge. Sigh.
				$save_result['data']['token'] = pb_backupbuddy::$options['dropboxtemptoken'];
				
				pb_backupbuddy::$options['remote_destinations'][] = $save_result['data'];
				
				pb_backupbuddy::save();
				echo 'Destination Added.';
			} elseif ( !isset( pb_backupbuddy::$options['remote_destinations'][$destination_id] ) ) { // EDITING NONEXISTANT.
				echo 'Error #54859. Invalid destination ID.';
			} else { // EDITING EXISTING -- Save!
				pb_backupbuddy::$options['remote_destinations'][$destination_id] = $save_result['data'];
				//echo '<pre>' . print_r( pb_backupbuddy::$options['remote_destinations'][$destination_id], true ) . '</pre>';
				
				pb_backupbuddy::save();
				echo 'Settings saved.';
			}
			
		} else {
			echo "Error saving settings.\n\n";
			echo implode( "\n", $save_result['errors'] );
		}
		die();
		
	} // End remote_save().
	
	
	
	/*	refresh_site_size()
	 *	
	 *	Server info page site size refresh. Echos out the new site size (pretty version).
	 *	
	 *	@return		null
	 */
	public function refresh_site_size() {
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		
		$site_size = pb_backupbuddy::$classes['core']->get_site_size(); // array( site_size, site_size_sans_exclusions ).
		
		echo pb_backupbuddy::$format->file_size( $site_size[0] );
		
		die();
	} // End refresh_site_size().
	
	
	
	/*	refresh_site_size_excluded()
	 *	
	 *	Server info page site size (sans exclusions) refresh. Echos out the new site size (pretty version).
	 *	
	 *	@return		null
	 */
	public function refresh_site_size_excluded() {
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		
		$site_size = pb_backupbuddy::$classes['core']->get_site_size(); // array( site_size, site_size_sans_exclusions ).
		
		echo pb_backupbuddy::$format->file_size( $site_size[1] );
		
		die();
	} // End refresh_site_size().
	
	
	
	/*	refresh_database_size()
	 *	
	 *	Server info page database size refresh. Echos out the new site size (pretty version).
	 *	
	 *	@return		null
	 */
	public function refresh_database_size() {
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		
		$database_size = pb_backupbuddy::$classes['core']->get_database_size(); // array( database_size, database_size_sans_exclusions ).
		
		echo pb_backupbuddy::$format->file_size( $database_size[1] );
		
		die();
	} // End refresh_site_size().
	
	
	
	/*	refresh_database_size_excluded()
	 *	
	 *	Server info page database size (sans exclusions) refresh. Echos out the new site size (pretty version).
	 *	
	 *	@return		null
	 */
	public function refresh_database_size_excluded() {
		if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
			pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
		}
		
		$database_size = pb_backupbuddy::$classes['core']->get_database_size(); // array( database_size, database_size_sans_exclusions ).
		
		echo pb_backupbuddy::$format->file_size( $database_size[1] );
		
		die();
	} // End refresh_site_size().
	
	
	
	/*	exclude_tree()
	 *	
	 *	Directory exclusion tree for settings page.
	 *	
	 *	@return		null
	 */
	function exclude_tree() {
		$root = ABSPATH . urldecode( pb_backupbuddy::_POST( 'dir' ) );
		
		if( file_exists( $root ) ) {
			$files = scandir( $root );
			
			natcasesort( $files );
			
			// Sort with directories first.
			$sorted_files = array(); // Temporary holder for sorting files.
			$sorted_directories = array(); // Temporary holder for sorting directories.
			foreach( $files as $file ) {
				if ( ( $file == '.' ) || ( $file == '..' ) ) {
					continue;
				}
				if( is_file( str_replace( '//', '/', $root . $file ) ) ) {
					array_push( $sorted_files, $file );
				} else {
					array_unshift( $sorted_directories, $file );
				}
			}
			$files = array_merge( array_reverse( $sorted_directories ), $sorted_files );
			unset( $sorted_files );
			unset( $sorted_directories );
			unset( $file );
			
			
			if( count( $files ) > 2 ) { /* The 2 accounts for . and .. */
				echo '<ul class="jqueryFileTree" style="display: none;">';
				foreach( $files as $file ) {
					if( file_exists( str_replace( '//', '/', $root . $file ) ) ) {
						if ( is_dir( str_replace( '//', '/', $root . $file ) ) ) { // Directory.
							echo '<li class="directory collapsed">';
							$return = '';
							$return .= '<div class="pb_backupbuddy_treeselect_control">';
							$return .= '<img src="' . pb_backupbuddy::plugin_url() . '/images/redminus.png" style="vertical-align: -3px;" title="Add to exclusions..." class="pb_backupbuddy_filetree_exclude">';
							$return .= '</div>';
							//echo $return;
							echo '<a href="#" rel="' . htmlentities( str_replace( ABSPATH, '', $root ) . $file) . '/" title="Toggle expand...">' . htmlentities($file) . $return . '</a>';
							echo '</li>';
						} else { // File.
							echo '<li class="file collapsed">';
							$return = '';
							$return .= '<div class="pb_backupbuddy_treeselect_control">';
							$return .= '<img src="' . pb_backupbuddy::plugin_url() . '/images/redminus.png" style="vertical-align: -3px;" title="Add to exclusions..." class="pb_backupbuddy_filetree_exclude">';
							$return .= '</div>';
							//echo $return;
							echo '<a href="#" rel="' . htmlentities( str_replace( ABSPATH, '', $root ) . $file) . '">' . htmlentities($file) . $return . '</a></li>';
						}
					}
				}
				echo '</ul>';
			} else {
				echo '<ul class="jqueryFileTree" style="display: none;">';
				echo '<li><a href="#" rel="' . htmlentities( pb_backupbuddy::_POST( 'dir' ) . 'NONE' ) . '"><i>Empty Directory ...</i></a></li>';
				echo '</ul>';
			}
		} else {
			echo 'Error #1127555. Unable to read site root.';
		}
		
		die();
	} // End exclude_tree().
	
	
	
	/*	file_tree()
	 *	
	 *	File tree for viewing zip contents.
	 *	
	 *	@return		null
	 */
	function file_tree() {
		$max_cache_time = 86400; // Time in seconds to cache file tree information for maximum.
		
		$root = trim( urldecode( pb_backupbuddy::_POST( 'dir' ) ) );
		//echo 'root: `' . $root . '`';
		$root_len = strlen( $root );
		//echo 'len: ' . $root_len;
		$serial = pb_backupbuddy::_GET( 'serial' );
		
		require_once( pb_backupbuddy::plugin_path() . '/classes/fileoptions.php' );
		$fileoptions_file = pb_backupbuddy::$options['log_directory'] . 'fileoptions/' . $serial . '-filetree.txt';
		
		// Purge cache if too old.
		if ( file_exists( $fileoptions_file ) && ( ( time() - filemtime( $fileoptions_file ) ) > $max_cache_time ) ) {
			if ( false === unlink( $fileoptions_file ) ) {
				pb_backupbuddy::alert( 'Error #456765545. Unable to wipe cached fileoptions file `' . $fileoptions_file . '`.' );
			}
		}
		
		$fileoptions = new pb_backupbuddy_fileoptions( $fileoptions_file );
		
		if ( true !== ( $result = $fileoptions->is_ok() ) ) {
			// Get file listing.
			require_once( pb_backupbuddy::plugin_path() . '/lib/zipbuddy/zipbuddy.php' );
			pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( ABSPATH, array(), 'unzip' );
			$files = pb_backupbuddy::$classes['zipbuddy']->get_file_list( pb_backupbuddy::$options['backup_directory'] . pb_backupbuddy::_GET( 'zip_viewer' ) );
			$fileoptions->options = $files;
			$fileoptions->save();
		} else {
			$files = &$fileoptions->options;
		}
		
		
		// Strip out files we dont want to show for this request.
		foreach( $files as $key => $file ) {
			if ( substr( $file[0], 0, $root_len ) != $root ) { // If ABOVE the root dont show.
				//echo 'unset' . $root;
				unset( $files[$key] );
				continue;
			}
			
			$unrooted_file = substr( $file[0], $root_len );
			if ( strlen( $file[0] ) <= $root_len ) { // If shorter than root length then certainly is not within this directory.
				unset( $files[$key] );
				continue;
			}
			
			$slash_count = substr_count( $unrooted_file, '/' );
			//echo 'unroot: ' . $unrooted_file . ' ~ ' . $slash_count . '<Br>';
			
			if ( $slash_count > 1 ) { // If BELOW the root dont show. More than one slash means too deep.
				//echo 'unset';
				unset( $files[$key] );
				continue;
			}
			
			if ( ( ( $slash_count == 1 ) && ( substr( $unrooted_file, -1 ) != '/' ) ) ) { // If BELOW the root multiple levels dont show. Has slashes AND does not end in a slash (so not 1 directory lower).
				//echo 'unroot: ' . $unrooted_file . '<br>';
				unset( $files[$key] );
				continue;
			}
			
			if ( $slash_count == 1 ) {
				//$files[$key][0] = '/' . $unrooted_file;
			}
			
			if ( $root_len > 0 ) { // Have a root to trim out of filename.
				$files[$key][0] = $unrooted_file;
			}
			
			
		}
		//echo 'count: ' . count( $files );
		
		/*
		echo '<pre>';
		print_r( $files );
		echo '</pre>';
		*/
		
		// Bubble directories up to top.
		/*
		$sorted_files = array();
		
		function backupbuddy_number_sort( $a,$b ) {
			//return $a['0']<$b['0'];
			$tmp = array( $a[0], $b[0] );
			natcasesort( $tmp );
			echo '<pre>';
			print_r( $tmp );
			echo '</pre>';
			if ( $tmp[0] == $b[0] ) {
				return true;
			} else {
				return false;
			}
		}
		// Sort by modified using custom sort function above.
		usort( $files, 'backupbuddy_number_sort' );
		
		
		echo '<pre>';
		print_r( $files );
		echo '</pre>';
		*/
		/*
		$new_files = array();
		foreach( $files as $file ) {
			$new_files[ $file[0] ] = array(
										$file[1].
										$file[2],
										$file[3],
									);
		}
		*/
		//print_r( $new_files );
		
		
		
		if( count( $files ) > 0 ) { /* The 2 accounts for . and .. */
			echo '<ul class="jqueryFileTree" style="display: none;">';
			foreach( $files as $file ) {
				if ( substr( $file[0], -1 ) == '/' ) { // Directory.
					echo '<li class="directory collapsed">';
					$return = '';
					/*
					$return .= '<div class="pb_backupbuddy_treeselect_control">';
					$return .= '<img src="' . pb_backupbuddy::plugin_url() . '/images/greenplus.png" style="vertical-align: -3px;" title="Restore..." class="pb_backupbuddy_filetree_exclude">';
					$return .= '</div>';
					*/
					//echo $return;
					echo '<a href="#" rel="' . htmlentities( $root . $file[0] ) . '" title="Toggle expand...">' . htmlentities( rtrim( $file[0], '/' ) ) . $return . '</a>';
					echo '</li>';
				} else { // File.
					echo '<li class="file collapsed">';
					$return = '';
					/*
					$return .= '<div class="pb_backupbuddy_treeselect_control">';
					$return .= '<img src="' . pb_backupbuddy::plugin_url() . '/images/greenplus.png" style="vertical-align: -3px;" title="Restore..." class="pb_backupbuddy_filetree_exclude">';
					$return .= '</div>';
					*/
					//echo $return;
					echo '<a href="#" rel="' . htmlentities( $root . $file[0] ) . '">' . htmlentities( $file[0] );
					
					echo '<span class="pb_backupbuddy_fileinfo">';
					echo '	<span class="pb_backupbuddy_col1">' . pb_backupbuddy::$format->file_size( $file[1] ) . '</span>';
					echo '	<span class="pb_backupbuddy_col2">' . pb_backupbuddy::$format->date( pb_backupbuddy::$format->localize_time( $file[3] ) ) . ' <span class="description">(' . pb_backupbuddy::$format->time_ago( $file[3] ) . ' ago)</span></span>';
					echo '</span>';
					
					echo $return . '</a></li>';
				}
			}
			echo '</ul>';
		} else {
			echo '<ul class="jqueryFileTree" style="display: none;">';
			echo '<li><a href="#" rel="' . htmlentities( pb_backupbuddy::_POST( 'dir' ) . 'NONE' ) . '"><i>Empty Directory ...</i></a></li>';
			echo '</ul>';
		}
		
		die();
	} // End exclude_tree().
	
	
	
	/*	download_archive()
	 *	
	 *	Handle allowing download of archive.
	 *	
	 *	@param		
	 *	@return		
	 */
	public function download_archive() {
		
		if ( is_multisite() && !current_user_can( 'manage_network' ) ) { // If a Network and NOT the superadmin must make sure they can only download the specific subsite backups for security purposes.
			// Load core if it has not been instantiated yet.
			if ( !isset( pb_backupbuddy::$classes['core'] ) ) {
				require_once( pb_backupbuddy::plugin_path() . '/classes/core.php' );
				pb_backupbuddy::$classes['core'] = new pb_backupbuddy_core();
			}
			
			// Only allow downloads of their own backups.
			if ( !strstr( pb_backupbuddy::_GET( 'backupbuddy_backup' ), pb_backupbuddy::$classes['core']->backup_prefix() ) ) {
				die( 'Access Denied. You may only download backups specific to your Multisite Subsite. Only Network Admins may download backups for another subsite in the network.' );
			}
		}
		
		// Make sure file exists we are trying to get.
		if ( !file_exists( pb_backupbuddy::$options['backup_directory'] . pb_backupbuddy::_GET( 'backupbuddy_backup' ) ) ) { // Does not exist.
			die( 'Error #548957857584784332. The requested backup file does not exist. It may have already been deleted.' );
		}
		
		$abspath = str_replace( '\\', '/', ABSPATH ); // Change slashes to handle Windows as we store backup_directory with Linux-style slashes even on Windows.
		
		// Make sure file to download is in a publicly accessible location (beneath WP web root technically).
		if ( FALSE === stristr( pb_backupbuddy::$options['backup_directory'], $abspath ) ) {
			die( 'Error #5432532. You cannot download backups stored outside of the WordPress web root. Please use FTP or other means.' );
		}
		
		// Made it this far so download dir is within this WP install.
		$sitepath = str_replace( $abspath, '', pb_backupbuddy::$options['backup_directory'] );
		$download_url = rtrim( site_url(), '/\\' ) . '/' . trim( $sitepath, '/\\' ) . '/' . pb_backupbuddy::_GET( 'backupbuddy_backup' );
		
		//$download_url = site_url() . '/wp-content/uploads/backupbuddy_backups/' . pb_backupbuddy::_GET( 'backupbuddy_backup' );
		
		if ( pb_backupbuddy::$options['lock_archives_directory'] == '1' ) { // High security mode.
			
			if ( file_exists( pb_backupbuddy::$options['backup_directory'] . '.htaccess' ) ) {
				$unlink_status = @unlink( pb_backupbuddy::$options['backup_directory'] . '.htaccess' );
				if ( $unlink_status === false ) {
					die( 'Error #844594. Unable to temporarily remove .htaccess security protection on archives directory to allow downloading. Please verify permissions of the BackupBuddy archives directory or manually download via FTP.' );
				}
			}
			
			header( 'Location: ' . $download_url );
			ob_clean();
			flush();
			sleep( 8 ); // Wait 8 seconds before creating security file.
			
			$htaccess_creation_status = @file_put_contents( pb_backupbuddy::$options['backup_directory'] . '.htaccess', 'deny from all' );
			if ( $htaccess_creation_status === false ) {
				die( 'Error #344894545. Security Warning! Unable to create security file (.htaccess) in backups archive directory. This file prevents unauthorized downloading of backups should someone be able to guess the backup location and filenames. This is unlikely but for best security should be in place. Please verify permissions on the backups directory.' );
			}
			
		} else { // Normal mode.
			header( 'Location: ' . $download_url );
		}
		
		
		
		die();
	} // End download_archive().
	
	
	
	// Server info page phpinfo button.
	public function phpinfo() {
		phpinfo();
		die();
	}
	
	
	
	/*	set_backup_note()
	 *	
	 *	Used for setting a note to a backup archive.
	 *	
	 *	@return		null
	 */
	public function set_backup_note() {
		if ( !isset( pb_backupbuddy::$classes['zipbuddy'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/lib/zipbuddy/zipbuddy.php' );
			pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( pb_backupbuddy::$options['backup_directory'] );
		}
		
		$backup_file = pb_backupbuddy::$options['backup_directory'] . pb_backupbuddy::_POST( 'backup_file' );
		$note = pb_backupbuddy::_POST( 'note' );
		$note = ereg_replace( "[[:space:]]+", ' ', $note );
		$note = ereg_replace( "[^[:print:]]", '', $note );
		$note = substr( $note, 0, 200 );
		
		
		// Returns true on success, else the error message.
		$old_comment = pb_backupbuddy::$classes['zipbuddy']->get_comment( $backup_file );
		$comment = pb_backupbuddy::$classes['core']->normalize_comment_data( $old_comment );
		$comment['note'] = $note;
		
		//$new_comment = base64_encode( serialize( $comment ) );
		
		$comment_result = pb_backupbuddy::$classes['zipbuddy']->set_comment( $backup_file, $comment );
		
		if ( $comment_result !== true ) {
			echo $comment_result;
		} else {
			echo '1';
		}
		
		// Even if we cannot save the note into the archive file, store it in internal settings.
		$serial = pb_backupbuddy::$classes['core']->get_serial_from_file( $backup_file );
		
		
		require_once( pb_backupbuddy::plugin_path() . '/classes/fileoptions.php' );
		$backup_options = new pb_backupbuddy_fileoptions( pb_backupbuddy::$options['log_directory'] . 'fileoptions/' . $serial . '.txt' );
		if ( true === ( $result = $backup_options->is_ok() ) ) {
			$backup_options->options['integrity']['comment'] = $note;
			$backup_options->save();
		}
		
		
		die();
	} // End set_backup_note().
	
	
	
	public function integrity_status() {
		$serial = pb_backupbuddy::_GET( 'serial' );
		$serial = str_replace( '/\\', '', $serial );
		pb_backupbuddy::load();
		pb_backupbuddy::$ui->ajax_header();
		
		// Backup overall status.
		/*
		echo 'Backup status: ';
		if ( $integrity['status'] == 'pass' ) { // Pass.
			echo '<span class="pb_label pb_label-success">Good</span>';
		} else { // Fail.
			echo '<span class="pb_label pb_label-important">Bad</span>';
		}
		echo '<br>';
		*/
		
		require_once( pb_backupbuddy::plugin_path() . '/classes/fileoptions.php' );
		$backup_options = new pb_backupbuddy_fileoptions( pb_backupbuddy::$options['log_directory'] . 'fileoptions/' . $serial . '.txt', $read_only = true );
		if ( true !== ( $result = $backup_options->is_ok() ) ) {
			pb_backupbuddy::alert( __('Unable to access fileoptions data file.', 'it-l10n-backupbuddy' ) . ' Error: ' . $result );
			die();
		}
		
		$integrity = $backup_options->options['integrity'];
		
		//***** BEGIN TESTS AND RESULTS.
		if ( ! is_array( $integrity['status_details'] ) ) { // $integrity['status_details'] is NOT array (old, pre-3.1.9).
			echo '<h3>Integrity Technical Details</h3>';
			echo '<textarea style="width: 100%; height: 175px;" wrap="off">';
			foreach( $backup_options['integrity'] as $item_name => $item_value ) {
				$item_value = str_replace( '<br />', '<br>', $item_value );
				$item_value = str_replace( '<br><br>', '<br>', $item_value );
				$item_value = str_replace( '<br>', "\n     ", $item_value );
				echo $item_name . ' => ' . $item_value . "\n";
			}
			echo '</textarea><br><br><b>Note:</b> It is normal to see several "file not found" entries as BackupBuddy checks for expected files in multiple locations, expecting to only find each file once in one of those locations.';
		} else { // $integrity['status_details'] is array.
			
			echo '<br>';
			
			function pb_pretty_results( $value ) {
				if ( $value === true ) {
					return '<span class="pb_label pb_label-success">Pass</span>';
				} else {
					return '<span class="pb_label pb_label-important">Fail</span>';
				}
			}
			
			// The tests & their status..
			$tests = array();
			$tests[] = array( 'BackupBackup data file exists', pb_pretty_results( $integrity['status_details']['found_dat'] ) );
			$tests[] = array( 'Database SQL file exists', pb_pretty_results( $integrity['status_details']['found_sql'] ) );
			if ( $integrity['detected_type'] == 'full' ) { // Full backup.
				$tests[] = array( 'WordPress wp-config.php exists (full backups only)', pb_pretty_results( $integrity['status_details']['found_wpconfig'] ) );
			} else { // DB only.
				$tests[] = array( 'WordPress wp-config.php exists (full backups only)', '<span class="pb_label pb_label-success">N/A</span>' );
			}
			
			$columns = array(
				__( 'Integrity Test', 'it-l10n-backupbuddy' ),
				__( 'Status', 'it-l10n-backupbuddy' ),
			);
			
			pb_backupbuddy::$ui->list_table(
				$tests,
				array(
					'columns'		=>	$columns,
					'css'			=>	'width: 100%; min-width: 200px;',
				)
			);
		
		} // end $integrity['status_details'] is an array.
		//***** END TESTS AND RESULTS.
		
		
		echo '<br><br>';
		
		
		//***** BEGIN STEPS.
		$steps = array();
		if ( isset( $backup_options->options['steps'] ) ) {
			foreach( $backup_options->options['steps'] as $step ) {
				if ( isset( $step['finish_time'] ) && ( $step['finish_time'] != 0 ) ) {
					
					// Step name.
					if ( $step['function'] == 'backup_create_database_dump' ) {
						$step_name = 'Database dump';
					} elseif ( $step['function'] == 'backup_zip_files' ) {
						$step_name = 'Zip archive creation';
					} elseif ( $step['function'] == 'post_backup' ) {
						$step_name = 'Post-backup cleanup';
					} elseif( $step['function'] == 'integrity_check' ) {
						$step_name = 'Integrity Check';
					} else {
						$step_name = $step['function'];
					}
					
					// Step time taken.
					$step_time = (string)( $step['finish_time'] - $step['start_time'] ) . ' seconds';
					
					// Compile details for this step into array.
					$steps[] = array(
						$step_name,
						$step_time,
						$step['attempts'],
					);
					
				}
			} // End foreach.
		} else { // End if serial in array is set.
			$step_times[] = 'unknown';
		} // End if serial in array is NOT set.
				
				
		$columns = array(
			__( 'Backup Step', 'it-l10n-backupbuddy' ),
			__( 'Time Taken', 'it-l10n-backupbuddy' ),
			__( 'Attempts', 'it-l10n-backupbuddy' ),
		);
		
		if ( count( $steps ) == 0 ) {
			_e( 'No step statistics were found for this backup.', 'it-l10n-backupbuddy' );
		} else {
			pb_backupbuddy::$ui->list_table(
				$steps,
				array(
					'columns'		=>	$columns,
					'css'			=>	'width: 100%; min-width: 200px;',
				)
			);
		}
		echo '<br><br>';
		//***** END STEPS.
		
		
		//***** BEGIN COMMENT META.
		if ( !isset( pb_backupbuddy::$classes['zipbuddy'] ) ) {
			require_once( pb_backupbuddy::plugin_path() . '/lib/zipbuddy/zipbuddy.php' );
			pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( pb_backupbuddy::$options['backup_directory'] );
		}
		$comment_meta = array();
		if ( isset( $backup_options->options['archive_file'] ) ) {
			$comment = pb_backupbuddy::$classes['zipbuddy']->get_comment( $backup_options->options['archive_file'] );
			$comment = pb_backupbuddy::$classes['core']->normalize_comment_data( $comment );
			
			$comment_meta = array();
			foreach( $comment as $comment_line_name => $comment_line_value ) { // Loop through all meta fields in the comment array to display.
				
				if ( $comment_line_name == 'serial' ) {
					$comment_line_name = 'Unique serial identifier (serial)';
				} elseif ( $comment_line_name == 'siteurl' ) {
					$comment_line_name = 'Site URL (siteurl)';
				} elseif ( $comment_line_name == 'type' ) {
					$comment_line_name = 'Backup type (type)';
					if ( $comment_line_value == 'db' ) {
						$comment_line_value = 'Database';
					} elseif ( $comment_line_value == 'full' ) {
						$comment_line_value = 'Full';
					}
				} elseif ( $comment_line_name == 'created' ) {
					$comment_line_name = 'Backup creation time (created)';
					if ( $comment_line_value != '' ) {
						$comment_line_value = pb_backupbuddy::$format->date( $comment_line_value );
					}
				} elseif ( $comment_line_name == 'bb_version' ) {
					$comment_line_name = 'BackupBuddy version at creation (bb_version)';
				} elseif ( $comment_line_name == 'wp_version' ) {
					$comment_line_name = 'WordPress version at creation (wp_version)';
				} elseif ( $comment_line_name == 'dat_path' ) {
					$comment_line_name = 'BackupBuddy data file (relative; dat_path)';
				} elseif ( $comment_line_name == 'note' ) {
					$comment_line_name = 'User-specified note';
					if ( $comment_line_value != '' ) {
						$comment_line_value = '"' . htmlentities( $comment_line_value ) . '"';
					}
				} else {
					$step_name = $step['function'];
				}
				
				if ( $comment_line_value != '' ) {
					$comment_meta[] = array( $comment_line_name, $comment_line_value );
				}
			}
		}
		
		if ( count( $comment_meta ) > 0 ) {
			pb_backupbuddy::$ui->list_table(
				$comment_meta,
				array(
					'columns'		=>	array( 'Meta Information', 'Value' ),
					'css'			=>	'width: 100%; min-width: 200px;',
				)
			);
		} else {
			echo '<i>No meta data found in zip comment. Skipping meta information display.</i>';
		}
		//***** END COMMENT META.
	
		
		if ( isset( $backup_options->options['trigger'] ) ) {
			$trigger = $backup_options->options['trigger'];
		} else {
			$trigger = 'Unknown trigger';
		}
		$scanned = pb_backupbuddy::$format->date( $integrity['scan_time'] );
		echo '<br><br>';
		echo ucfirst( $trigger ) . " backup {$integrity['file']} last scanned {$scanned}.";
		echo '<br><br><br>';
		
		echo '<a class="button secondary-button" onclick="jQuery(\'#pb_backupbuddy_advanced_debug\').slideToggle();">Display Advanced Debugging</a>';
		echo '<div id="pb_backupbuddy_advanced_debug" style="display: none;">';
		echo '<textarea style="width: 100%; height: 400px;" wrap="on">';
		echo print_r( $backup_options->options, true );
		echo '</textarea><br><br>';
		echo '</div>';
		
		
		pb_backupbuddy::$ui->ajax_footer();
		die();
		
	} // End integrity_status().
	
	
	
	/*	db_check()
	 *	
	 *	Check database integrity on a specific table. Used on server info page.
	 *	
	 *	@return		null
	 */
	public function db_check() {
		
		$table = base64_decode( pb_backupbuddy::_GET( 'table' ) );
		$check_level = 'MEDIUM';
		
		pb_backupbuddy::$ui->ajax_header();
		echo '<h2>Database Table Check</h2>';
		echo 'Checking table `' . $table . '` using ' . $check_level . ' scan...<br><br>';
		$result = mysql_query( "CHECK TABLE `" . mysql_real_escape_string( $table ) . "` " . $check_level );
		echo '<b>Results:</b><br><br>';
		echo '<table class="widefat">';
		while( $rs = mysql_fetch_array( $result ) ) {
			echo '<tr>';
			echo '<td>' . $rs['Msg_type'] . '</td>';
			echo '<td>' . $rs['Msg_text'] . '</td>';
			echo '</tr>';
		}
		echo '</table>';
		pb_backupbuddy::$ui->ajax_footer();
		
		die();
		
	} // End db_check().
	
	
	
	/*	db_repair()
	 *	
	 *	Repair specific table. Used on server info page.
	 *	
	 *	@return		null
	 */
	public function db_repair() {
		
		$table = base64_decode( pb_backupbuddy::_GET( 'table' ) );
		
		pb_backupbuddy::$ui->ajax_header();
		echo '<h2>Database Table Repair</h2>';
		echo 'Repairing table `' . $table . '`...<br><br>';
		$result = mysql_query( "REPAIR TABLE `" . mysql_real_escape_string( $table ) . "`" );
		echo '<b>Results:</b><br><br>';
		echo '<table class="widefat">';
		while( $rs = mysql_fetch_array( $result ) ) {
			echo '<tr>';
			echo '<td>' . $rs['Msg_type'] . '</td>';
			echo '<td>' . $rs['Msg_text'] . '</td>';
			echo '</tr>';
		}
		echo '</table>';
		pb_backupbuddy::$ui->ajax_footer();
		
		die();
		
	} // End db_repair().
	
	
	/*	php_max_runtime_test()
	 *	
	 *	Tests the ACTUAL PHP maximum runtime of the server by echoing and logging to the status log the seconds elapsed.
	 *	
	 *	@param		int		$stop_time_limit		Time after which the test will stop if it is still running.
	 *	@return		null
	 */
	public function php_max_runtime_test() {
		
		$stop_time_limit = 240;
		pb_backupbuddy::set_greedy_script_limits(); // Crank it up for the test!
		
		$m = "# Starting BackupBuddy PHP Max Execution Time Tester. Determines what your ACTUAL limit is (usually shorter than the server reports so now you can find out the truth!). Stopping test if it gets to `{$stop_time_limit}` seconds. When your browser stops loading this page then the script has most likely timed out at your actual PHP limit.";
		pb_backupbuddy::status( 'details', $m );
		echo $m . "<br>\n";
		
		$t = 0; // Time = 0;
		while( $t < $stop_time_limit ) {
			
			pb_backupbuddy::status( 'details', 'Max PHP Execution Time Test status: ' . $t );
			echo $t . "<br>\n";
			//sleep( 1 );
			$now = time(); while ( time() < ( $now + 1 ) ) { true; }
			flush();
			$t++;
			
		}
		
		$m = '# Ending BackupBuddy PHP Max Execution Time The test was stopped as the test time limit of ' . $stop_time_limit . ' seconds.';
		pb_backupbuddy::status( 'details', $m );
		echo $m . "<br>\n";
		die();
	} // End php_max_runtime_test().
	
	
	
	public function disalert() {
		$unique_id = pb_backupbuddy::_POST( 'unique_id' );
		
		pb_backupbuddy::$options['disalerts'][$unique_id] = time();
		pb_backupbuddy::save();
		
		die('1');
		
	} // End disalert().
	
	
	
	public function importexport_settings() {
		pb_backupbuddy::load();
		pb_backupbuddy::$ui->ajax_header();
		
		if ( pb_backupbuddy::_POST( 'import_settings' ) != '' ) {
			$import = trim( stripslashes( pb_backupbuddy::_POST( 'import_data' ) ) );
			$import = base64_decode( $import );
			if ( $import === false ) { // decode failed.
				pb_backupbuddy::alert( 'Unable to decode settings data. Import aborted. Insure that you fully copied the settings and did not change any of the text.' );
			} else { // decode success.
				if ( ( $import = maybe_unserialize( $import ) ) === false ) { // unserialize fail.
					pb_backupbuddy::alert( 'Unable to unserialize settings data. Import aborted. Insure that you fully copied the settings and did not change any of the text.' );
				} else { // unserialize success.
					if ( !isset( $import['data_version'] ) ) { // missing expected content.
						pb_backupbuddy::alert( 'Unserialized settings data but it did not contain expected data. Import aborted. Insure that you fully copied the settings and did not change any of the text.' );
					} else { // contains expected content.
						pb_backupbuddy::$options = $import;
						require_once( pb_backupbuddy::plugin_path() . '/controllers/activation.php' ); // Run data migration to upgrade if needed.
						pb_backupbuddy::save();
						pb_backupbuddy::alert( 'Provided settings successfully imported. Prior settings overwritten.' );
					}
				}
			}
		}
		
		echo '<h2>Export BackupBuddy Settings</h2>';
		echo 'Copy the encoded plugin settings below and paste it into the destination BackupBuddy Settings Import page.<br><br>';
		echo '<textarea style="width: 100%; height: 100px;" wrap="on">';
		echo base64_encode( serialize( pb_backupbuddy::$options ) );
		echo '</textarea>';
		
		echo '<br><br><br>';
		
		echo '<h2>Import BackupBuddy Settings</h2>';
		echo 'Paste encoded plugin settings below to import & replace current settings.  If importing settings from an older version and errors are encountered please deactivate and reactivate the plugin.<br><br>';
		echo '<form method="post" action="' . pb_backupbuddy::ajax_url( 'importexport_settings' ) . '">';
		echo '<textarea style="width: 100%; height: 100px;" wrap="on" name="import_data"></textarea>';
		echo '<br><br><input type="submit" name="import_settings" value="Import Settings" class="button button-primary">';
		echo '</form>';
		
		pb_backupbuddy::$ui->ajax_footer();
		die();
	} // End importexport_settings().
	
	
	/*
	public function view_status_log() {
		
		pb_backupbuddy::$ui->ajax_header();
		
		if ( pb_backupbuddy::_GET( 'serial' ) == '' ) {
			die( 'Error #85487478555. Missing `serial` parameter.' );
		}
		$serial = pb_backupbuddy::_GET( 'serial' );
		
		$log_directory = WP_CONTENT_DIR . '/uploads/pb_' . pb_backupbuddy::settings( 'slug' ) . '/';
		$serial_file = $log_directory . 'status-' . $serial . '_' . pb_backupbuddy::$options['log_serial'] . '.txt';
		if ( ! file_exists( $serial_file ) ) {
			die( 'Status log file `' . $serial_file . '` does not exist. It may have already been deleted. If it does exist, verify permissions.' );
		}
		
		$status_log = pb_backupbuddy::get_status( $serial, false, false );
		
		echo '<h3>Backup Status Log</h3>';
		echo '<textarea style="width: 100%; height: 70%;" wrap="off">';
		echo print_r( $status_log, true );
		foreach( $status_log as $status_log_line ) {
			echo pb_backupbuddy::$format->localize_time( $status_log_line[0] );
			echo
		}
		echo '</textarea><br><br>';
		
		
		pb_backupbuddy::$ui->ajax_footer();
		die();
		
	} // End view_status_log().
	*/
	
	
	
	/* refresh_zip_methods()
	 *
	 * Server Info page refreshing available zip methods. Useful since these are normally cached.
	 *
	 */
	public function refresh_zip_methods() {
	
		// Make sure the legacy method transient is gone
		delete_transient( 'pb_backupbuddy_avail_zip_methods_classic' );
		
		if ( !isset( pb_backupbuddy::$classes['zipbuddy'] ) ) {

			// We don't have an instance of zipbuddy so make sure we can create one
			require_once( pb_backupbuddy::plugin_path() . '/lib/zipbuddy/zipbuddy.php' );
			
			// Find out the transient name(s) and delete them
			$transients = pluginbuddy_zipbuddy::get_transient_names_static();
			foreach ( $transients as $transient ) {
			
				delete_transient( $transient );
				
			}
			
			// Instantiating a class object will renew the deleted method transient
			pb_backupbuddy::$classes['zipbuddy'] = new pluginbuddy_zipbuddy( ABSPATH );
			
		} else {
		
			// We have an instance of zipbuddy so we can use it
			// Find out the transient name(s) and delete them
			$transients = pluginbuddy_zipbuddy::get_transient_names_static();
			foreach ( $transients as $transient ) {
			
				delete_transient( $transient );
				
			}
			
			// Just call the refresh function
			pb_backupbuddy::$classes['zipbuddy']->refresh_zip_methods();
			
		}
		
		// Now simply provide the list of methods
		echo implode( ', ', pb_backupbuddy::$classes['zipbuddy']->_zip_methods );
		
		die();
	} // End refresh_zip_methods().
	
	
	
	/* site_size_listing()
	 *
	 * Display site site listing on Server Info page.
	 *
	 */
	public function site_size_listing() {
		
		$exclusions = pb_backupbuddy_core::get_directory_exclusions();
		
		$result = pb_backupbuddy::$filesystem->dir_size_map( ABSPATH, ABSPATH, $exclusions, $dir_array );
		if ( 0 == $result ) {
			pb_backupbuddy::alert( 'Error #5656653. Unable to access directory map listing for directory `' . ABSPATH . '`.' );
			die();
		}
		$total_size = pb_backupbuddy::$options['stats']['site_size'] = $result[0];
		$total_size_excluded = pb_backupbuddy::$options['stats']['site_size_excluded'] = $result[1];
		pb_backupbuddy::$options['stats']['site_size_updated'] = time();
		pb_backupbuddy::save();
		
		arsort( $dir_array );
		
		if ( pb_backupbuddy::_GET( 'text' ) == 'true' ) {
			pb_backupbuddy::$ui->ajax_header();
			echo '<h3>' . __( 'Site Size Listing & Exclusions', 'it-l10n-backupbuddy' ) . '</h3>';
			echo '<textarea style="width:100%; height: 300px; font-family: monospace;" wrap="off">';
			echo __('Size + Children', 'it-l10n-backupbuddy' ) . "\t";
			echo __('- Exclusions', 'it-l10n-backupbuddy' ) . "\t";
			echo __('Directory', 'it-l10n-backupbuddy' ) . "\n";
		} else {
			?>
			<table class="widefat">
				<thead>
					<tr class="thead">
						<?php
							echo '<th>', __('Directory', 'it-l10n-backupbuddy' ), '</th>',
								 '<th>', __('Size with Children', 'it-l10n-backupbuddy' ), '</th>',
								 '<th>', __('Size with Exclusions', 'it-l10n-backupbuddy' ), '</th>';
						?>
					</tr>
				</thead>
				<tfoot>
					<tr class="thead">
						<?php
							echo '<th>', __('Directory', 'it-l10n-backupbuddy' ), '</th>',
								 '<th>', __('Size with Children', 'it-l10n-backupbuddy' ), '</th>',
								 '<th>', __('Size with Exclusions', 'it-l10n-backupbuddy' ), '</th>';
						?>
					</tr>
				</tfoot>
				<tbody>
			<?php
		}
		if ( pb_backupbuddy::_GET( 'text' ) == 'true' ) {
				echo str_pad( pb_backupbuddy::$format->file_size( $total_size ), 10, ' ', STR_PAD_RIGHT ) . "\t" . str_pad( pb_backupbuddy::$format->file_size( $total_size_excluded ), 10, ' ', STR_PAD_RIGHT ) . "\t" . __( 'TOTALS', 'it-l10n-backupbuddy' ) . "\n";
		} else {
			echo '<tr><td align="right"><b>' . __( 'TOTALS', 'it-l10n-backupbuddy' ) . ':</b></td><td><b>' . pb_backupbuddy::$format->file_size( $total_size ) . '</b></td><td><b>' . pb_backupbuddy::$format->file_size( $total_size_excluded ) . '</b></td></tr>';
		}
		$item_count = 0;
		foreach ( $dir_array as $id => $item ) { // Each $item is in format array( TOTAL_SIZE, TOTAL_SIZE_TAKING_EXCLUSIONS_INTO_ACCOUNT );
			$item_count++;
			if ( $item_count > 100 ) {
				flush();
				$item_count = 0;
			}
			if ( $item[1] === false ) {
				if ( pb_backupbuddy::_GET( 'text' ) == 'true' ) {
					$excluded_size = 'EXCLUDED';
					echo '**';
				} else {
					$excluded_size = '<span class="pb_label pb_label-important">Excluded</span>';
					echo '<tr style="background: #fcc9c9;">';
				}
			} else {
				$excluded_size = pb_backupbuddy::$format->file_size( $item[1] );
				if ( pb_backupbuddy::_GET( 'text' ) != 'true' ) {
					echo '<tr>';
				}
			}
			if ( pb_backupbuddy::_GET( 'text' ) == 'true' ) {
				echo str_pad( pb_backupbuddy::$format->file_size( $item[0] ), 10, ' ', STR_PAD_RIGHT ) . "\t" . str_pad( $excluded_size, 10, ' ', STR_PAD_RIGHT ) . "\t" . $id . "\n";
			} else {
				echo '<td>' . $id . '</td><td>' . pb_backupbuddy::$format->file_size( $item[0] ) . '</td><td>' . $excluded_size . '</td></tr>';
			}
		}
		if ( pb_backupbuddy::_GET( 'text' ) == 'true' ) {
				echo str_pad( pb_backupbuddy::$format->file_size( $total_size ), 10, ' ', STR_PAD_RIGHT ) . "\t" . str_pad( pb_backupbuddy::$format->file_size( $total_size_excluded ), 10, ' ', STR_PAD_RIGHT ) . "\t" . __( 'TOTALS', 'it-l10n-backupbuddy' ) . "\n";
		} else {
			echo '<tr><td align="right"><b>' . __( 'TOTALS', 'it-l10n-backupbuddy' ) . ':</b></td><td><b>' . pb_backupbuddy::$format->file_size( $total_size ) . '</b></td><td><b>' . pb_backupbuddy::$format->file_size( $total_size_excluded ) . '</b></td></tr>';
		}
		if ( pb_backupbuddy::_GET( 'text' ) == 'true' ) {
			echo "\n\nEXCLUSIONS (" . count( $exclusions ) . "):" . "\n" . implode( "\n", $exclusions );
			echo '</textarea>';
			pb_backupbuddy::$ui->ajax_footer();
		} else {
			echo '</tbody>';
			echo '</table>';
			
			echo '<br>';
			echo 'Exclusions (' . count( $exclusions ) . ')';
			pb_backupbuddy::tip( 'List of directories that will be excluded in an actual backup. This includes user-defined directories and BackupBuddy directories such as the archive directory and temporary directories.' );
			echo '<div id="pb_backupbuddy_serverinfo_exclusions" style="background-color: #EEEEEE; padding: 4px; float: right; white-space: nowrap; height: 90px; width: 70%; min-width: 400px; overflow: auto;"><i>' . implode( "<br>", $exclusions ) . '</i></div>';
			echo '<br style="clear: both;">';
			echo '<br><center>';
			echo '<a href="' . pb_backupbuddy::ajax_url( 'site_size_listing' ) . '&text=true&#038;TB_iframe=1&#038;width=640&#038;height=600" class="thickbox button secondary-button">' . __( 'Display Results in Text Format', 'it-l10n-backupbuddy' ) . '</a>';
			echo '</center>';
		}
		die();
		
	} // End site_size_listing().
	
	
	
	function stop_backup() {
		
		$serial = pb_backupbuddy::_POST( 'serial' );
		set_transient( 'pb_backupbuddy_stop_backup-' . $serial, true, ( 60*60*24 ) );
		
		die( '1' );
		
	} // End stop_backup().
	
	
	
	function quickstart_stash_test() {
		die( 'Not yet implemented.' );
	} // End quickstart_stash_test().
	
	
	
	/* quickstart_form()
	 *
	 * Quickstart form on Getting Started page form saving.
	 *
	 */
	function quickstart_form() {
		
		$errors = array();
		$form = pb_backupbuddy::_POST();
		//print_r( $form );
		
		if ( ( '' != $form['email'] ) && ( false !== stristr( $form['email'], '@' ) ) ) {
			pb_backupbuddy::$options['email_notify_error'] = strip_tags( $form['email'] );
		} else {
			$errors[] = 'Invalid email address.';
		}
		
		if ( ( '' != $form['password'] ) && ( $form['password'] == $form['password_confirm'] ) ) {
			pb_backupbuddy::$options['importbuddy_pass_hash'] = md5( $form['password'] );
			pb_backupbuddy::$options['importbuddy_pass_length'] = strlen( $form['password'] );
			pb_backupbuddy::$options['repairbuddy_pass_hash'] = md5( $form['password'] );
			pb_backupbuddy::$options['repairbuddy_pass_length'] = strlen( $form['password'] );
		} elseif ( '' == $form['password'] ) {
			$errors[] = 'Please enter a password for restoring / migrating.';
		} else {
			$errors[] = 'Passwords do not match.';
		}
		
		if ( '' != $form['schedule'] ) {
			$destination_id = '';
			if ( '' != $form['destination_id'] ) { // Dest id explicitly set.
				$destination_id = $form['destination_id'];
			} else { // No explicit destination ID; deduce it.
				if ( '' != $form['destination'] ) {
					foreach( pb_backupbuddy::$options['remote_destinations'] as $destination_index => $destination ) { // Loop through ending with the last created destination of this type.
						if ( $destination['type'] == $form['destination'] ) {
							$destination_id = $destination_index;
						}
					}
				}
			}
			
			function pb_backupbuddy_schedule_exist_by_title( $title ) {
				foreach( pb_backupbuddy::$options['schedules'] as $schedule ) {
					if ( $schedule['title'] == $title ) {
						return true;
					}
				}
				return false;
			}
			
			// STARTER
			if ( 'starter' == $form['schedule'] ) {
				
				$title = 'Weekly Database (Quick Setup - Starter)';
				if ( false === pb_backupbuddy_schedule_exist_by_title( $title ) ) {
					$add_response = pb_backupbuddy::$classes['core']->add_backup_schedule(
						$title,
						$type = 'db',
						$interval = 'weekly',
						$first_run = ( time() + ( get_option( 'gmt_offset' ) * 3600 ) + 86400 ),
						$remote_destinations = array( $destination_id )
					);
					if ( true !== $add_response ) { $errors[] = $add_response; }
				}
				
				$title = 'Monthly Full (Quick Setup - Starter)';
				if ( false === pb_backupbuddy_schedule_exist_by_title( $title ) ) {
					$add_response = pb_backupbuddy::$classes['core']->add_backup_schedule(
						$title,
						$type = 'full',
						$interval = 'monthly',
						$first_run = ( time() + ( get_option( 'gmt_offset' ) * 3600 ) + 86400 + 18000 ),
						$remote_destinations = array( $destination_id )
					);
					if ( true !== $add_response ) { $errors[] = $add_response; }
				}
				
			}
			
			// BLOGGER
			if ( 'blogger' == $form['schedule'] ) {
				
				$title = 'Weekly Database (Quick Setup - Blogger)';
				if ( false === pb_backupbuddy_schedule_exist_by_title( $title ) ) {
					$add_response = pb_backupbuddy::$classes['core']->add_backup_schedule(
						$title,
						$type = 'db',
						$interval = 'daily',
						$first_run = ( time() + ( get_option( 'gmt_offset' ) * 3600 ) + 86400 ),
						$remote_destinations = array( $destination_id )
					);
					if ( true !== $add_response ) { $errors[] = $add_response; }
				}
				
				$title = 'Monthly Full (Quick Setup - Blogger)';
				if ( false === pb_backupbuddy_schedule_exist_by_title( $title ) ) {
					$add_response = pb_backupbuddy::$classes['core']->add_backup_schedule(
						$title,
						$type = 'full',
						$interval = 'weekly',
						$first_run = ( time() + ( get_option( 'gmt_offset' ) * 3600 ) + 86400 + 18000 ),
						$remote_destinations = array( $destination_id )
					);
					if ( true !== $add_response ) { $errors[] = $add_response; }
				}
				
			}
			
			
		} // end set schedule.
		
		
		if ( 0 == count( $errors ) ) {
			pb_backupbuddy::save();
			die( 'Success.' );
		} else {
			die( implode( "\n", $errors ) );
		}
		
	} // End quickstart_form().
	
	
	
} // end class.
?>
