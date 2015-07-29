<script type="text/javascript">
	jQuery(document).ready(function() {
		
		jQuery( '.pb_backupbuddy_hoveraction_migrate' ).click( function(e) {
			tb_show( 'BackupBuddy', '<?php echo pb_backupbuddy::ajax_url( 'migration_picker' ); ?>&callback_data=' + jQuery(this).attr('rel') + '&migrate=1&TB_iframe=1&width=640&height=455', null );
			return false;
		});
		
		jQuery( '.pb_backupbuddy_hoveraction_hash' ).click( function(e) {
			tb_show( 'BackupBuddy', '<?php echo pb_backupbuddy::ajax_url( 'hash' ); ?>&callback_data=' + jQuery(this).attr('rel') + '&TB_iframe=1&width=640&height=455', null );
			return false;
		});
		
		jQuery( '.pb_backupbuddy_hoveraction_send' ).click( function(e) {
			<?php if ( pb_backupbuddy::$options['importbuddy_pass_hash'] == '' ) { ?>
				alert( 'You must set an ImportBuddy password via the BackupBuddy settings page before you can send this file.' );
				return false;
			<?php } ?>
			tb_show( 'BackupBuddy', '<?php echo pb_backupbuddy::ajax_url( 'destination_picker' ); ?>&callback_data=' + jQuery(this).attr('rel') + '&sending=1&TB_iframe=1&width=640&height=455', null );
			return false;
		});
		
		jQuery( '.pb_backupbuddy_get_importbuddy' ).click( function(e) {
			<?php
			if ( pb_backupbuddy::$options['importbuddy_pass_hash'] == '' ) {
				//echo 'alert(\'' . __( 'Please set an ImportBuddy password on the BackupBuddy Settings page to download this script. This is required to prevent unauthorized access to the script when in use.', 'it-l10n-backupbuddy' ) . '\');';
				?>
				
				var password = prompt( '<?php _e( 'To download, enter a password to lock the ImportBuddy script from unauthorized access. You will be prompted for this password when you go to importbuddy.php in your browser. Since you have not defined a default password yet this will be used as your default and can be changed later from the Settings page.', 'it-l10n-backupbuddy' ); ?>' );
				if ( ( password != null ) && ( password != '' ) ) {
					window.location.href = '<?php echo pb_backupbuddy::ajax_url( 'importbuddy' ); ?>&p=' + password;
				}
				if ( password == '' ) {
					alert( 'You have not set a default password on the Settings page so you must provide a password here to download ImportBuddy.' );
				}
				
				return false;
				<?php
			} else {
				?>
				var password = prompt( '<?php _e( 'To download, either enter a new password for just this download OR LEAVE BLANK to use your default ImportBuddy password (set on the Settings page) to lock the ImportBuddy script from unauthorized access.', 'it-l10n-backupbuddy' ); ?>' );
				if ( password != null ) {
					window.location.href = '<?php echo pb_backupbuddy::ajax_url( 'importbuddy' ); ?>&p=' + password;
				}
				return false;
				<?php
			}
			?>
			return false;
		});
		

		
		jQuery( '.pb_backupbuddy_hoveraction_note' ).click( function(e) {
			
			var existing_note = jQuery(this).parents( 'td' ).find('.pb_backupbuddy_notetext').text();
			if ( existing_note == '' ) {
				existing_note = 'My first backup';
			}
			
			var note_text = prompt( '<?php _e( 'Enter a short descriptive note to apply to this archive for your reference. (175 characters max)', 'it-l10n-backupbuddy' ); ?>', existing_note );
			if ( ( note_text == null ) || ( note_text == '' ) ) {
				// User cancelled.
			} else {
				jQuery( '.pb_backupbuddy_backuplist_loading' ).show();
				jQuery.post( '<?php echo pb_backupbuddy::ajax_url( 'set_backup_note' ); ?>', { backup_file: jQuery(this).attr('rel'), note: note_text }, 
					function(data) {
						data = jQuery.trim( data );
						jQuery( '.pb_backupbuddy_backuplist_loading' ).hide();
						if ( data != '1' ) {
							alert( '<?php _e('Error', 'it-l10n-backupbuddy' );?>: ' + data );
						}
						javascript:location.reload(true);
					}
				);
			}
			return false;
		});
		
	}); // end ready.
	
	
	
	
	function pb_backupbuddy_selectdestination( destination_id, destination_title, callback_data ) {
		if ( callback_data != '' ) {
			if ( callback_data == 'importbuddy.php' ) {
				window.location.href = '<?php echo pb_backupbuddy::page_url(); ?>&destination=' + destination_id + '&destination_title=' + destination_title + '&callback_data=' + callback_data;
				return false;
			}
			jQuery.post( '<?php echo pb_backupbuddy::ajax_url( 'remote_send' ); ?>', { destination_id: destination_id, destination_title: destination_title, file: callback_data, trigger: 'migration', send_importbuddy: '1' }, 
				function(data) {
					data = jQuery.trim( data );
					if ( data.charAt(0) != '1' ) {
						alert( '<?php _e('Error starting remote send of file to migrate', 'it-l10n-backupbuddy' ); ?>:' + "\n\n" + data );
					} else {
						window.location.href = '<?php echo pb_backupbuddy::page_url(); ?>&destination=' + destination_id + '&destination_title=' + destination_title + '&callback_data=' + callback_data;
					}
				}
			);
			
			/* Try to ping server to nudge cron along since sometimes it doesnt trigger as expected. */
			jQuery.post( '<?php echo admin_url('admin-ajax.php'); ?>',
				function(data) {
				}
			);

		} else {
			window.location.href = '<?php echo pb_backupbuddy::page_url(); ?>&custom=remoteclient&destination_id=' + destination_id;
		}
	}
	
	
	
</script>


<br>
<?php
pb_backupbuddy::$ui->start_tabs(
	'destinations',
	array(
		array(
			'title'		=>		'Standard Restore / Migrate',
			'slug'		=>		'standard_migration',
		),
		array(
			'title'		=>		'Automated Restore / Migrate',
			'slug'		=>		'automated_migration',
		),
	),
	'width: 100%;'
);



pb_backupbuddy::$ui->start_tab( 'standard_migration' );
	?><br>
	The ImportBuddy tool (importbuddy.php) is the standard method of restoring a full or database backup as well as
	migrating <?php pb_backupbuddy::video( 'uSBvBSfSjWM', __('Automated migration', 'it-l10n-backupbuddy' ), true ); ?> to a new server or URL.
	This is a step-by-step process with instructions along the way.
	Keep a copy of this script with your backups for restoring sites directly from backups.
	<br><br>
	
	<h3>Instructions</h3>
	<ol>
		<li>
			<a href="<?php echo pb_backupbuddy::ajax_url( 'importbuddy' ); ?>" class="button button-primary pb_backupbuddy_get_importbuddy">Download ImportBuddy</a> or
			<a href="" rel="importbuddy.php" class="button button-primary pb_backupbuddy_hoveraction_send">Send ImportBuddy to a Destination</a>
		</li>
		<li>Upload importbuddy.php & backup ZIP file to the destination server directory where you want your site restored to (ie into FTP directory /public_html/your.com/ or similar).</li>
		<li>Navigate to the uploaded importbuddy.php URL in your web browser (ie http://your.com/importbuddy.php).</li>
		<li>Follow the on-screen directions until the restore / migration is complete.</li>
	</ol>
	<?php
pb_backupbuddy::$ui->end_tab();



pb_backupbuddy::$ui->start_tab( 'automated_migration' );
	echo '<br>';
	if ( pb_backupbuddy::$options['importbuddy_pass_hash'] == '' ) { // NO HASH SET.
		echo '<b>Set an ImportBuddy password on the <a href="';
			if ( is_network_admin() ) {
				echo network_admin_url( 'admin.php' );
			} else {
				echo admin_url( 'admin.php' );
			}
			echo '?page=pb_backupbuddy_settings">Settings</a> page before you begin.
		</b>';
	}
	?>
	Automated migration <?php pb_backupbuddy::video( 'jvL1X9w-CUY', __('Manual migration', 'it-l10n-backupbuddy' ), true ); ?> allows you to quickly <b>migrate full backups to another location</b> such as another server or another directory on this server.
	Your backup archive and the ImportBuddy tool (importbuddy.php) will automatically be transferred to the destination and run.
	This feature cannot be used to restore a site back to the same location over this site.
	<?php
	if ( count( $backups ) > 0 ) { // $backups set in the controller as view data.
		_e( 'Hover over the backup below you would like to migrate and select "Migrate this backup" to begin the automated migration process. If you encounter difficulty during automated migration then manual migration is suggested & only takes a few more minutes.', 'it-l10n-backupbuddy' );
		echo ' ';
		_e( 'Only full backups are listed below.', 'it-l10n-backupbuddy' );
		echo '<br><br>';
	} else {
		echo '<br>';
		_e( 'You must create a backup prior to migrating this site.', 'it-l10n-backupbuddy' );
		echo '<br>';
	}

	$listing_mode = 'migrate';
	require_once( '_backup_listing.php' );
	echo '<br>';
pb_backupbuddy::$ui->end_tab();



echo '<br><br><br><br><br>';
if ( pb_backupbuddy::$options['repairbuddy_pass_hash'] == '' ) {
	echo '<a onclick="alert(\'' . __( 'Please set a RepairBuddy password on the BackupBuddy Settings page to download this script. This is required to prevent unauthorized access to the script when in use.', 'it-l10n-backupbuddy' ) . '\'); return false;" href="" style="text-decoration: none;" title="' . __( 'Download the troubleshooting & repair script, repairbuddy.php', 'it-l10n-backupbuddy' ) . '">';
} else {
	echo '<a href="' . admin_url( 'admin-ajax.php' ) . '?action=pb_backupbuddy_repairbuddy" style="text-decoration: none;" title="' . __('Download the troubleshooting & repair script, repairbuddy.php', 'it-l10n-backupbuddy' ) . '">';
}
echo __( 'Download RepairBuddy troubleshooting & repair tool.', 'it-l10n-backupbuddy' ) . '</a>';

echo '</div>'; // Todo: Quick temporary fix.  Dangling div. Not sure where extra div is from yet.
?>