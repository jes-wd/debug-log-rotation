<?php

/**
 * Plugin Name: Debug Log Rotation
 * Description: Avoid massive and unusable debug.log files by archiving and compressing them.
 * Version: 0.1
 * Author: JES Web Development
 * Author URI: https://jeswebdevelopment.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 **/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// define the absolute plugin path for includes
define('JES_DEBUG_LOG_ROTATION_PLUGIN_PATH', plugin_dir_path(__FILE__));

class JES_Debug_Rotation {
    private static $instance;
    const CLASS_NAME = 'JES_Debug_Rotation';
    const PLUGIN_NAME = 'Debug Log Rotation';

    public function __construct() {
        //run our hooks on plugins loaded to as we may need checks       
        add_action('plugins_loaded', array($this, 'plugin_init'));
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function plugin_init() {
        //add tasks to the cron job
        add_action(self::CLASS_NAME . '_archive_process', array($this, 'run_archive_process'));
        add_action(self::CLASS_NAME . '_compression_process', array($this, 'zip_oldest_files_but_leave_one'));
    }

    static function on_activation($network_wide) {
        if (is_multisite() && $network_wide) {
            $args = array('number' => 500, 'fields' => 'ids');
            $sites = get_sites($args);

            foreach ($sites as $blog_id) {
                switch_to_blog($blog_id);
                self::setup_crons();
                restore_current_blog();
            }
        } else {
            self::setup_crons();
        }
    }

    static function on_deactivation($network_wide) {
        if (is_multisite() && $network_wide) {
            $args = array('number' => 500, 'fields' => 'ids');
            $sites = get_sites($args);

            foreach ($sites as $blog_id) {
                switch_to_blog($blog_id);
                self::remove_crons();
                restore_current_blog();
            }
        } else {
            self::remove_crons();
        }
    }

    private static function get_archive_dir() {
        return WP_CONTENT_DIR . '/debug-archive';
    }

    // private static function get_max_files() {
    //     return 5;
    // }

    private static function setup_crons() {
        // setup the cron for the archiving of debug.log files
        wp_clear_scheduled_hook(self::CLASS_NAME . '_archive_process');
        wp_schedule_event(time(), 'per_minute', self::CLASS_NAME . '_archive_process');
        // set up the cron for the compression of archived debug.log files
        wp_clear_scheduled_hook(self::CLASS_NAME . '_compression_process');
        wp_schedule_event(time(), 'per_minute', self::CLASS_NAME . '_compression_process');
    }

    private static function remove_crons() {
        wp_clear_scheduled_hook(self::CLASS_NAME . '_archive_process');
        wp_clear_scheduled_hook(self::CLASS_NAME . '_compression_process');
    }

    private static function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(plugin_basename(__FILE__) . ' - ' . print_r($log, true));
            } else {
                error_log(plugin_basename(__FILE__) . ' - ' . $log);
            }
        }
    }

    private static function get_debug_file_path() {
        if (in_array(strtolower((string) WP_DEBUG_LOG), array('true', '1'), true)) {
            $log_path = WP_CONTENT_DIR . '/debug.log';
        } elseif (is_string(WP_DEBUG_LOG)) {
            $log_path = WP_DEBUG_LOG;
        } else {
            $log_path = false;
        }

        return $log_path;
    }

    public function run_archive_process() {
        if ($file_path = self::get_debug_file_path()) {
            $size = filesize($file_path);
            //by default clear the log if it is larger than 4MB.
            $size_threshold = apply_filters(self::CLASS_NAME . '_size_threshold', 200);

            if ($size > $size_threshold) {

                self::ensure_archive_dir_exists();

                rename($file_path, self::get_archive_dir() . '/' . date('Y-m-d-H-i-s') . '.debug.log');

                self::write_log(self::PLUGIN_NAME . ' file size is ' . $size . ' bytes which is bigger than the threshold of ' . $size_threshold . ' bytes  and therefore has been deleted');
            } else {
                self::write_log(self::PLUGIN_NAME . ' file size is smaller and is ' . $size);
            }
        }
    }

    private static function ensure_archive_dir_exists() {
        if (!file_exists(self::get_archive_dir())) {
            mkdir(self::get_archive_dir());
        }
    }

    public static function zip_oldest_files_but_leave_one() {
        $files = glob(self::get_archive_dir() . '/*.debug.log');
        $files = array_slice($files, 1);
        $files = array_reverse($files);

        $zip = new ZipArchive();
        $zip_name = self::get_archive_dir() . '/' . date('Y-m-d-H-i-s') . '.zip';

        if ($zip->open($zip_name, ZipArchive::CREATE) === true) {
            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
        }

        foreach ($files as $file) {
            unlink($file);
        }
    }
}

$jes_debug_rotation = JES_Debug_Rotation::get_instance();
register_activation_hook(__FILE__, array('JES_Debug_Rotation', 'on_activation'));
register_deactivation_hook(__FILE__, array('JES_Debug_Rotation', 'on_deactivation'));
// include settings page
include(JES_DEBUG_LOG_ROTATION_PLUGIN_PATH . 'includes/admin-settings-page.php');
