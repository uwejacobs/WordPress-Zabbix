<?php
/**
 * Plugin Name:       Zabbix Worker
 * Description:       Provide statistics for zabbix
 * Version:           1.0.2
 * Author:            Uwe Jacobs
 * Requires at least: 6.0
 * Tested up to:      6.1.1
 * Requires PHP:      7.0
 * Text Domain:       zabbix_worker
 * GitHub Plugin URI: https://github.com/uwejacobs/Zabbix-Worker
 * Primary Branch:    main
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2, as published by the
 * Free Software Foundation.  You may NOT assume that you can use any other
 * version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 *
 * @package    ZabbixWorker
 * @since      1.0.0
 * @copyright  Copyright (c) 2022, Uwe Jacobs
 * @license    GPL-2.0+
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once(dirname(__FILE__).'/classes/wordpress.php');


add_action( 'wp_loaded', 'zw_internal_rewrites' );
function zw_internal_rewrites(){
    add_rewrite_rule( 'zabbix-api$', 'index.php?zabbix-api=1', 'top' );
}

add_filter( 'query_vars', 'zw_internal_query_vars' );
function zw_internal_query_vars( $query_vars ){
    $query_vars[] = 'zabbix-api';
    return $query_vars;
}

add_action( 'parse_request', 'zw_internal_rewrites_parse_request' );
function zw_internal_rewrites_parse_request( &$wp ) {
    $error = false;

    if (!array_key_exists( 'zabbix-api', $wp->query_vars ) ) {
        $error = true;
        return;
    }

    // generate security key
    $key = get_option("zabbix_worker_key");
    if (empty($key)) {
        $key = zw_generate_guidv4();
        add_option("zabbix_worker_key", $key);
    }

    // security check validation
    if (empty($_GET["token"]) || $_GET["token"] !== $key) {
        $error = true;
        return;
    }

    $wp_stats = new zw_WordPress_Interna();

    if (!$error) {
        $result = array('status' => 0, 'message' => 'Ok', 'wordpress' => $wp_stats->get_data());
        $output = json_encode(array('result' => $result));
    }
    else {
        $result = array('status' => 1, 'message' => 'Error!');
        $output = json_encode(array('result' => $result));
    }

    echo $output;
    die();
}

function zw_generate_guidv4() {
    // Generate 16 bytes (128 bits) of random data
    $data = random_bytes(16);
    assert(strlen($data) == 16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
