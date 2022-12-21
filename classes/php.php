<?php
class zw_PHP_Interna {

    function get_data() {
        $interna = [];
        $interna["version"] = $this->zw_get_php_version();
        $interna["max_upload_size"] = $this->php_max_upload_size();
        $interna["max_post_size"] = $this->php_max_post_size();
        $interna["max_execution_time"] = $this->php_max_execution_time();
        $interna["short_tag"] = $this->php_short_tag();
        $interna["memory_limit"] = $this->php_check_limit();
        $interna["memory_usage"] = function_exists('memory_get_usage') ? memory_get_usage() : 0;
        $interna["memory_usage_pct"] = (((int)$interna["memory_usage"] / (int)$interna["memory_limit"]) * 100);

        return $interna;
    }
    
    private function zw_get_php_version() {
        return PHP_VERSION;
    }

/*
The following functions are taken from the plugin WP Server Stats and released under the same license.

Original Plugin Name: WP Server Stats
Original Plugin URI: https://wordpress.org/plugins/wp-server-stats/
Original Description: Show up the memory limit and current memory usage in the dashboard and admin footer
Original Author: Saumya Majumder, Acnam Infotech
Original Author URI: https://acnam.com/
Original Version: 1.7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
    private function php_max_upload_size() {
        $php_max_upload_size = get_transient('zw_php_max_upload_size');

        if ($php_max_upload_size === false) {
            if (ini_get('upload_max_filesize')) {
                $php_max_upload_size = ini_get('upload_max_filesize');
                $php_max_upload_size = $this->format_php_size($php_max_upload_size);
                set_transient('zw_php_max_upload_size', $php_max_upload_size, DAY_IN_SECONDS);
            } else {
                $php_max_upload_size = -1;
            }
        }

        return $php_max_upload_size;
    }

    private function php_max_post_size() {

        $php_max_post_size = get_transient('zw_php_max_post_size');

        if ($php_max_post_size === false) {
            if (ini_get('post_max_size')) {
                $php_max_post_size = ini_get('post_max_size');
                $php_max_post_size = $this->format_php_size($php_max_post_size);
                set_transient('zw_php_max_post_size', $php_max_post_size, DAY_IN_SECONDS);
            } else {
                $php_max_post_size = -1;
            }
        }

        return $php_max_post_size;
    }

    private function php_max_execution_time() {
        if (ini_get('max_execution_time')) {
            $max_execute = ini_get('max_execution_time');
        } else {
            $max_execute = -1;
        }

        return $max_execute;
    }

    private function php_short_tag() {
        $short_tag = ini_get('short_open_tag') ? 'true' : 'false';

        return $short_tag;
    }

    private function format_php_size($size) {
        if (!is_numeric($size)) {
            if (strpos($size, 'M') !== false) {
                $size = intval($size) * 1024 * 1024;
            } elseif (strpos($size, 'K') !== false) {
                $size = intval($size) * 1024;
            } elseif (strpos($size, 'G') !== false) {
                $size = intval($size) * 1024 * 1024 * 1024;
            }
        }

        return $size;
    }

    private function php_check_limit() {
        $memory_limit = ini_get('memory_limit');
        if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
            $memory_limit = $matches[1];
            switch($matches[2]) {
                case 'P':
                    $memory_limit *= 1024;
                case 'T':
                    $memory_limit *= 1024;
                case 'G':
                    $memory_limit *= 1024;
                case 'M':
                    $memory_limit *= 1024;
                case 'K':
                    $memory_limit *= 1024;
            }
        }

        return $memory_limit;
    }

}

