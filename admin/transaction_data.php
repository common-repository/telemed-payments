<?php

/**
 * Created by PhpStorm.
 * User: BWoods
 * Date: 7/4/2017
 * Time: 1:32 PM
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$options = get_option("clearent_opts");

$payment_data = array();
if ($options['environment'] == "sandbox") {
    $mode = "sandbox";
} else {
    $mode = "production";
}

global $wpdb;
$table_name = $wpdb->prefix . "telemed_transaction";
$query = "SELECT *
             FROM $table_name
             WHERE environment = '$mode'
             ORDER BY date_added DESC"; //AND date_added > NOW() - INTERVAL 90 DAY
$recordset = $wpdb->get_results($query);

echo json_encode($recordset);