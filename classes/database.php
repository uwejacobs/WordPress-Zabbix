<?php
class zw_Database_Interna {

    function get_data() {
        $interna = [];
        $interna["software"] = $this->database_software();
        $interna["version"] = $this->database_version();
        $interna["max_connections"] = $this->database_max_no_connection();
        $interna["max_packet_size"] = $this->database_max_packet_size();
        $interna["disk_usage"] = $this->database_disk_usage();
        $interna["index_disk_usage"] = $this->index_disk_usage();

        return $interna;
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

    private function database_software() {
        $db_software = get_transient('zw_db_software');

        if ($db_software === false) {
            global $wpdb;
            $db_software_query = $wpdb->get_row("SHOW VARIABLES LIKE 'version_comment'");
            $db_software_dump = $db_software_query->Value;
            if (!empty($db_software_dump)) {
                $db_soft_array = explode(" ", trim($db_software_dump));
                $db_software = $db_soft_array[0];
                set_transient('zw_db_software', $db_software, DAY_IN_SECONDS);
            } else {
                $db_software = 'Unkown';
            }
        }

        return $db_software;
    }

    private function database_version() {
        $db_version = get_transient('zw_db_version');

        if ($db_version === false) {
            global $wpdb;
            $db_version_dump = $wpdb->get_var("SELECT VERSION() AS version from DUAL");
            if (preg_match('/\d+(?:\.\d+)+/', $db_version_dump, $matches)) {
                $db_version = $matches[0]; //returning the first match
                set_transient('zw_db_version', $db_version, DAY_IN_SECONDS);
            } else {
                $db_version = 'Unkown';
            }
        }

        return $db_version;
    }

    private function database_max_no_connection() {
        $db_max_connection = get_transient('zw_db_max_connection');

        if ($db_max_connection === false) {
            global $wpdb;
            $connection_max_query = $wpdb->get_row("SHOW VARIABLES LIKE 'max_connections'");
            $db_max_connection = $connection_max_query->Value;
            if (empty($db_max_connection)) {
                $db_max_connection = -1;
            } else {
                $db_max_connection = number_format_i18n($db_max_connection, 0);
                set_transient('zw_db_max_connection', $db_max_connection, DAY_IN_SECONDS);
            }
        }

        return $db_max_connection;
    }

    private function database_max_packet_size() {
        $db_max_packet_size = get_transient('zw_db_max_packet_size');

        if ($db_max_packet_size === false) {
            global $wpdb;
            $packet_max_query = $wpdb->get_row("SHOW VARIABLES LIKE 'max_allowed_packet'");
            $db_max_packet_size = $packet_max_query->Value;
            if (empty($db_max_packet_size)) {
                $db_max_packet_size = -1;
            } else {
                set_transient('zw_db_max_packet_size', $db_max_packet_size, DAY_IN_SECONDS);
            }
        }

        return $db_max_packet_size;
    }

    private function database_disk_usage() {
        $db_disk_usage = get_transient('zw_db_disk_usage');

        if ($db_disk_usage === false) {
            global $wpdb;
            $db_disk_usage = 0;
            $tablesstatus = $wpdb->get_results("SHOW TABLE STATUS");

            foreach ($tablesstatus as $tablestatus) {
                $db_disk_usage += $tablestatus->Data_length;
            }

            if (empty($db_disk_usage)) {
                $db_disk_usage = -1;
            } else {
                set_transient('zw_db_disk_usage', $db_disk_usage, DAY_IN_SECONDS);
            }
        }

        return $db_disk_usage;
    }

    private function index_disk_usage()
    {

        $db_index_disk_usage = get_transient('zw_db_index_disk_usage');

        if ($db_index_disk_usage === false) {
            global $wpdb;
            $db_index_disk_usage = 0;
            $tablesstatus = $wpdb->get_results("SHOW TABLE STATUS");

            foreach ($tablesstatus as $tablestatus) {
                $db_index_disk_usage += $tablestatus->Index_length;
            }

            if (empty($db_index_disk_usage)) {
                $db_index_disk_usage = -1;
            } else {
                set_transient('zw_db_index_disk_usage', $db_index_disk_usage, DAY_IN_SECONDS);
            }
        }

        return $db_index_disk_usage;
    }
}