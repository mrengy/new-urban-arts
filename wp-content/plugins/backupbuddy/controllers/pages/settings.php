<?php
// TODO: move all output into the view.


pb_backupbuddy::load_script( 'filetree.js' );
pb_backupbuddy::load_style( 'filetree.css' );

// Reset settings to defaults.
if ( pb_backupbuddy::_POST( 'reset_defaults' ) != '' ) {
	if ( call_user_func(  'pb_backupbuddy::reset_options', true ) === true ) {
		pb_backupbuddy::$classes['core']->verify_directories(); // Re-verify directories such as backup dir, temp, etc.
		pb_backupbuddy::alert( 'Plugin settings have been reset to defaults.' );
	} else {
		pb_backupbuddy::alert( 'Unable to reset plugin settings. Verify you are running the latest version.' );
	}
}
?>

<style type="text/css">
	.pb_backupbuddy_customize_email_error_row, .pb_backupbuddy_customize_email_scheduled_start_row, .pb_backupbuddy_customize_email_scheduled_complete_row {
		display: none;
	}
</style>
<script type="text/javascript">
	jQuery(document).ready(function() {
		
		// Show options on hover.
		jQuery( '.jqueryFileTree > li a' ).live('mouseover mouseout', function(event) {
			if ( event.type == 'mouseover' ) {
				jQuery(this).children( '.pb_backupbuddy_treeselect_control' ).css( 'visibility', 'visible' );
			} else {
				jQuery(this).children( '.pb_backupbuddy_treeselect_control' ).css( 'visibility', 'hidden' );
			}
		});
		
		
		jQuery('#exlude_dirs').fileTree(
			{
				root: '/',
				multiFolder: false,
				script: '<?php echo pb_backupbuddy::ajax_url( 'exclude_tree' ); ?>'
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
		
		
		/* Begin Table Selector */
		jQuery( '.pb_backupbuddy_table_addexclude' ).click(function(){
			jQuery('#pb_backupbuddy_mysqldump_additional_excludes').val( jQuery(this).parent().parent().parent().find( 'a' ).attr( 'alt' ) + "\n" + jQuery('#pb_backupbuddy_mysqldump_additional_excludes').val() );
			return false;
		});
		jQuery( '.pb_backupbuddy_table_addinclude' ).click(function(){
			jQuery('#pb_backupbuddy_mysqldump_additional_includes').val( jQuery(this).parent().parent().parent().find( 'a' ).attr( 'alt' ) + "\n" + jQuery('#pb_backupbuddy_mysqldump_additional_includes').val() );
			return false;
		});
		
		
		/* Begin Directory / File Selector */
		jQuery( '.pb_backupbuddy_filetree_exclude' ).live( 'click', function(){
			text = jQuery(this).parent().parent().find( 'a' ).attr( 'rel' );
			if ( ( text == 'wp-config.php' ) || ( text == '/wp-content/' ) || ( text == '/wp-content/uploads/' ) || ( text == '<?php echo pb_backupbuddy::$options['backup_directory']; ?>' ) || ( text == '/wp-content/uploads/backupbuddy_temp/' ) ) {
				alert( '<?php _e('You cannot exclude /wp-content/, /wp-content/uploads/, or BackupBuddy directories.  However, you may exclude subdirectories within these. BackupBuddy directories such as backupbuddy_backups are automatically excluded and cannot be added to exclusion list.', 'it-l10n-backupbuddy' );?>' );
			} else {
				jQuery('#pb_backupbuddy_excludes').val( text + "\n" + jQuery('#pb_backupbuddy_excludes').val() );
			}
			return false;
		});
		
		
		
		jQuery( '.pb_backupbuddy_customize_email_scheduled_start' ).click( function() {
			jQuery( '.pb_backupbuddy_customize_email_scheduled_start_row' ).slideToggle();
			return false;
		});
		jQuery( '.pb_backupbuddy_customize_email_scheduled_complete' ).click( function() {
			jQuery( '.pb_backupbuddy_customize_email_scheduled_complete_row' ).slideToggle();
			return false;
		});
		jQuery( '.pb_backupbuddy_customize_email_error' ).click( function() {
			jQuery( '.pb_backupbuddy_customize_email_error_row' ).slideToggle();
			return false;
		});
		
		
		
	});
	
	function pb_backupbuddy_selectdestination( destination_id, destination_title, callback_data ) {
		window.location.href = '<?php echo pb_backupbuddy::page_url(); ?>&custom=remoteclient&destination_id=' + destination_id;
	}
</script>


<?php
pb_backupbuddy::$ui->title( 'BackupBuddy Settings' );
pb_backupbuddy::$classes['core']->versions_confirm();


/* BEGIN VERIFYING BACKUP DIRECTORY */
if ( pb_backupbuddy::_POST( 'pb_backupbuddy_backup_directory' ) != '' ) {
	$backup_directory = pb_backupbuddy::_POST( 'pb_backupbuddy_backup_directory' );
	$backup_directory = str_replace( '\\', '/', $backup_directory );
	$backup_directory = rtrim( $backup_directory, '/\\' ) . '/'; // Enforce single trailing slash.
	if ( ! is_dir( $backup_directory ) ) {
		if ( false === @mkdir( $backup_directory, 0755 ) ) {
			pb_backupbuddy::alert( 'Error #4838594589: Selected backup directory does not exist and it could not be created. Verify the path is correct or manually create the directory and set proper permissions. Reset to default path.' );
			$_POST['pb_backupbuddy_backup_directory'] = pb_backupbuddy::$options['backup_directory']; // Set back to previous value (aka unchanged).
		}
	}
	
	if ( pb_backupbuddy::$options['backup_directory'] != $backup_directory ) { // Directory differs. Needs updated in post var. Give messages here as this value is going to end up being saved.
		pb_backupbuddy::anti_directory_browsing( $backup_directory );
		
		$old_backup_dir = pb_backupbuddy::$options['backup_directory'];
		$new_backup_dir = $backup_directory;
		
		// Move all files from old backup to new.
		$old_backups_moved = 0;
		$old_backups = glob( $old_backup_dir . 'backup*.zip' );
		if ( !is_array( $old_backups ) || empty( $old_backups ) ) { // On failure glob() returns false or an empty array depending on server settings so normalize here.
			$old_backups = array();
		}
		foreach( $old_backups as $old_backup ) {
			if ( false === rename( $old_backup, $new_backup_dir . basename( $old_backup ) ) ) {
				pb_backupbuddy::alert( 'ERROR: Unable to move backup "' . basename( $old_backup ) . '" to new storage directory. Manually move it or delete it for security and to prevent it from being backed up within backups.' );
			} else { // rename success.
				$old_backups_moved++;
				$serial = pb_backupbuddy::$classes['core']->get_serial_from_file( basename( $old_backup ) );
				
				require_once( pb_backupbuddy::plugin_path() . '/classes/fileoptions.php' );
				$fileoptions_files = glob( pb_backupbuddy::$options['log_directory'] . 'fileoptions/*.txt' );
				if ( ! is_array( $fileoptions_files ) ) {
					$fileoptions_files = array();
				}
				foreach( $fileoptions_files as $fileoptions_file ) {
					$backup_options = new pb_backupbuddy_fileoptions( $fileoptions_file );
					if ( true !== ( $result = $backup_options->is_ok() ) ) {
						pb_backupbuddy::status( 'error', __('Unable to access fileoptions data.', 'it-l10n-backupbuddy' ) . ' Error: ' . $result );
						continue;
					}
					
					if ( isset( $backup_options->options[ $serial ] ) ) {
						if ( isset( $backup_options->options['archive_file'] ) ) {
							$backup_options->options['archive_file'] = str_replace( $old_backup_dir, $new_backup_dir, $backup_options->options['archive_file'] );
						}
					}
					$backup_options->save();
					unset( $backup_options );
				}
				
			}
		}
		
		$_POST['pb_backupbuddy_backup_directory'] = $backup_directory;
		pb_backupbuddy::alert( 'Your backup storage directory has been updated from "' . $old_backup_dir . '" to "' . $new_backup_dir . '". ' . $old_backups_moved . ' backup(s) have been moved to the new location. You should perform a manual backup to verify that your backup storage directory changes perform as expected.' );
	}
}
/* END VERIFYING BACKUP DIRECTORY */

/* BEGIN DISALLOWING DEFAULT IMPORT/REPAIR PASSWORD */
if ( strtolower( pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) ) == 'myp@ssw0rd' ) {
	pb_backupbuddy::alert( 'Warning: The example password is not allowed for security reasons for ImportBuddy. Please choose another password.' );
	$_POST['pb_backupbuddy_importbuddy_pass_hash'] = '';
}
if ( strtolower( pb_backupbuddy::_POST( 'pb_backupbuddy_repairbuddy_pass_hash' ) ) == 'myp@ssw0rd' ) {
	pb_backupbuddy::alert( 'Warning: The example password is not allowed for security reasons for RepairBuddy. Please choose another password.' );
	$_POST['pb_backupbuddy_repairbuddy_pass_hash'] = '';
}
/* END DISALLOWING DEFAULT IMPORT/REPAIR PASSWORD */



/* BEGIN VERIFYING PASSWORD CONFIRMATIONS MATCH */
$importbuddy_pass_match_fail = false;
if ( pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) != pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash_confirm' ) ) {
	pb_backupbuddy::alert( 'Error: The provided ImportBuddy password and confirmation do not match. Please make sure you type the password and re-type it correctly.' );
	$_POST['pb_backupbuddy_importbuddy_pass_hash'] = '';
	$_POST['pb_backupbuddy_importbuddy_pass_hash_confirm'] = '';
	$importbuddy_pass_match_fail = true;
}

$repairbuddy_pass_match_fail = false;
if ( pb_backupbuddy::_POST( 'pb_backupbuddy_repairbuddy_pass_hash' ) != pb_backupbuddy::_POST( 'pb_backupbuddy_repairbuddy_pass_hash_confirm' ) ) {
	pb_backupbuddy::alert( 'Error: The provided RepairBuddy password and confirmation do not match. Please make sure you type the password and re-type it correctly.' );
	$_POST['pb_backupbuddy_repairbuddy_pass_hash'] = '';
	$_POST['pb_backupbuddy_repairbuddy_pass_hash_confirm'] = '';
	$repairbuddy_pass_match_fail = true;
}
/* END VERIFYING PASSWORD CONFIRMATIONS MATCH */



/* BEGIN REPLACING IMPORTBUDDY/REPAIRBUDDY_PASS_HASH WITH VALUE OF ACTUAL HASH */
// ImportBuddy hash replace.
if ( ( str_replace( ')', '', pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) ) != '' ) && ( md5( pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) ) != pb_backupbuddy::$options['importbuddy_pass_hash'] ) ) {
	//echo 'posted value: ' . pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) . '<br>';	
	//echo 'hash: ' . md5( pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) ) . '<br>';
	pb_backupbuddy::$options['importbuddy_pass_length'] = strlen( pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) );
	$_POST['pb_backupbuddy_importbuddy_pass_hash'] = md5( pb_backupbuddy::_POST( 'pb_backupbuddy_importbuddy_pass_hash' ) );
} else { // Keep the same.
	if ( $importbuddy_pass_match_fail !== true ) { // keep the same
		$_POST['pb_backupbuddy_importbuddy_pass_hash'] = pb_backupbuddy::$options['importbuddy_pass_hash'];
	}
}
// Set importbuddy dummy text to display in form box. Equal length to the provided password.
$importbuddy_pass_dummy_text = str_pad( '', pb_backupbuddy::$options['importbuddy_pass_length'], ')' );



// RepairBuddy hash replace.
if ( ( str_replace( ')', '', pb_backupbuddy::_POST( 'pb_backupbuddy_repairbuddy_pass_hash' ) ) != '' ) && ( md5( pb_backupbuddy::_POST( 'pb_backupbuddy_repairbuddy_pass_hash' ) ) != pb_backupbuddy::$options['repairbuddy_pass_hash'] ) ) {
	pb_backupbuddy::$options['repairbuddy_pass_length'] = strlen( pb_backupbuddy::_POST( 'pb_backupbuddy_repairbuddy_pass_hash' ) );
	$_POST['pb_backupbuddy_repairbuddy_pass_hash'] = md5( pb_backupbuddy::_POST( 'pb_backupbuddy_repairbuddy_pass_hash' ) );
} else { // Keep the same.
	if ( $repairbuddy_pass_match_fail !== true ) { // keep the same
		$_POST['pb_backupbuddy_repairbuddy_pass_hash'] = pb_backupbuddy::$options['repairbuddy_pass_hash'];
	}
}
// Set repairbuddy dummy text to display in form box. Equal length to the provided password.
$repairbuddy_pass_dummy_text = str_pad( '', pb_backupbuddy::$options['repairbuddy_pass_length'], ')' );
/* END REPLACING IMPORTBUDDY/REPAIRBUDDY_PASS_HASH WITH VALUE OF ACTUAL HASH */


/* BEGIN SAVE MULTISITE SPECIFIC SETTINGS IN SET OPTIONS SO THEY ARE AVAILABLE GLOBALLY */
if ( is_multisite() ) {
	// Save multisite export option to the global site/network options for global retrieval.
	$options = get_site_option( 'pb_' . pb_backupbuddy::settings( 'slug' ) );
	$options[ 'multisite_export' ] = pb_backupbuddy::_POST( 'pb_backupbuddy_multisite_export' );
	update_site_option( 'pb_' . pb_backupbuddy::settings( 'slug' ), $options );
	unset( $options );
}
/* END SAVE MULTISITE SPECIFIC SETTINGS IN SET OPTIONS SO THEY ARE AVAILABLE GLOBALLY */



/* BEGIN CONFIGURING PLUGIN SETTINGS FORM */

$settings_form = new pb_backupbuddy_settings( 'settings', '', '', 320 );


$settings_form->add_setting( array(
	'type'		=>		'title',
	'name'		=>		'title_2',
	'title'		=>		__( 'General Options', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::video( 'PmXLw_tS42Q#6', __('General Options Tutorial', 'it-l10n-backupbuddy' ), false ),
) );
$settings_form->add_setting( array(
	'type'		=>		'password',
	'name'		=>		'importbuddy_pass_hash',
	'title'		=>		__('ImportBuddy password', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Example: myp@ssw0rD] - Required password for running the ImportBuddy import/migration script. This prevents unauthorized access when using this tool. You should not use your WordPress password here.', 'it-l10n-backupbuddy' ),
	'value'		=>		$importbuddy_pass_dummy_text,
	'after'		=>		'&nbsp;&nbsp; Confirm: <input type="password" name="pb_backupbuddy_importbuddy_pass_hash_confirm" value="' . $importbuddy_pass_dummy_text . '">',
	//'classes'	=>		'regular-text code',
) );
$settings_form->add_setting( array(
	'type'		=>		'password',
	'name'		=>		'repairbuddy_pass_hash',
	'title'		=>		__('RepairBuddy password', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Example: myp@ssw0rD] - Required password for running the RepairBuddy troubleshooting/repair script. This prevents unauthorized access when using this tool. You should not use your WordPress password here.', 'it-l10n-backupbuddy' ),
	'value'		=>		$repairbuddy_pass_dummy_text,
	'after'		=>		'&nbsp;&nbsp; Confirm: <input type="password" name="pb_backupbuddy_repairbuddy_pass_hash_confirm" value="' . $repairbuddy_pass_dummy_text . '">',
	//'classes'	=>		'regular-text code',
) );

$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'backup_directory',
	'title'		=>		__('Local storage directory for backups', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('Local directory where all backup ZIP files will be saved to. This directory must have proper write and read permissions. Upon changing, any backups in the existing directory will be moved to the new directory. Note: This is only where local backups will be, not remotely stored backups. Remote storage is configured on the Remote Destinations page.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'required',
	'css'		=>		'width: 325px;',
	'after'		=>		' <a style="cursor: pointer;" onClick="jQuery(\'#pb_backupbuddy_backup_directory\').val(\'' . str_replace( '\\', '/', ABSPATH ) . 'wp-content/uploads/backupbuddy_backups/\');">Reset Default</a>',
) );


$settings_form->add_setting( array(
	'type'		=>		'select',
	'name'		=>		'role_access',
	'title'		=>		__('BackupBuddy access permission', 'it-l10n-backupbuddy' ),
	'options'	=>		array(
								'administrator'			=> __( 'Administrator (default)', 'it-l10n-backupbuddy' ),
								'moderate_comments'		=> __( 'Editor (moderate_comments)', 'it-l10n-backupbuddy' ),
								'edit_published_posts'	=> __( 'Author (edit_published_posts)', 'it-l10n-backupbuddy' ),
								'edit_posts'			=> __( 'Contributor (edit_posts)', 'it-l10n-backupbuddy' ),
							),
	'tip'		=>		__('[Default: Administrator] - Allow other user levels to access BackupBuddy. Use extreme caution as users granted access will have FULL access to BackupBuddy and your backups, including remote destinations. This is a potential security hole if used improperly. Use caution when selecting any other user roles or giving users in such roles access. Not applicable to Multisite installations.', 'it-l10n-backupbuddy' ),
	'after'		=>		' <span class="description">Use caution changing from "administrator".</span>',
	'rules'		=>		'required',
) );

$log_file = WP_CONTENT_DIR . '/uploads/pb_' . pb_backupbuddy::settings( 'slug' ) . '/log-' . pb_backupbuddy::$options['log_serial'] . '.txt';
$settings_form->add_setting( array(
	'type'		=>		'select',
	'name'		=>		'log_level',
	'title'		=>		__('Logging / Debug level', 'it-l10n-backupbuddy' ),
	'options'	=>		array(
								'0'		=>		__( 'None', 'it-l10n-backupbuddy' ),
								'1'		=>		__( 'Errors Only', 'it-l10n-backupbuddy' ),
								'2'		=>		__( 'Errors & Warnings', 'it-l10n-backupbuddy' ),
								'3'		=>		__( 'Everything (debug mode)', 'it-l10n-backupbuddy' ),
							),
	'tip'		=>		sprintf( __('[Default: Errors Only] - This option controls how much activity is logged for records or debugging. When in debug mode error emails will contain encrypted debugging data for support. Log file: %s', 'it-l10n-backupbuddy' ), $log_file ),
	'rules'		=>		'required',
) );

$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'max_site_log_size',
	'title'		=>		__('Maximum log file size', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Default: 10 MB] - If the log file exceeds this size then it will be cleared to prevent it from using too much space.' ),
	'rules'		=>		'required',
	'css'		=>		'width: 50px;',
	'after'		=>		' MB',
) );

$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'backup_reminders',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Enable backup reminders', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: enabled] - When enabled links will be displayed upon post or page edits and during WordPress upgrades to remind and allow rapid backing up after modifications or before upgrading.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'',
	'rules'		=>		'required',
) );

$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'archive_name_format',
	'options'	=>		array( 'unchecked' => 'date', 'checked' => 'datetime' ),
	'title'		=>		__( 'Add time in backup file name', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: disabled (date only)] - When enabled your backup filename will display the time the backup was created in addition to the default date. This is useful when making multiple backups in a one day period.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'rules'		=>		'required',
) );




$settings_form->add_setting( array(
	'type'		=>		'title',
	'name'		=>		'title_1',
	'title'		=>		__( 'Email Notification Recipients', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::video( 'PmXLw_tS42Q#6', __('Email Notifications Tutorial', 'it-l10n-backupbuddy' ), false ),
) );

$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'email_notify_scheduled_start',
	'title'		=>		__('Scheduled backup started email recipient(s)', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('Email address to send notifications to upon scheduled backup starting. Use commas to separate multiple email addresses. Notifications will not be sent for remote destination file transfers.', 'it-l10n-backupbuddy' ),
	//'rules'		=>		'string[0-500]',
	'css'		=>		'width: 325px;',
	'after'		=>		' <a href="" class="pb_backupbuddy_customize_email_scheduled_start" style="text-decoration: none;">Customize Email</a>',
) );
$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'email_notify_scheduled_start_subject',
	'title'		=>		' ',
	'rules'		=>		'required|string[1-500]',
	'css'		=>		'width: 360px;',
	'row_class'	=>		'pb_backupbuddy_customize_email_scheduled_start_row',
	'before'	=>		'<span style="display: inline-block; width: 65px;">' . __('Subject', 'it-l10n-backupbuddy' ) . ':</span>',
) );
$settings_form->add_setting( array(
	'type'		=>		'textarea',
	'name'		=>		'email_notify_scheduled_start_body',
	'title'		=>		' ',
	'rules'		=>		'required|string[1-500]',
	'css'		=>		'width: 360px; height: 75px;',
	'row_class'	=>		'pb_backupbuddy_customize_email_scheduled_start_row',
	'before'	=>		'<span style="display: inline-block; width: 65px; float: left;">' . __('Body', 'it-l10n-backupbuddy' ) . ':</span>',
	'after'		=>		'<div style="margin-left: 65px; width: 360px;" class="description">
							Variables: {site_url} {backupbuddy_version} {current_datetime} {message}
						</div>',
) );


$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'email_notify_scheduled_complete',
	'title'		=>		__('Scheduled backup completed email recipient(s)', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('Email address to send notifications to upon scheduled backup completion. Use commas to separate multiple email addresses.', 'it-l10n-backupbuddy' ),
	'css'		=>		'width: 325px;',
	'after'		=>		' <a href="" class="pb_backupbuddy_customize_email_scheduled_complete" style="text-decoration: none;">Customize Email</a>',
) );
$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'email_notify_scheduled_complete_subject',
	'title'		=>		' ',
	'rules'		=>		'required|string[1-500]',
	'css'		=>		'width: 360px;',
	'row_class'	=>		'pb_backupbuddy_customize_email_scheduled_complete_row',
	'before'	=>		'<span style="display: inline-block; width: 65px;">' . __('Subject', 'it-l10n-backupbuddy' ) . ':</span>',
) );
$settings_form->add_setting( array(
	'type'		=>		'textarea',
	'name'		=>		'email_notify_scheduled_complete_body',
	'title'		=>		' ',
	'classes'	=>		'regular-text',
	'rules'		=>		'required|string[1-500]',
	'css'		=>		'width: 360px; height: 75px;',
	'row_class'	=>		'pb_backupbuddy_customize_email_scheduled_complete_row',
	'before'	=>		'<span style="display: inline-block; width: 65px; float: left;">' . __('Body', 'it-l10n-backupbuddy' ) . ':</span>',
	'after'		=>		'<div style="margin-left: 65px; width: 360px;" class="description">
							Variables: {site_url} {backupbuddy_version} {current_datetime} {message}
							{download_link} {backup_size} {backup_type} {backup_file} {backup_serial}
						</div>',
) );



$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'email_notify_error',
	'title'		=>		__('Error notification recipient(s)', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('Email address to send notifications to upon encountering any errors or problems. Use commas to separate multiple email addresses.', 'it-l10n-backupbuddy' ),
	'css'		=>		'width: 325px;',
	'after'		=>		' <a href="" class="pb_backupbuddy_customize_email_error" style="text-decoration: none;">Customize Email</a>',
) );
$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'email_notify_error_subject',
	'title'		=>		' ',
	'rules'		=>		'required|string[1-500]',
	'css'		=>		'width: 360px;',
	'row_class'	=>		'pb_backupbuddy_customize_email_error_row',
	'before'	=>		'<span style="display: inline-block; width: 65px;">' . __('Subject', 'it-l10n-backupbuddy' ) . ':</span>',
) );
$settings_form->add_setting( array(
	'type'		=>		'textarea',
	'name'		=>		'email_notify_error_body',
	'title'		=>		' ',
	'classes'	=>		'regular-text',
	'rules'		=>		'required|string[1-500]',
	'css'		=>		'width: 360px; height: 75px;',
	'row_class'	=>		'pb_backupbuddy_customize_email_error_row',
	'before'	=>		'<span style="display: inline-block; width: 65px; float: left;">' . __('Body', 'it-l10n-backupbuddy' ) . ':</span>',
	'after'		=>		'<div style="margin-left: 65px; width: 360px;" class="description">
							Variables: {site_url} {backupbuddy_version} {current_datetime} {message}
						</div>',
) );



$settings_form->add_setting( array(
	'type'		=>		'title',
	'name'		=>		'title_archivestoragelimits',
	'title'		=>		__( 'Local Archive Storage Limits', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::video( 'PmXLw_tS42Q#45', __('Archive Storage Limits Tutorial', 'it-l10n-backupbuddy' ), false ),
) );
$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'archive_limit',
	'title'		=>		__('Maximum number of local backups to keep', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Example: 10] - Maximum number of local archived backups to store (remote archive limits are configured per destination on their respective settings pages). Any new backups created after this limit is met will result in your oldest backup(s) being deleted to make room for the newer ones. Changes to this setting take place once a new backup is made. Set to zero (0) for no limit.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'required|string[0-500]',
	'css'		=>		'width: 50px;',
	'after'		=>		' backups',
) );
$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'archive_limit_size',
	'title'		=>		__('Maximum size of all local backups combined', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Example: 350] - Maximum size (in MB) to allow your total local archives to reach (remote archive limits are configured per destination on their respective settings pages). Any new backups created after this limit is met will result in your oldest backup(s) being deleted to make room for the newer ones. Changes to this setting take place once a new backup is made. Set to zero (0) for no limit.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'required|string[0-500]',
	'css'		=>		'width: 50px;',
	'after'		=>		' MB',
) );
$settings_form->add_setting( array(
	'type'		=>		'text',
	'name'		=>		'archive_limit_age',
	'title'		=>		__('Maximum age of local backups', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Example: 90] - Maximum age (in days) to allow your local archives to reach (remote archive limits are configured per destination on their respective settings pages). Any backups exceeding this age will be deleted as new backups are created. Changes to this setting take place once a new backup is made. Set to zero (0) for no limit.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'required|string[0-99999]',
	'css'		=>		'width: 50px;',
	'after'		=>		' days',
) );



if ( is_multisite() ) {
	$settings_form->add_setting( array(
		'type'		=>		'title',
		'name'		=>		'title_multisite',
		'title'		=>		__( 'Multisite', 'it-l10n-backupbuddy' ),
	) );
	$settings_form->add_setting( array(
		'type'		=>		'checkbox',
		'name'		=>		'multisite_export',
		'title'		=>		__( 'Allow individual site exports by administrators?', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::video( '_oKGIzzuVzw', __('Multisite export', 'it-l10n-backupbuddy' ), false ),
		'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
		'tip'		=>		__('[Default: disabled] - When enabled individual sites may be exported by Administrators of the individual site. Network Administrators always see this menu (notes with the words SuperAdmin in parentheses in the menu when only SuperAdmins have access to the feature).', 'it-l10n-backupbuddy' ),
		'rules'		=>		'required',
		'after'		=>		'<span class="description"> ' . __( 'Check to extend Site Exporting functionality to subsite Administrators.', 'it-l10n-backupbuddy' ) . '</span>',
	) );
}



$settings_form->add_setting( array(
	'type'		=>		'title',
	'name'		=>		'title_mysqltables',
	'title'		=>		__( 'Database Backup', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::video( 'PmXLw_tS42Q#62', __('Database backup settings', 'it-l10n-backupbuddy' ), false ),
) );

global $wpdb;
$settings_form->add_setting( array(
	'type'		=>		'radio',
	'name'		=>		'backup_nonwp_tables',
	'options'	=>		array( '0' => 'This WordPress\' tables (starts with prefix ' . $wpdb->prefix . ')', '1' => 'All tables in database (including non-WordPress)' ),
	'title'		=>		__( '<b>Default</b> database tables to backup', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: This WordPress\' tables prefix (' . $wpdb->prefix . ')] - Determines the default set of tables to backup.  If this WordPress\' tables is selected then only tables with the same prefix (for example ' . $wpdb->prefix . ' for this installation) will be backed up by default.  If all are selected then all tables will be backed up by default. Additional inclusions & exclusions may be defined below.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'rules'		=>		'required',
) );


function pb_additional_tables() {
	$return = '';
	
	global $wpdb;
	$prefix = $wpdb->prefix;
	$prefix_length = strlen( $wpdb->prefix );
	
	$additional_includes = explode( "\n", pb_backupbuddy::$options['mysqldump_additional_includes'] );
	array_walk( $additional_includes, create_function('&$val', '$val = trim($val);')); 
	$additional_excludes = explode( "\n", pb_backupbuddy::$options['mysqldump_additional_excludes'] );
	array_walk( $additional_excludes, create_function('&$val', '$val = trim($val);')); 

	
	$total_size = 0;
	$total_size_with_exclusions = 0;
	$total_rows = 0;
	$result = mysql_query("SHOW TABLE STATUS");
	while( $rs = mysql_fetch_array( $result ) ) {
		$excluded = true; // Default.
		
		// TABLE STATUS.
		$resultb = mysql_query("CHECK TABLE `{$rs['Name']}`");
		while( $rsb = mysql_fetch_array( $resultb ) ) {
			if ( $rsb['Msg_type'] == 'status' ) {
				$status = $rsb['Msg_text'];
			}
		}
		mysql_free_result( $resultb );
		
		// Fix up row count and average row length for InnoDB engine which returns inaccurate
		// (and changing) values for these
		if ( 'InnoDB' === $rs[ 'Engine' ] ) {
		
			if ( false !== ( $resultc = mysql_query( "SELECT COUNT(1) FROM `{$rs[ 'Name' ]}`" ) ) ) {
			
				if ( false !== ( $row = mysql_fetch_row( $resultc ) ) ) {
				
					if ( 0 < ( $rs[ 'Rows' ] = $row[ 0 ] ) ) {
					
						$rs[ 'Avg_row_length' ] = ( $rs[ 'Data_length' ] / $rs[ 'Rows' ] );
						
					}
					
				}
				
				mysql_free_result( $resultc );
				
			}
			
		}
		
		// TABLE SIZE.
		$size = ( $rs['Data_length'] + $rs['Index_length'] );
		$total_size += $size;
		
		
		// HANDLE EXCLUSIONS.
		if ( pb_backupbuddy::$options['backup_nonwp_tables'] == 0 ) { // Only matching prefix.
			if ( ( substr( $rs['Name'], 0, $prefix_length ) == $prefix ) OR ( in_array( $rs['Name'], $additional_includes ) ) ) {
				if ( !in_array( $rs['Name'], $additional_excludes ) ) {
					$total_size_with_exclusions += $size;
					$excluded = false;
				}
			}
		} else { // All tables.
			if ( !in_array( $rs['Name'], $additional_excludes ) ) {
				$total_size_with_exclusions += $size;
				$excluded = false;
			}
		}
		
		
		
		
		$return .= '<li class="file ext_sql collapsed">';
		$return .= '<a rel="/" alt="' . $rs['Name'] . '">' . $rs['Name'] . ' (' . pb_backupbuddy::$format->file_size( $size ) . ') ';
		if ( true === $excluded ) {
			//$return .= '<span class="pb_label pb_label-important">Excluded</span> ';
		}
		$return .= '<div class="pb_backupbuddy_treeselect_control">';
		$return .= '<img src="' . pb_backupbuddy::plugin_url() . '/images/redminus.png" style="vertical-align: -3px;" title="Add to exclusions..." class="pb_backupbuddy_table_addexclude"> <img src="' . pb_backupbuddy::plugin_url() . '/images/greenplus.png" style="vertical-align: -3px;" title="Add to inclusions..." class="pb_backupbuddy_table_addinclude">';
		$return .= '</div>';
		$return .= '</a>';
		
		
		$return .= '</li>';
		
		
	}
	
	$return = '<div class="jQueryOuterTree" style="position: absolute; height: 160px;"><ul class="jqueryFileTree">' .
				$return .
				'</ul></div>';
	
	return $return;
}
$settings_form->add_setting( array(
	'type'		=>		'textarea',
	'name'		=>		'mysqldump_additional_includes',
	'title'		=>		'Hover tables & click <img src="' . pb_backupbuddy::plugin_url() .'/images/greenplus.png" style="vertical-align: -3px;"> to include, <img src="' . pb_backupbuddy::plugin_url() .'/images/redminus.png" style="vertical-align: -3px;"> to exclude.' . ' ' . pb_additional_tables(),
	'before'	=>		__('<b>Inclusions</b> beyond default', 'it-l10n-backupbuddy' ) . ':',
	//'after'		=>		'<span class="description">' . __( 'One table per line. This may be manually edited.', 'it-l10n-backupbuddy' ) . '</span>',
	'tip'		=>		__('Additional databases tables to include OR exclude IN ADDITION to the DEFAULTS determined by the previous option. You may override defaults with exclusions. Excluding tables may result in an incomplete or broken backup so exercise caution.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'',
	'css'		=>		'width: 100%;',
) );
$settings_form->add_setting( array(
	'type'		=>		'textarea',
	'name'		=>		'mysqldump_additional_excludes',
	//'title'		=>		__('Additional tables to <b>exclude</b>', 'it-l10n-backupbuddy' ) . '<br><span class="description">' . __( 'One table per line.', 'it-l10n-backupbuddy' ) . '</span>',
	'title'		=>		'&nbsp;',
	'before'	=>		__('<b>Exclusions</b> beyond default', 'it-l10n-backupbuddy' ) . ':',
	'after'		=>		'<span class="description">' . __( 'One table per line. This may be manually edited.', 'it-l10n-backupbuddy' ) . '</span>',
	'tip'		=>		__('Additional databases tables to EXCLUDE from the backup. Exclusions are exempted after calculating defaults and additional table includes first. These may include non-WordPress and WordPress tables. WARNING: Excluding WordPress tables results in an incomplete backup and could result in failure in the ability to restore or data loss. Use with caution.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'',
	'css'		=>		'width: 100%;',
) );





$settings_form->add_setting( array(
	'type'		=>		'title',
	'name'		=>		'title_exclusions',
	'title'		=>		__( 'File & Directory Exclusions', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::video( 'PmXLw_tS42Q#94', __('Backup Directory Excluding Tutorial', 'it-l10n-backupbuddy' ), false ),
) );

$settings_form->add_setting( array(
	'type'		=>		'textarea',
	'name'		=>		'excludes',
	'title'		=>		'Click directories to navigate or click <img src="' . pb_backupbuddy::plugin_url() .'/images/redminus.png" style="vertical-align: -3px;"> to exclude.' . ' ' .
						pb_backupbuddy::tip( __('Click on a directory name to navigate directories. Click the red minus sign to the right of a directory to place it in the exclusion list. /wp-content/, /wp-content/uploads/, and BackupBuddy backup & temporary directories cannot be excluded. BackupBuddy directories are automatically excluded.', 'it-l10n-backupbuddy' ), '', false ) .
						'<br><div id="exlude_dirs" class="jQueryOuterTree"></div>',
	//'tip'		=>		,
	'rules'		=>		'string[0-9000]',
	'css'		=>		'width: 100%; height: 135px;',
	'before'	=>		__('Excluded files & directories (relative to WordPress installation directory)' , 'it-l10n-backupbuddy' ) . pb_backupbuddy::tip( __('List paths relative to the WordPress installation directory to be excluded from backups.  You may use the directory selector to the left to easily exclude directories by ctrl+clicking them.  Paths are relative to root, for example: /wp-content/uploads/junk/', 'it-l10n-backupbuddy' ), '', false ) . '<br>',
	'after'		=>		'<span class="description">' . __( 'One file or directory exclusion per line. This may be manually edited.', 'it-l10n-backupbuddy' ) . '</span>',
) );



$settings_form->add_setting( array(
	'type'		=>		'title',
	'name'		=>		'title_troubleshooting',
	'title'		=>		__( 'Troubleshooting & Compatibility', 'it-l10n-backupbuddy' ) . ' ' . pb_backupbuddy::video( 'PmXLw_tS42Q#108', __('Troubleshooting options', 'it-l10n-backupbuddy' ), false ),
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'lock_archives_directory',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Lock archive directory (high security)', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: disabled] - When enabled all downloads of archives via the web will be prevented under all circumstances via .htaccess file. If your server permits it, they will only be unlocked temporarily on click to download. If your server does not support this unlocking then you will have to access the archives via the server (such as by FTP).', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __('Check for enhanced security to block backup downloading.', 'it-l10n-backupbuddy' ) . ' This may<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;result in an inability to download backups while enabled on some servers.</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'delete_archives_pre_backup',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Delete all backup archives prior to backups', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: disabled] - When enabled all local backup archives will be deleted prior to each backup. This is useful if in compatibilty mode to prevent backing up existing files.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __('Check if using compatibilty mode & exclusions are unavailable.', 'it-l10n-backupbuddy' ) . '</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'integrity_check',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__('Perform integrity check on backup files', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Default: enabled] - By default each backup file is checked for integrity and completion the first time it is viewed on the Backup page.  On some server configurations this may cause memory problems as the integrity checking process is intensive.  If you are experiencing out of memory errors on the Backup file listing, you can uncheck this to disable this feature.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __( 'Uncheck if having problems viewing your backup listing.', 'it-l10n-backupbuddy' ) . '</span>',
	'rules'		=>		'required',
) );
// $settings_form->add_setting( array(
// 	'type'		=>		'checkbox',
// 	'name'		=>		'force_compatibility',
// 	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
// 	'title'		=>		__('Force compatibility mode zip', 'it-l10n-backupbuddy' ),
// 	'tip'		=>		__('[Default: disabled] - (WARNING: This forces the potentially slower mode of zip creation. Only use if absolutely necessary. Checking this box can cause backup failures if it is not needed.) Under normal circumstances compatibility mode is automatically entered as needed without user intervention. However under some server configurations the native backup system is unavailable but is incorrectly reported as functioning by the server.  Forcing compatibility may fix problems in this situation by bypassing the native backup system check entirely.', 'it-l10n-backupbuddy' ),
// 	'css'		=>		'',
// 	'after'		=>		'<span class="description"> ' . __('Check if absolutely necessary or directed by support.', 'it-l10n-backupbuddy' ) . '</span>',
// 	'rules'		=>		'required',
// ) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'force_mysqldump_compatibility',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__('Force compatibility mode database dump', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Default: disabled] - WARNING: This forces the potentially slower mode of database dumping. Under normal circumstances mysql dump compatibility mode is automatically entered as needed without user intervention.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __( 'Check if database dumping fails. Pre-v3.x mode.', 'it-l10n-backupbuddy' ) . '</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'skip_database_dump',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__('Skip database dump on backup', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Default: disabled] - (WARNING: This prevents BackupBuddy from backing up the database during any kind of backup. This is for troubleshooting / advanced usage only to work around being unable to backup the database.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __('Check if unable to backup database for some reason.', 'it-l10n-backupbuddy' ) . '</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'include_importbuddy',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__('Include ImportBuddy in full backup archive', 'it-l10n-backupbuddy' ),
	'tip'		=>		__('[Default: enabled] - When enabled, the importbuddy.php file will be included within the backup archive ZIP file.  This file can be used to restore your site.  Inclusion in the ZIP file itself insures you always have access to it. importbuddy.php is only included in full backups and only when this option is enabled.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __('Uncheck to skip adding ImportBuddy to backup archive.', 'it-l10n-backupbuddy' ) . '</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'alternative_zip_2',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Alternative zip system (BETA)', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: Disabled] Use if directed by support.', 'it-l10n-backupbuddy' ) . '</span>',
	'css'		=>		'',
	'after'		=>		'<span class="description"> Check if directed by support.</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'select',
	'name'		=>		'zip_method_strategy',
	'title'		=>		__('Zip method strategy', 'it-l10n-backupbuddy' ),
	'options'	=>		array(
								'1'		=>		__( 'Best Available', 'it-l10n-backupbuddy' ),
								'2'		=>		__( 'All Available', 'it-l10n-backupbuddy' ),
								'3'		=>		__( 'Force Compatibility', 'it-l10n-backupbuddy' ),
							),
	'tip'		=>		__('[Default: Best Only] - Normally use Best Available but if the server is unreliable in this mode can try All Available or Force Compatibility', 'it-l10n-backupbuddy' ),
	'after'		=>		'<span class="description"> ' . __('Select Force Compatibility if absolutely necessary.', 'it-l10n-backupbuddy' ) . '</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'disable_zipmethod_caching',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Disable zip method caching', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: Disabled] Use if directed by support. Bypasses caching available zip methods so they are always displayed in logs. When unchecked BackupBuddy will cache command line zip testing for 12 hours so it does not run too often. This means that your backup status log may not always show the test results unless you disable caching. The methods can always be re-tested and cached by manual action on the Server Information page.', 'it-l10n-backupbuddy' ) . '</span>',
	'css'		=>		'',
	'after'		=>		'<span class="description"> Normally should not be checked. Check if directed by support to see in logs if<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; command line zip is failing to be detected.</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'compression',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Enable zip compression', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: enabled] - ZIP compression decreases file sizes of stored backups. If you are encountering timeouts due to the script running too long, disabling compression may allow the process to complete faster.', 'it-l10n-backupbuddy' ),
	'css'		=>		'',
	'after'		=>		'<span class="description"> ' . __('Uncheck for large sites causing backups to not complete.', 'it-l10n-backupbuddy' ) . '</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'ignore_zip_warnings',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Ignore zip archive warnings', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: Disabled] When enabled BackupBuddy will ignore non-fatal warnings encountered during the backup process such as inability to read or access a file, symlink problems, etc. These non-fatal warnings will still be logged.', 'it-l10n-backupbuddy' ) . '</span>',
	'css'		=>		'',
	'after'		=>		'<span class="description"> Check to ignore non-fatal errors when zipping files.</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'ignore_zip_symlinks',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Ignore/do-not-follow symbolic links', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: Enabled] When enabled BackupBuddy will ignore/not-follow symbolic links encountered during the backup process', 'it-l10n-backupbuddy' ) . '</span>',
	'css'		=>		'',
	'after'		=>		'<span class="description"> Check to ignore/not-follow symbolic links when zipping files.</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'disable_https_local_ssl_verify',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Disable local SSL certificate verification', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: Disabled] When checked, WordPress will skip local https SSL verification.', 'it-l10n-backupbuddy' ) . '</span>',
	'css'		=>		'',
	'after'		=>		'<span class="description"> Check if local SSL verification fails (ie. for loopbacks).</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'save_comment_meta',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Save meta data in comment', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: Enabled] When enabled, BackupBuddy will store general backup information in the ZIP comment header such as Site URL, backup type & time, serial, etc. during backup creation.', 'it-l10n-backupbuddy' ) . '</span>',
	'css'		=>		'',
	'after'		=>		'<span class="description"> Uncheck to skip storing meta data in zip comment.</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'checkbox',
	'name'		=>		'breakout_tables',
	'options'	=>		array( 'unchecked' => '0', 'checked' => '1' ),
	'title'		=>		__( 'Break out big table dumps into steps (beta)', 'it-l10n-backupbuddy' ),
	'tip'		=>		__( '[Default: Disabled] Currently in beta. Breaks up some commonly known database tables to be backed up separately rather than all at once. Helps with larger databases.', 'it-l10n-backupbuddy' ) . '</span>',
	'css'		=>		'',
	'after'		=>		'<span class="description"> Check if you want some commonly known to be large tables dumped in<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;separate steps. This is useful for large databases if dumps are timing out.</span>',
	'rules'		=>		'required',
) );
$settings_form->add_setting( array(
	'type'		=>		'select',
	'name'		=>		'backup_mode',
	'title'		=>		__('Manual backup mode', 'it-l10n-backupbuddy' ),
	'options'	=>		array(
								'1'		=>		__( 'Classic (v1.x)', 'it-l10n-backupbuddy' ),
								'2'		=>		__( 'Modern (v2.x)', 'it-l10n-backupbuddy' ),
							),
	'tip'		=>		__('[Default: Modern] - If you are encountering difficulty backing up due to WordPress cron, HTTP Loopbacks, or other features specific to version 2.x you can try classic mode which runs like BackupBuddy v1.x did.', 'it-l10n-backupbuddy' ),
	'rules'		=>		'required',
) );


$settings_form->process(); // Handles processing the submitted form (if applicable).
$settings_form->set_value( 'importbuddy_pass_hash', $importbuddy_pass_dummy_text );
$settings_form->set_value( 'repairbuddy_pass_hash', $repairbuddy_pass_dummy_text );
$data['settings_form'] = &$settings_form; // For use in view.

/* END CONFIGURING PLUGIN SETTINGS FORM */



pb_backupbuddy::flush();
pb_backupbuddy::$classes['core']->periodic_cleanup( 43200, false ); // Cleans up and also makes sure directory security is always configured right on downloads after settings changes.
pb_backupbuddy::flush();



//$settings_form->clear_values();


// Load settings view.
pb_backupbuddy::load_view( 'settings', $data );
?>






<style type="text/css">
	/* Core Styles - USED BY DIRECTORY EXCLUDER */
	.jqueryFileTree LI.directory { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/directory.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.expanded { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/folder_open.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.file { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/file.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.wait { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/spinner.gif') 6px 6px no-repeat; }
	/* File Extensions*/
	.jqueryFileTree LI.ext_3gp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_afp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_afpa { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_asp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_aspx { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_avi { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_bat { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/application.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_bmp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_c { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_cfm { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_cgi { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_com { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/application.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_cpp { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_css { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/css.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_doc { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/doc.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_exe { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/application.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_gif { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_fla { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/flash.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_h { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_htm { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/html.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_html { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/html.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_jar { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/java.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_jpg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_jpeg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_js { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/script.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_lasso { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_log { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/txt.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_m4p { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/music.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_mov { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_mp3 { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/music.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_mp4 { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_mpg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_mpeg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_ogg { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/music.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_pcx { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_pdf { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/pdf.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_php { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/php.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_png { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_ppt { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ppt.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_psd { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/psd.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_pl { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/script.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_py { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/script.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_rb { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ruby.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_rbx { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ruby.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_rhtml { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ruby.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_rpm { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/linux.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_ruby { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/ruby.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_sql { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/db.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_swf { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/flash.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_tif { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_tiff { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/picture.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_txt { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/txt.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_vb { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_wav { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/music.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_wmv { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/film.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_xls { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/xls.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_xml { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/code.png') 6px 6px no-repeat; }
	.jqueryFileTree LI.ext_zip { background: url('<?php echo pb_backupbuddy::plugin_url(); ?>/images/filetree/zip.png') 6px 6px no-repeat; }
</style>
