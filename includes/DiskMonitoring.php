<?php


class DiskMonitoring
{

    /**
     * @var int default value in GB to send notifications
     */
    public  $alert_size = 5;
    /**
     * @var $send_warning_to
     */
    public  $send_warning_to = '';

    public function __construct()
    {
        // set the default value
        $this->send_warning_to = get_option('admin_email',null);

        add_action('admin_init', [$this, 'add_option_section']);

        add_filter('cron_schedules', [$this, 'cron_add_one_hour']);

        add_action('wp_dashboard_setup', [$this, 'disk_monitoring_widget']);

        add_action('check_disk_space', [$this, 'check_disk_space']);
        // adding schedule
        if (!wp_next_scheduled('check_disk_space')) {
            wp_schedule_event(time(), 'one_hour', 'check_disk_space');
        }

        add_action('update_option_tavakal_disk_monitoring_send_notification_to', [$this, 'save_send_notification_to'], 10, 2);
        add_action('update_option_tavakal_alert_at_size', [$this, 'save_tavakal_alert_at_size'], 10, 2);
    }

    /**
     * @param $schedules
     * @return mixed
     */
    public function cron_add_one_hour($schedules)
    {
        $schedules['one_hour'] = [
            'interval' => 60 * 60,
            'display' => 'Each 60 min'
        ];
        return $schedules;
    }


    public function check_disk_space()
    {
        $disk_info = $this->get_disk_space();

        /**
         * @var string $send_warning_to_string
         */
        $send_warning_to_string = get_option('tavakal_disk_monitoring_send_notification_to', $this->send_warning_to);
        $send_warning_to_array = array_map('trim', explode(',',$send_warning_to_string));

        $size_alert_at = get_option('tavakal_alert_at_size', $this->alert_size);

        // check if free space is running out and send notification
        if ($disk_info['size'] < $size_alert_at && ($disk_info['type'] === 'GB' || $disk_info['type'] === 'MB' || $disk_info['type'] === 'KB') && count($send_warning_to_array)) {
            $message = "Free space on disk is less than ".esc_html($size_alert_at)." GB.\n";
            $message .= "Available: " . esc_html($disk_info['size']) . esc_html($disk_info['type']);
            wp_mail($send_warning_to_array, 'Tavakal disk monitoring warning', $message);
        }
    }

    function disk_monitoring_widget()
    {
        wp_add_dashboard_widget('monitoring_disk_widget', 'Tavakal disk monitoring ', function () {
            $disk_info = $this->get_disk_space();
            echo "FREE SPACE: " . esc_html($disk_info['size']) . esc_html($disk_info['type']) . "<br>";
        });
    }

    public function get_disk_space($directory = '/')
    {

        $disk_size = @disk_free_space($directory);

        if (!$disk_size) {
            return false;
        }

        $base = log($disk_size) / log(1000);
        $suffix = array("", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
        $fBase = floor($base);
        return ['size' => round(pow(1000, $base - floor($base)), 1), 'type' => $suffix[$fBase]];
    }

    public function save_tavakal_alert_at_size($old_value, $value)
    {
        if ($old_value === $value) {
            return;
        }
        update_option('tavakal_alert_at_size', $value);
    }


    public function save_send_notification_to($old_value, $value)
    {
        if ($old_value === $value) {
            return;
        }
        update_option('tavakal_disk_monitoring_send_notification_to', $value);
    }

    public function add_option_section()
    {

        register_setting('general', 'tavakal_disk_monitoring_send_notification_to');
        register_setting('general', 'tavakal_alert_at_size');

        add_settings_section(
            'disk_monitoring',
            'Tavakal disk monitoring',
            '',
            'general'
        );


        add_settings_field(
            'tavakal_disk_monitoring_send_notification_to',
            '<label for="tavakal_disk_monitoring_send_notification_to">' . esc_html('Send notification to the next emails') . '</label>',
            function () {
                $option = get_option('tavakal_disk_monitoring_send_notification_to', $this->send_warning_to);
                 echo '<input type="text" name="tavakal_disk_monitoring_send_notification_to" placeholder="'.esc_html('separate by comma').'" value="'.esc_html($option).'"  >';
            },
            'general',
            'disk_monitoring'
        );

        add_settings_field(
            'tavakal_alert_at_size',
            '<label for="tavakal_disk_monitoring_send_notification_to">' . esc_html('Send notification if space is < (gb)') . '</label>',
            function () {
                $option = get_option('tavakal_alert_at_size', $this->alert_size);
                echo '<input type="number" name="tavakal_alert_at_size" placeholder="'.esc_html('gb').'" value="'.esc_html($option).'"  >';
            },
            'general',
            'disk_monitoring'
        );
    }

}


