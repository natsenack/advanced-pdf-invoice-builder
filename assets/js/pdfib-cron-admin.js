/* global jQuery, ajaxurl, pdfibCronData */
jQuery( document ).ready( function( $ ) {
	'use strict';

	const d    = globalThis.pdfibCronData || {};
	const i18n = d.i18n || {};

	// Diagnose cron system
	$( '#diagnose-cron-btn' ).on( 'click', function() {
		$( this ).prop( 'disabled', true ).text( i18n.diagnosing );
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pdfib_diagnose_cron',
				nonce: d.nonce
			},
			success: function( response ) {
				if ( response.success ) {
					var $diag = $( '<pre>' );
					$diag.text( JSON.stringify( response.data, null, 2 ) );
					$( '#cron-status-display' ).empty().append( $diag );
					$( '#cron-results' ).show();
					$( '#cron-results-content' ).html( '<pre>Diagnostics completed successfully</pre>' );
				} else {
					alert( i18n.errorDiagnosingCronSystem + ' ' + response.data );
				}
				$( '#diagnose-cron-btn' ).prop( 'disabled', false ).text( i18n.diagnoseCronSystem );
			},
			error: function() {
				$( '#diagnose-cron-btn' ).prop( 'disabled', false ).text( i18n.diagnoseCronSystem );
				alert( i18n.ajaxErrorOccurred );
			}
		} );
	} );

	// Repair cron system
	$( '#repair-cron-btn' ).on( 'click', function() {
		if ( ! confirm( i18n.areYouSureRepairCron ) ) {
			return;
		}
		$( this ).prop( 'disabled', true ).text( i18n.repairing );
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pdfib_repair_cron',
				nonce: d.nonce
			},
			success: function( response ) {
				$( '#repair-cron-btn' ).prop( 'disabled', false ).text( i18n.repairCronSystem );
				if ( response.success ) {
					alert( i18n.cronSystemRepairedSuccessfully );
					$( '#diagnose-cron-btn' ).click();
				} else {
					alert( i18n.errorRepairingCronSystem + ' ' + response.data );
				}
			},
			error: function() {
				$( '#repair-cron-btn' ).prop( 'disabled', false ).text( i18n.repairCronSystem );
				alert( i18n.ajaxErrorOccurred );
			}
		} );
	} );

	// View backup statistics
	$( '#backup-stats-btn' ).on( 'click', function() {
		$( this ).prop( 'disabled', true ).text( i18n.loading );
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pdfib_get_backup_stats',
				nonce: d.nonce
			},
			success: function( response ) {
				$( '#backup-stats-btn' ).prop( 'disabled', false ).text( i18n.viewBackupStatistics );
				if ( response.success ) {
					$( '#cron-results' ).show();
						var $pre = $( '<pre>' );
						$pre.text( response.data );
						$( '#cron-results-content' ).empty().append( $pre );
				} else {
					alert( i18n.errorLoadingBackupStatistics + ' ' + response.data );
				}
			},
			error: function() {
				$( '#backup-stats-btn' ).prop( 'disabled', false ).text( i18n.viewBackupStatistics );
				alert( i18n.ajaxErrorOccurred );
			}
		} );
	} );

	// Create manual backup
	$( '#manual-backup-btn' ).on( 'click', function() {
		if ( ! confirm( i18n.areYouSureCreateBackup ) ) {
			return;
		}
		$( this ).prop( 'disabled', true ).text( i18n.creatingBackup );
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pdfib_create_backup',
				nonce: d.nonce
			},
			success: function( response ) {
				$( '#manual-backup-btn' ).prop( 'disabled', false ).text( i18n.createManualBackup );
				if ( response.success ) {
					alert( i18n.manualBackupCreatedSuccessfully );
					$( '#backup-stats-btn' ).click();
				} else {
					alert( i18n.errorCreatingManualBackup + ' ' + response.data );
				}
			},
			error: function() {
				$( '#manual-backup-btn' ).prop( 'disabled', false ).text( i18n.createManualBackup );
				alert( i18n.ajaxErrorOccurred );
			}
		} );
	} );

	// WP Cron Status Check
	function updateWpCronStatusIndicator( elementId, status, text ) {
		const indicator   = $( '#' + elementId + '-indicator' );
		const textElement = $( '#' + elementId + '-text' );

		indicator.removeClass( 'pdfb-status-good status-warning status-error' );

		if ( status === 'good' ) {
			indicator.addClass( 'pdfb-status-good' ).css( 'background', '#28a745' );
		} else if ( status === 'warning' ) {
			indicator.addClass( 'pdfb-status-warning' ).css( 'background', '#ffc107' );
		} else if ( status === 'error' ) {
			indicator.addClass( 'pdfb-status-error' ).css( 'background', '#dc3545' );
		}

		textElement.text( text );
	}

	function checkCronConfig() {
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pdfib_check_wp_cron_config',
				nonce: d.nonce
			},
			success: function( response ) {
				if ( response.success ) {
					if ( response.data.cron_disabled ) {
						updateWpCronStatusIndicator( 'wp-cron-enabled', 'error', i18n.wpCronDisabled );
					} else {
						updateWpCronStatusIndicator( 'wp-cron-enabled', 'good', i18n.wpCronEnabled );
					}
				} else {
					updateWpCronStatusIndicator( 'wp-cron-enabled', 'error', i18n.cannotCheckWpCronConfiguration );
				}
			},
			error: function() {
				updateWpCronStatusIndicator( 'wp-cron-enabled', 'error', i18n.errorCheckingWpCronConfiguration );
			}
		} );
	}

	function checkCronScheduledTasks() {
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pdfib_check_scheduled_tasks',
				nonce: d.nonce
			},
			success: function( response ) {
				if ( response.success ) {
					const taskCount = response.data.scheduled_tasks?.length ?? 0;
					if ( taskCount > 0 ) {
						updateWpCronStatusIndicator( 'wp-cron-scheduled', 'good', i18n.scheduledTasksActive + taskCount + i18n.tasks );
					} else {
						updateWpCronStatusIndicator( 'wp-cron-scheduled', 'warning', i18n.noScheduledTasksFound );
					}
				} else {
					updateWpCronStatusIndicator( 'wp-cron-scheduled', 'error', i18n.cannotCheckScheduledTasks );
				}
			},
			error: function() {
				updateWpCronStatusIndicator( 'wp-cron-scheduled', 'error', i18n.errorCheckingScheduledTasks );
			}
		} );
	}

	function testCronResponse() {
		$.ajax( {
			url: d.cronTestUrl,
			type: 'GET',
			timeout: 10000,
			success: function( response ) {
				if ( response?.success ) {
					updateWpCronStatusIndicator( 'wp-cron-response', 'good', i18n.cronSystemRespondingCorrectly );
				} else {
					updateWpCronStatusIndicator( 'wp-cron-response', 'warning', i18n.cronSystemRespondingWithIssues );
				}
			},
			error: function( xhr, status ) {
				if ( status === 'timeout' ) {
					updateWpCronStatusIndicator( 'wp-cron-response', 'warning', i18n.cronResponseSlow );
				} else {
					updateWpCronStatusIndicator( 'wp-cron-response', 'error', i18n.cronSystemNotResponding );
				}
			}
		} );
	}

	function checkWpCronStatus() {
		updateWpCronStatusIndicator( 'wp-cron-enabled', 'warning', i18n.checkingWpCronConfiguration );
		updateWpCronStatusIndicator( 'wp-cron-scheduled', 'warning', i18n.checkingScheduledTasks );
		updateWpCronStatusIndicator( 'wp-cron-response', 'warning', i18n.testingCronResponse );
		checkCronConfig();
		checkCronScheduledTasks();
		testCronResponse();
	}

	// Bind refresh button
	$( '#check-wp-cron-status-btn' ).on( 'click', function() {
		$( this ).prop( 'disabled', true ).html(
			'<span class="dashicons dashicons-update spin" style="vertical-align: middle; margin-right: 5px;"></span>' + i18n.checking
		);
		checkWpCronStatus();
		setTimeout( function() {
			$( '#check-wp-cron-status-btn' ).prop( 'disabled', false ).html(
				'<span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>' + i18n.refreshStatus
			);
		}, 2000 );
	} );

	// Initialize WP Cron status check on page load
	checkWpCronStatus();
} );
