<?php
/**
 * Plugin Name:       Zabbix Worker
 * Description:       Provide statistics for zabbix
 * Version:           1.0.3
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

add_action("admin_menu", "zabbix_worker_options_submenu");
function zabbix_worker_options_submenu() {
  add_submenu_page(
        'options-general.php',
        'Zabbix Worker',
        'Zabbix Worker',
        'administrator',
        'zabbix-worker-options',
        'zabbix_worker_settings_page' );
}

function zabbix_worker_settings_page() {
	if (!is_admin() || !current_user_can('manage_options')) {
		return;
	}

    $key = get_option("zabbix_worker_key");
?><h1>Zabbix Worker</h1>
<form method="post">
    <label for="zw_key_option" style="padding-right:10px;font-size:125%">Zabbix Worker Key:</label>
    <input readonly id="zw_key_option" size="36" placeholder="Regenerate the Access key" type="text" value="<?php esc_html_E($key) ?>">
    <input type="hidden" name="zw_action" value="zw_regenerate_key" />
    <?php wp_nonce_field( 'zw_regenerate_key_nonce', 'zw_regenerate_key_nonce' ); ?>
    <?php submit_button( esc_html__( 'Regenerate Key', 'zabbix_worker' ), 'secondary', 'submit', false ); ?>
</form><?php
}

add_action( 'admin_init', 'zw_regenerate_key' );
function zw_regenerate_key() {
    if( empty( $_POST['zw_action'] ) || 'zw_regenerate_key' != sanitize_text_field($_POST['zw_action']) )
        return;
    if( ! wp_verify_nonce( sanitize_text_field($_POST['zw_regenerate_key_nonce']), 'zw_regenerate_key_nonce' ) )
        return;
    if (!current_user_can('manage_options'))
        return;

    $key = zw_generate_guidv4();
    update_option("zabbix_worker_key", $key);
}

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
        update_option("zabbix_worker_key", $key);
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
    return strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
}
