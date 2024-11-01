<?php

/**
 * Plugin Name: Tavakal - Disk monitoring
 * Description: Free light plugin to monitor the free disk space, and notification system when running out of space.
 * Author: Tavakal4devs
 * Version: 1.0.0
 * Donate link: https://paypal.me/MohAsly
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

require dirname(__FILE__) . '/includes/DiskMonitoring.php';

// init
new DiskMonitoring();

register_deactivation_hook(__FILE__, 'tavakal_disk_monitor_deactivate');

function tavakal_disk_monitor_deactivate()
{
    delete_option('tavakal_disk_monitoring_send_notification_to');
    delete_option('tavakal_alert_at_size');
    wp_clear_scheduled_hook('check_disk_space');
}