<?php
/**
 * Plugin Name:       Zabbix Worker
 * Description:       Provide statistics for zabbix
 * Version:           1.0.0
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

    if (!array_key_exists( 'zabbix-api', $wp->query_vars ) ) {
        return;
    }

    $error = false;

    // security check validation
    $str = print_r($_GET,1);

    $stats = array (
        'no_pages' => 0,
        'no_posts' => 0,
        'users' => 0
    );
    
    $xxx = new zw_WordPress_Interna();

    if (!$error) {
        $result = array('status' => 0, 'message' => $str, 'data' => $stats, 'wordpress' => $xxx->get_data());
        $output = json_encode(array('result' => $result));
    }
    else {
        $result = array('status' => 1, 'message' => 'Error!');
        $output = json_encode(array('result' => $result));
    }

    echo $output;
    die();
}
