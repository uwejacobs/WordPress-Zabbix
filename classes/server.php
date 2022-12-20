<?php
class zw_Server_Interna {

    function get_data() {
        $interna = $this->zw_get_Server_Data();
        $interna[] = $this->zw_get_Server_Location();

        return $interna;
    }

    private function zw_get_Server_Data() {
        /* If Shell is enablelled then execute the CPU Load, Memory Load, RAM Load and Uptime */
        if ($this->isShellEnabled()) {
            $cpu_load = trim(shell_exec("echo $((`ps aux|awk 'NR > 0 { s +=$3 }; END {print s}'| cut -d . -f 1` / `cat /proc/cpuinfo | grep cores | grep -o '[0-9]' | wc -l`))"));
            $memory_usage = function_exists('memory_get_usage') ? memory_get_usage() : 0;
            $memory_usage_pct = round((($memory_usage / (int)$this->check_memory_limit_cal()) * 100), 0);
            $total_ram_server = (is_numeric($this->check_total_ram()) ? (int) $this->check_total_ram() : 0);
            $free_ram_server = (is_numeric($this->check_free_ram()) ? (int) $this->check_free_ram() : 0);
            $used_ram_server = ($total_ram_server - $free_ram_server);
            $ram_usage_pct = round((($used_ram_server / $total_ram_server) * 100), 0);
        
            $uptime = trim(shell_exec("cut -d. -f1 /proc/uptime"));
            $out = array(
                'cpu_load' => $cpu_load,
                'memory_usage' => $memory_usage,
                'memory_usage_pct' => $memory_usage_pct,
                'total_ram' => $total_ram_server,
                'free_ram' => $free_ram_server,
                'used_ram' => $used_ram_server,
                'ram_usage_pct' => $ram_usage_pct,
                'uptime' => $uptime
            );
        /* Otherwise just run the memory load check */
        } else {
            $memory_usage = function_exists('memory_get_usage') ? memory_get_usage() : 0;
            $memory_usage_pct = round($memory_usage / (int)$this->check_memory_limit_cal() * 100, 0);
            $out = array(
                'cpu_load' => null,
                'memory_usage' => $memory_usage,
                'memory_usage_pct' => $memory_usage_pct,
                'uptime' => null
            );
        }

        return $out;
    }

    private function check_total_ram()
    {
        $total_ram = get_transient('zw_total_ram');

        if ($total_ram === false) {
            if ($this->isShellEnabled()) {
                $total_ram = shell_exec("grep -w 'MemTotal' /proc/meminfo | grep -o -E '[0-9]+'");
                set_transient('zw_total_ram', $total_ram, WEEK_IN_SECONDS);
            } else {
                $total_ram = -1;
            }
        }

        return trim($total_ram);
    }

    private function check_free_ram()
    {
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

    public function check_ram_cache() {
        if ($this->isShellEnabled()) {
            $ram_cache = shell_exec("grep -w 'Cached' /proc/meminfo | grep -o -E '[0-9]+'");
        } else {
            $ram_cache= -1;
        }

        return trim($ram_cache);
    }

    public function check_ram_buffer() {
        if ($this->isShellEnabled()) {
            $ram_buffer = shell_exec("grep -w 'Buffers' /proc/meminfo | grep -o -E '[0-9]+'");
        } else {
            $ram_buffer= -1;
        }

        return trim($ram_buffer);
    }


    private function check_memory_limit_cal()
    {
        return (int)ini_get('memory_limit');
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


                        private function zw_get_Server_Location()
                        {
                                $ip = $this->check_server_ip();

                                $server_location = get_transient('zw_server_location');

                                if ($server_location === false) {
                                        // lets validate the ip
                                        if ($this->validate_ip_address($ip)) {
                                                $query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
                                                if ($query && $query['status'] == 'success') {
                                                        $server_location = $query['city'] . ', ' . $query['country'];
                                                        set_transient('zw_server_location', $server_location, WEEK_IN_SECONDS);
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

                                return array('server_location' => $server_location);
                        }

                        private function validate_ip_address($ip)
                        {
                                if (!filter_var($ip, FILTER_VALIDATE_IP) === false) {
                                        return true; // $ip is a valid IP address
                                } else {
                                        return false; // $ip is NOT a valid IP address
                                }
                        }

                        private function check_server_ip()
                        {
                                return trim(gethostbyname(gethostname()));
                        }








}
