<?php
if ( ! defined( 'PB_IMPORTBUDDY' ) || ( true !== PB_IMPORTBUDDY ) ) {
	die( '<html></html>' );
}

$ITXAPI_KEY = 'ixho7dk0p244n0ob';
$ITXAPI_URL = 'http://api.ithemes.com';

// Handle small size PHP upload limit knocking off authentication when uploading a backup.
if ( isset( $_SERVER['CONTENT_LENGTH'] ) && ( intval( $_SERVER['CONTENT_LENGTH'] ) > 0 ) && ( count( $_POST ) === 0 ) ) {
	pb_backupbuddy::alert( 'Error #5484548595. Unable to upload. Your PHP post_max_size setting is too small so it discarded POST data. You may have to log back in.', true );
}

$step = '1';
if ( true !== Auth::is_authenticated() ) { // Need authentication.
	$page_title = 'Authentication Required';
} else {
	$page_title = 'Choose your backup file';
}
require_once( '_header.php' );
?>

<script type="text/javascript" src="importbuddy/js/jquery.leanModal.min.js"></script>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('.leanModal').leanModal(
			{ top : 20, overlay : 0.4, closeButton: ".modal_close" }
		);
	});
</script>

<?php
echo pb_backupbuddy::$classes['import']->status_box( 'Step 1 debugging information for ImportBuddy ' . pb_backupbuddy::settings( 'version' ) . ' from BackupBuddy v' . pb_backupbuddy::$options['bb_version'] . '...', true );
?>

<div class="wrap">

<?php
if ( true !== Auth::is_authenticated() ) { // Need authentication.
	if ( pb_backupbuddy::_POST( 'password' ) != '' ) {
		global $pb_login_attempts;
		pb_backupbuddy::alert( 'Invalid password. Please enter the password you provided within BackupBuddy Settings. Attempt #' . $pb_login_attempts . '.' );
		echo '<br>';
	}
	?>
	Enter your ImportBuddy password to continue.
	<br><br>
	<form method="post" action="?step=1<?php if ( pb_backupbuddy::_GET( 'skip_serverinfo' ) != '' ) { echo '&skip_serverinfo=true'; } ?>">
		<input type="password" name="password">
		<input type="submit" name="submit" value="Authenticate" class="button">
	</form>
	
	</div><!-- /wrap -->
<?php
} else {
	upload(); // Handle any uploading of a backup file.
	$backup_archives = get_archives_list();
	//print_r( $backup_archives );
	$wordpress_exists = wordpress_exists();
	?>
	
	
	
	Select a backup to restore from this server, Stash, or upload a backup below. Throughout the restore process you may hover over question marks
	<?php pb_backupbuddy::tip( 'This is an example help tip. Hover over these for additional help.' ); ?> 
	for additional help. For support see the <a href="http://ithemes.com/codex/page/BackupBuddy" target="_blank">Knowledge Base</a>
	or <a href="http://pluginbuddy.com/support/" target="_blank">Support Forum</a>.
	<br><br>
	
	
	
	<?php
	
	if ( pb_backupbuddy::_GET( 'file' ) != '' ) {
		echo '
		<div style="padding: 15px; background: #FFFFFF;">Restoring from backup <i>' . htmlentities( pb_backupbuddy::_GET( 'file' ) ) . '</i></div>
		<form action="?step=2" method="post">
			<input type="hidden" name="pass_hash" value="' . PB_PASSWORD . '" />
			<input type="hidden" name="options" value="' . htmlspecialchars( serialize( pb_backupbuddy::$options ) ) . '" />
			<input type="hidden" name="file" value="' . htmlspecialchars( pb_backupbuddy::_GET( 'file' ) ) . '">
		';
				
	} else {
		
		/********* Start warnings for existing files. *********/
		if ( wordpress_exists() === true ) {
			pb_backupbuddy::alert( 'WARNING: Existing WordPress installation found. It is strongly recommended that existing WordPress files and database be removed prior to migrating or restoring to avoid conflicts. You should not install WordPress prior to migrating.' );
		}
		if ( phpini_exists() === true ) {
			pb_backupbuddy::alert( 'WARNING: Existing php.ini file found. If your backup also contains a php.ini file it may overwrite the current one, possibly resulting in changes in cofiguration or problems. Make a backup of your existing file if your are unsure.' );
		}
		if ( htaccess_exists() === true ) {
			pb_backupbuddy::alert( 'WARNING: Existing .htaccess file found. If your backup also contains a .htaccess file it may overwrite the current one, possibly resulting in changed in configuration or problems. Make a backup of your existing file if you are unsure.' );
		}
		
		// Look for directories named after a backup file that contain WordPress.
		$backup_dirs = glob( ABSPATH . 'backup-*/wp-login.php' );
		if ( ! is_array( $backup_dirs ) ) {
			$backup_dirs = array();
		}
		if ( count( $backup_dirs ) > 0 ) {
			pb_backupbuddy::alert( 'A manually unzipped backup may have been found in the following location(s). If you manually unzipped confirm the files were not unzipped into this subdirectory else they will need to be moved up out of the subdirectory into the same directory as importbuddy.php. Possible manually unzipped backups in a subdirectory: ' . implode( ', ', $backup_dirs ) );
		}
		
		
		echo '<br><br>';
		?>
		
		<div id="pluginbuddy-tabs">
			<ul>
				<li><a href="#pluginbuddy-tabs-server"><span>Server</span></a></li>
				<li><a href="#pluginbuddy-tabs-upload"><span>Upload</span></a></li>
				<li><a href="#pluginbuddy-tabs-stash"><span>Stash</span></a></li>
			</ul>
			<div id="pluginbuddy-tabs-stash">
				<div class="tabs-item">
					
					<?php
					
						//print_r( $_POST );
					
						$credentials_form = new pb_backupbuddy_settings( 'pre_settings', false, 'step=1&upload=stash#pluginbuddy-tabs-stash' ); // name, savepoint|false, additional querystring
						
						$credentials_form->add_setting( array(
							'type'		=>		'hidden',
							'name'		=>		'pass_hash',
							'default'	=>		PB_PASSWORD,
						) );
						$credentials_form->add_setting( array(
							'type'		=>		'hidden',
							'name'		=>		'options',
							'default'	=>		htmlspecialchars( serialize( pb_backupbuddy::$options ) ),
						) );
						
						$credentials_form->add_setting( array(
							'type'		=>		'text',
							'name'		=>		'itxapi_username',
							'title'		=>		__( 'iThemes username', 'it-l10n-backupbuddy' ),
							'rules'		=>		'required|string[1-45]',
						) );
						$credentials_form->add_setting( array(
							'type'		=>		'password',
							'name'		=>		'itxapi_password_raw',
							'title'		=>		__( 'iThemes password', 'it-l10n-backupbuddy' ),
							'rules'		=>		'required|string[1-45]',
						) );
						
						$settings_result = $credentials_form->process();
						$login_welcome = __( 'Connect to Stash with your iThemes.com member account to select a backup to restore.', 'it-l10n-backupbuddy' ) . '<br><br>';
						
						if ( count( $settings_result ) == 0 ) { // No form submitted.
							
							echo $login_welcome;
							$credentials_form->display_settings( 'Connect to Stash' );
						} else { // Form submitted.
							if ( count( $settings_result['errors'] ) > 0 ) { // Form errors.
								echo $login_welcome;
								
								pb_backupbuddy::alert( implode( '<br>', $settings_result['errors'] ) );
								$credentials_form->display_settings( 'Connect to Stash' );
								
							} else { // No form errors; process!
								
								
								$itx_helper_file = dirname( dirname( __FILE__ ) ) . '/classes/class.itx_helper.php';
								require_once( $itx_helper_file );
								
								$itxapi_username = $settings_result['data']['itxapi_username'];
								$itxapi_password = ITXAPI_Helper::get_password_hash( $itxapi_username, $settings_result['data']['itxapi_password_raw'] ); // Generates hash for use as password for API.
								
								
								$requestcore_file = dirname( dirname( __FILE__ ) ) . '/lib/requestcore/requestcore.class.php';
								require_once( $requestcore_file );
								
								
								$stash = new ITXAPI_Helper( $ITXAPI_KEY, $ITXAPI_URL, $itxapi_username, $itxapi_password );
								
								$files_url = $stash->get_files_url();
								
								$request = new RequestCore( $files_url );
								$response = $request->send_request(true);
								
								// See if the request was successful.
								if(!$response->isOK())
									pb_backupbuddy::status( 'error', 'Stash request for files failed.' );
								
								// See if we got a json response.
								if(!$stash_files = json_decode($response->body, true))
									pb_backupbuddy::status( 'error', 'Stash did not get valid json response.' );
								
								// Finally see if the API returned an error.
								if(isset($stash_files['error'])) {            
									if ( $stash_files['error']['code'] == '3002' ) {
										pb_backupbuddy::alert( 'Invalid iThemes.com Member account password. Please verify your password. <a href="http://ithemes.com/member/member.php" target="_new">Forget your password?</a>' );
									} else {
										pb_backupbuddy::alert( implode( ' - ', $stash_files['error'] ) );
									}
									
									$credentials_form->display_settings( 'Submit' );
								} else { // NO ERRORS
									
									/*
									echo '<pre>';
									print_r( $stash_files );
									echo '</pre>';
									*/
									
									$backup_list_temp = array();
									foreach( $stash_files['files'] as $stash_file ) {
										$file = $stash_file['filename'];
										$url = $stash_file['link'];
										$size = $stash_file['size'];
										$modified = $stash_file['last_modified'];
										
										if ( substr( $file, 0, 3 ) == 'db/' ) {
											$backup_type = 'Database';
										} elseif ( substr( $file, 0, 5 ) == 'full/' ) {
											$backup_type = 'Full';
										} elseif( $file == 'importbuddy.php' ) {
											$backup_type = 'ImportBuddy Tool';
										} else {
											if ( stristr( $file, '/db/' ) !== false ) {
												$backup_type = 'Database';
											} elseif( stristr( $file, '/full/' ) !== false ) {
												$backup_type = 'Full';
											} else {
												$backup_type = 'Unknown';
											}
										}
										
										$backup_list_temp[ $modified ] = array(
											$url,
											$file,
											pb_backupbuddy::$format->date( pb_backupbuddy::$format->localize_time( $modified ) ) . '<br /><span class="description">(' . pb_backupbuddy::$format->time_ago( $modified ) . ' ago)</span>',
											pb_backupbuddy::$format->file_size( $size ),
											$backup_type,
										);
									}
									
									krsort( $backup_list_temp );
									
									$backup_list = array();
									foreach( $backup_list_temp  as $backup_item ) {
										$backup_list[ $backup_item[0] ] = array(
											$backup_item[1],
											$backup_item[2],
											$backup_item[3],
											$backup_item[4],
											'<form action="?step=1#pluginbuddy-tabs-server" method="POST">
												<input type="hidden" name="pass_hash" value="' . PB_PASSWORD . '">
												<input type="hidden" name="upload" value="stash">
												<input type="hidden" name="options" value="' . htmlspecialchars( serialize( pb_backupbuddy::$options ) ) . '">
												<input type="hidden" name="link" value="' . $backup_item[0] . '">
												<input type="hidden" name="itxapi_username" value="' . $itxapi_username . '">
												<input type="hidden" name="itxapi_password" value="' . $itxapi_password . '">
												<input type="submit" name="submit" value="Import" class="button-primary">
											</form>
											'
										);
									}
									unset( $backup_list_temp );
									
									
									// Render table listing files.
									if ( count( $backup_list ) == 0 ) {
										echo '<b>';
										_e( 'You have not sent any backups to Stash yet (or files are still transferring).', 'it-l10n-backupbuddy' );
										echo '</b>';
									} else {
										echo 'Select a backup to import from Stash (beta feature):<br><br>';
										pb_backupbuddy::$ui->list_table(
											$backup_list,
											array(
												//'action'		=>	pb_backupbuddy::page_url() . '&custom=remoteclient&destination_id=' . htmlentities( pb_backupbuddy::_GET( 'destination_id' ) ) . '&remote_path=' . htmlentities( pb_backupbuddy::_GET( 'remote_path' ) ),
												'columns'		=>	array( 'Backup File', 'Uploaded <img src="' . pb_backupbuddy::plugin_url() . '/images/sort_down.png" style="vertical-align: 0px;" title="Sorted most recent first">', 'File Size', 'Type', '&nbsp;' ),
												'css'			=>		'width: 100%;',
											)
										);
									}
									
									
									
									if ( $stash_files === false ) {
										$credentials_form->display_settings( 'Submit' );
									}
								} // end no errors getting file info from API.
								
							}
							
						} // end form submitted.
					?>
					
					<br><br>
					<i>You can manage your Stash backups at <a href="http://ithemes.com/member/stash.php">http://ithemes.com/member/stash.php</a></i>
					
				</div>
			</div>
			<div class="tabs-borderwrap">
				
				<div id="pluginbuddy-tabs-upload">
					<div class="tabs-item">
						<form enctype="multipart/form-data" action="?step=1" method="POST">
							<input type="hidden" name="pass_hash" value="<?php echo PB_PASSWORD; ?>">
							<input type="hidden" name="upload" value="local">
							<input type="hidden" name="options" value="<?php echo htmlspecialchars( serialize( pb_backupbuddy::$options ) ); ?>'">
							Choose a local backup from your computer to upload to this server.<br><br>
							<p>
								<input name="file" type="file" style="width: 100%;">
							</p>
							<br>
							<input type="submit" value="Upload Backup" class="toggle button">
						</form>
						
						<br><br>
						<i>If you have trouble with uploads from this page use FTP to upload backups instead.</i>
					</div>
				</div>
				
				<div id="pluginbuddy-tabs-server">
					<div class="tabs-item">
						<?php
						if ( empty( $backup_archives ) ) { // No backups found.
							
							// Look for manually unzipped
							pb_backupbuddy::alert( '<b>No BackupBuddy Zip backup found in directory</b> - 
								You must upload a backup file by FTP (into the same directory as this importbuddy.php file), the upload tab, or import from Stash via the Stash tab above to continue.
								Do not rename the backup file. If you manually extracted/unzipped, upload the backup file,
								select it, then select <i>Advanced Troubleshooting Options</i> & click <i>Skip Zip Extraction</i>. Refresh this page once you have uploaded the backup.' );
							
						} else { // Found one or more backups.
							?>
								<form action="?step=2" method="post">
									<input type="hidden" name="pass_hash" value="<?php echo PB_PASSWORD; ?>">
									<input type="hidden" name="options" value="<?php echo htmlspecialchars( serialize( pb_backupbuddy::$options ) ); ?>'" />
							<?php
							echo '<div class="backup_select_text">Select backup from <div style="display: inline-block; max-width: 500px; overflow: scroll; vertical-align: -3px;">' . ABSPATH . '</div></div>';
							echo '<br>';
							echo '<ul style="list-style-type: none; margin: 0; padding: 0;">';
							$backup_count = count( $backup_archives );
							$i = 0;
							foreach( $backup_archives as $backup_id => $backup_archive ) {
								$i++;
								echo '<li style="padding-top: 8px; padding-bottom: 8px;';
								if ( $i < $backup_count ) {
									echo ' border-bottom: 1px solid #DFDFDF;';
								}
								echo '"><input type="radio" ';
								if ( $backup_id == 0 ) {
									echo 'checked="checked" ';
								}
								echo 'name="file" value="' . $backup_archive['file'] . '"> ' . $backup_archive['file'];
								echo '<span style="float: right;">' . pb_backupbuddy::$format->file_size( filesize( ABSPATH . $backup_archive['file'] ) ) . '</span>';
								echo '<br>';
								
								echo '<div class="description" style="margin-left: 22px; margin-top: 6px; font-style: normal; line-height: 26px;">';
								$meta = array();
								
								if ( $backup_archive['comment']['type'] == '' ) {
									if ( stristr( $backup_archive['file'], '-db-' ) !== false ) {
										echo 'Database Backup';
									} elseif ( stristr( $backup_archive['file'], '-full-' ) !== false ) {
										echo 'Full Backup';
									}
								} else {
									if ( $backup_archive['comment']['type'] == 'db' ) {
										echo 'Database Backup';
									} elseif ( $backup_archive['comment']['type'] == 'full' ) {
										echo 'Full Backup';
									} else {
										echo $backup_archive['comment']['type'] . ' Backup';
									}
								}
								
								if ( $backup_archive['comment']['created'] != '' ) {
									echo ' from ' . pb_backupbuddy::$format->date( $backup_archive['comment']['created'] );
								}
								
								if ( $backup_archive['comment']['wp_version'] != '' ) {
									echo ' on WordPress v' . $backup_archive['comment']['wp_version'];
								}
								if ( $backup_archive['comment']['bb_version'] != '' ) {
									echo ' & BackupBuddy v' . $backup_archive['comment']['bb_version'];
								}
								
								if ( $backup_archive['comment']['siteurl'] != '' ) {
									echo '<br>Site: ' . $backup_archive['comment']['siteurl'] . '<br>';
								}
								
								if ( $backup_archive['comment']['note'] != '' ) {
									echo '<br>Note: ' . htmlentities( $backup_archive['comment']['note'] ) . '<br>';
								}
								
								//echo implode( ' - ', $meta );
								echo '</div>';
								echo '</li>';
							}
							echo '</ul>';
						}
						?>
						
						
					</div>
				</div>
				

				
			</div>
		</div>
		<br>
	<?php } // End file not given in querystring.
	
	
	
	// If one or more backup files was found then provide a button to continue.
	if ( !empty( $backup_archives ) ) {
		echo '</div><!-- /wrap -->';
		echo '<div class="main_box_foot">';
		echo '<a href="#pb_serverinfo_modal" class="button button-tertiary leanModal" style="float: left; font-size: 13px; margin-right: 5px;">Server Information</a>';
		echo '<a href="#pb_advanced_modal" class="button button-tertiary leanModal" style="float: left; font-size: 13px;">Advanced Options</a>';
		echo '<input type="submit" name="submit" value="Next Step &rarr;" class="button">';
		echo '</div>';
	} else {
		//pb_backupbuddy::alert( 'Upload a backup file to continue.' );
		echo '<b>You must upload a backup file by FTP, the upload tab, or import from Stash to continue.</b>';
		echo '</div><!-- /wrap -->';
	}
	
	?>

	<div id="pb_advanced_modal" style="display: none;">
		<div class="modal">
			<div class="modal_header">
				<a class="modal_close">X</a>
				<h2>Advanced Options</h2>
				These advanced options allow customization of various ImportBuddy functionality for custom purposes or troubleshooting.
				<b>Exercise caution</b> as some advanced options may have unforeseen effects if not used properly, such as overwriting existing files
				or erasing existing database content.
			</div>
			<div class="modal_content">
				
				
				
				<br><b>ZIP Archive Extraction (Step 2)</b><br>
				<input type="checkbox" value="on" name="skip_files"> Skip zip file extraction. <?php pb_backupbuddy::tip( 'Checking this box will prevent extraction/unzipping of the backup ZIP file.  You will need to manually extract it either on your local computer then upload it or use a server-based tool such as cPanel to extract it. This feature is useful if the extraction step is unable to complete for some reason.' ); ?><br>
				<input type="checkbox" value="on" name="force_compatibility_medium" /> Force medium speed compatibility mode (ZipArchive). <br>
				<input type="checkbox" value="on" name="force_compatibility_slow" /> Force slow speed compatibility mode (PCLZip). <br>
				
				<br><b>Database Import & Migration (Steps 3-5)</b><br>
				<span style="width: 16px; display: inline-block;"></span> <i>Select the "Adanced Options" button while on Step 3 for database options.</i><br>
				
				<br><b>General</b><br>
				<input type="checkbox" value="on" name="skip_htaccess"> Skip migration of .htaccess file. <br>
				<?php //<input type="checkbox" name="force_high_security"> Force high security on a normal security backup<br> ?>
				<input type="checkbox" value="on" name="show_php_warnings" /> Show detailed PHP warnings. <br>
				
				<br>
				Import Logging: <select name="log_level">
					<option value="0">None</option>
					<option value="1">Errors Only</option>
					<option value="2">Errors & Warnings</option>
					<option value="3" selected>Everything (default)</option>
				</select> <?php pb_backupbuddy::tip( 'Errors and other debugging information will be written to importbuddy.txt in the same directory as importbuddy.php.  This is useful for debugging any problems encountered during import.  Support may request this file to aid in tracking down any problems or bugs.' ); ?>
				
				
				
			</div>
		</div>
	</div>
	
	
	
	<div id="pb_serverinfo_modal" style="display: none; height: 90%;">
		<div class="modal">
			<div class="modal_header">
				<a class="modal_close">X</a>
				<h2>Server Information</h2>
			</div>
			<div class="modal_content">
				
				
				
				<?php
				global $detected_max_execution_time;
				$server_info_file = ABSPATH . 'importbuddy/controllers/pages/server_info.php';
				if ( file_exists( $server_info_file ) ) {
					require_once( $server_info_file );
				} else {
					echo '{Error: Missing server tools file `' . $server_info_file . '`.}';
				}
				?>
				
				
				
			</div>
		</div>
	</div>
	
	
	
<?php
	echo '</form>';
}
require_once( '_footer.php' );
?>
