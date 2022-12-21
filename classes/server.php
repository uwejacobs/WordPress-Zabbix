<?php
class zw_Server_Interna {

    function get_data() {
        $interna = $this->zw_get_Server_Data();
        $interna["server_location"] = $this->zw_get_Server_Location();
        $interna["cpu_count"] = $this->check_cpu_count();
        $interna["cpu_core_count"] = $this->check_cpu_core_count();
        $interna["os"] = $this->server_os();
        $interna["bits"] = PHP_INT_SIZE * 8;
        $interna["software"] = $_SERVER['SERVER_SOFTWARE'];
        $interna["ip_address"] = ($this->validate_ip_address($this->check_server_ip()) ? $this->check_server_ip() : "ERROR IP096T");
        $interna["port"] = $_SERVER['SERVER_PORT'];
        $interna["hostname"] = gethostname();
        $interna["site_document_root"] = $_SERVER['DOCUMENT_ROOT'];
        $interna["memcached"] = (class_exists('Memcache') ? 'true' : 'false');
        
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

    private function zw_get_Server_Data() {
        /* If Shell is enablelled then execute the CPU Load, Memory Load, RAM Load and Uptime */
        if ($this->isShellEnabled()) {
            $cpu_load = trim(shell_exec("echo $((`ps aux|awk 'NR > 0 { s +=$3 }; END {print s}'| cut -d . -f 1` / `cat /proc/cpuinfo | grep cores | grep -o '[0-9]' | wc -l`))"));
            $total_ram_server = (is_numeric($this->check_total_ram()) ? (int) $this->check_total_ram() : 0);
            $free_ram_server = (is_numeric($this->check_free_ram()) ? (int) $this->check_free_ram() : 0);
            $used_ram_server = ($total_ram_server - $free_ram_server);
            $ram_usage_pct = round(($used_ram_server / $total_ram_server) * 100, 2);
        
            $uptime = trim(shell_exec("cut -d. -f1 /proc/uptime"));

            $out = array(
                'cpu_load' => $cpu_load,
                'total_ram' => $total_ram_server,
                'free_ram' => $free_ram_server,
                'used_ram' => $used_ram_server,
                'ram_usage_pct' => $ram_usage_pct,
                'uptime' => $uptime
            );
        /* Otherwise just run the memory load check */
        } else {
            $out = array(
                'cpu_load' => 0,
                'total_ram' => 0,
                'free_ram' => 0,
                'used_ram' => 0,
                'ram_usage_pct' => 0,
                'uptime' => 0
            );
        }

        return $out;
    }

    private function check_total_ram() {
        $total_ram = get_transient('zw_total_ram');

        if ($total_ram === false) {
            if ($this->isShellEnabled()) {
                $total_ram = shell_exec("grep -w 'MemTotal' /proc/meminfo | grep -o -E '[0-9]+'");
                set_transient('zw_total_ram', $total_ram, DAY_IN_SECONDS);
            } else {
                $total_ram = -1;
            }
        }

        return trim($total_ram);
    }

    private function check_free_ram() {
        if ($this->isShellEnabled()) {
            $free_ram = shell_exec("grep -w 'MemFree' /proc/meminfo | grep -o -E '[0-9]+'");

            if( !is_null( $this->check_ram_cache() ) || !is_null( $this->check_ram_buffer() ) ) {
                $ram_cache = is_null( $this->check_ram_cache() ) ? 0 : (int) $this->check_ram_cache();
                $ram_buffer = is_null( $this->check_ram_buffer() ) ? 0 : (int) $this->check_ram_buffer();
                $free_ram_final = (int) $free_ram + $ram_cache + $ram_buffer;
            } else {
                $free_ram_final = $free_ram;
            }
        } else {
            $free_ram_final = -1;
        }

        return trim($free_ram_final);
    }

    private function check_ram_cache() {
        if ($this->isShellEnabled()) {
            $ram_cache = shell_exec("grep -w 'Cached' /proc/meminfo | grep -o -E '[0-9]+'");
        } else {
            $ram_cache= -1;
        }

        return trim($ram_cache);
    }

    private function check_ram_buffer() {
        if ($this->isShellEnabled()) {
            $ram_buffer = shell_exec("grep -w 'Buffers' /proc/meminfo | grep -o -E '[0-9]+'");
        } else {
            $ram_buffer= -1;
        }

        return trim($ram_buffer);
    }

    private function isShellEnabled()
    {
        /*Check if shell_exec() is enabled on this server*/
        if (function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(', ', ini_get('disable_functions'))))) {
            /*Check if shell_exec() actually works*/
            $returnVal = shell_exec('cat /proc/cpuinfo');
            if (!empty($returnVal)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    private function zw_get_Server_Location() {
        $ip = $this->check_server_ip();

        $server_location = get_transient('zw_server_location');

        if ($server_location === false) {
            // lets validate the ip
            if ($this->validate_ip_address($ip)) {
                $query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
                if ($query && $query['status'] == 'success') {
                    $server_location = $query['city'] . ', ' . $query['country'];
                    set_transient('zw_server_location', $server_location, DAY_IN_SECONDS);
                } else {
                    if (empty($query['message'])) {
                        $server_location = $query['status'];
                    } else {
                        $server_location = $query['message'];
                    }
                }
            } else {
                $server_location = "Unkown";
            }
        }

        return $server_location;
    }

    private function validate_ip_address($ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP) === false) {
                return true; // $ip is a valid IP address
        } else {
                return false; // $ip is NOT a valid IP address
        }
    }

    private function check_server_ip() {
        return trim(gethostbyname(gethostname()));
    }

    private function check_cpu_count() {
        $cpu_count = get_transient('zw_cpu_count');

        if ($cpu_count === false) {
            if ($this->isShellEnabled()) {
                $cpu_count = shell_exec('cat /proc/cpuinfo |grep "physical id" | sort | uniq | wc -l');
                set_transient('zw_cpu_count', $cpu_count, DAY_IN_SECONDS);
            } else {
                $cpu_count = -1;
            }
        }

        return (int)$cpu_count;
    }

    private function check_cpu_core_count() {
        $cpu_core_count = get_transient('zw_cpu_core_count');

        if ($cpu_core_count === false) {
            if ($this->isShellEnabled()) {
                $cpu_core_count = shell_exec("echo \"$((`cat /proc/cpuinfo | grep cores | grep -o -E '[0-9]+' | uniq` * `cat /proc/cpuinfo |grep 'physical id' | sort | uniq | wc -l`))\"");
                set_transient('zw_cpu_core_count', $cpu_core_count, DAY_IN_SECONDS);
            } else {
                $cpu_core_count = -1;
            }
        }

        return (int)$cpu_core_count;
    }

    private function server_os() {
        $server_os = get_transient('wpss_server_os');

        if ($server_os === false) {
            $os_detail = php_uname();
            $just_os_name = explode(" ", trim($os_detail));
            $server_os = $just_os_name[0];
            set_transient('wpss_server_os', $server_os, WEEK_IN_SECONDS);
        }

        return $server_os;
    }







}
