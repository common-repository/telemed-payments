<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="postbox">
    <h3>Transaction History</h3>

    <?php

    $telemed_plugin_url = plugin_dir_url(__FILE__);
    $transaction_data_url = $telemed_plugin_url . "/transaction_data.php";
    $options = get_option("clearent_opts");

    $payment_data = array();
    if ($options['environment'] == "sandbox") {
        $mode = "sandbox";
    } else {
        $mode = "production";
    }

    ?>
        <table
            id="table"
            data-toggle="table"
            data-ajax="ajaxTransactionRequest"
            data-sort-name="date_added"
            data-sort-order="desc"
            data-search="true"
            data-detail-view="true"
            data-detail-formatter="detailFormatter"
            data-pagination="true"
            data-side-pagination="server"
            data-page-list="[5, 10, 20, 50, 100, 200]"
            data-classes="table table-hover">
            <thead>
            <tr>
                <th data-field="billing_firstname" data-sortable="true">First Name</th>
                <th data-field="billing_lastname" data-sortable="true">Last Name</th>
                <th data-field="email_address" data-sortable="true">Email</th>
                <th data-field="result" data-sortable="true">Result</th>
                <th data-field="amount" data-sortable="true">Amount</th>
                <th data-field="billing_city" data-sortable="true">City</th>
                <th data-field="billing_state" data-sortable="true">State</th>
                <th data-field="date_added" data-sortable="true">Date(UTC)</th>
            </tr>
            </thead>
        </table>
    <?php
/*
    global $wpdb;
    $table_name = $wpdb->prefix . "telemed_transaction";
    $query = "SELECT *
             FROM $table_name
             WHERE environment = '$mode'
             ORDER BY date_added DESC"; //AND date_added > NOW() - INTERVAL 90 DAY
    $recordset = $wpdb->get_results($query);
    if ($mode == "sandbox") {
        echo('<p class="warning">');
    } else {
        echo('<p>');
    }
    echo('Application is in ' . $mode . ' mode. Viewing transactions for ' . $mode . '.</p>');
    if (empty($recordset)) {
        echo('There are no transctions to display.');
    } else {
        echo('<p>Below is a list of transactions.  Most recent transactions are listed first.');
        echo('<br>Additional transactions can be accessed in your application database; up to 13 months previous transactions are available through Telemed\'s Virtual Terminal.</p>');
        echo('<table data-toggle="table" data-classes="table table-hover">');
        echo('  <tr>');
        echo('    <th>name</th>');
        echo('    <th>email</th>');
        echo('    <th>result</th>');
        echo('    <th>amount</th>');
        echo('    <th>city</th>');
        echo('    <th>state</th>');
        echo('    <th><div class="sortable">date(utc)</div></th>');
        echo('</tr>');

        //TODO: remove debug dump
        //echo '<pre>';var_dump($recordset);die();
        $total_amount_approved = 0;
        foreach ($recordset as $r) {
            echo('  <tr onclick="showDetails(\'' . $r->transaction_id . '\')">');
            if ($r - billing_lastname && $r->billing_firstname)
                echo('    <td>' . $r->billing_lastname . ', ' . $r->billing_firstname . '</td>');
            else
                echo('    <td></td>');
            $error_style = '';
            if ($r->result != "APPROVED") {
                $error_style = ' error ';
            } else {
                $total_amount_approved += $r->amount;
            }
            $message = '';
            $message .= '<span class="label' . $error_style . '">Result:</span><span class="' . $error_style . '">' . $r->result . '</span><br>';
            $message .= '<span class="label' . $error_style . '">Status:</span><span class="' . $error_style . '">' . $r->{'result_code'} . ' - ' . $r->{'display_message'} . '</span><br>';
            $message .= '<span class="label">Exchange ID:</span>' . $r->{'exchange_id'} . '<br>';
            $message .= '<span class="label">Transaction ID:</span>' . $r->{'transaction_id'} . '<br>';
            $message .= '<span class="label">Authorization Code:</span>' . $r->{'authorization-code'} . '<br>';
            $message .= '<span class="label">Amount:</span>' . $r->amount . '<br>';
            if ($r->sales_tax_amount) {
                $total = number_format((float)$r->amount + (float)$r->sales_tax_amount, 2, '.', '');
                $message .= '<span class="label">Sales Tax:</span>' . $r->sales_tax_amount . '<br>';
                $message .= '<span class="label">Total Amount:</span>' . $total . '<br>';
            }
            $message .= '<span class="label">Card:</span>' . $r->card . '<br>';
            $message .= '<span class="label">Expiration Date:</span>' . $r->{'exp_date'};
            echo('    <td>' . $r->email_address . '</td>');
            echo('    <td>' . $r->result . '</td>');
            echo('    <td class="text-center">' . $r->amount . '</td>');
            echo('    <td>' . $r->billing_city . '</td>');
            echo('    <td>' . $r->billing_state . '</td>');
            echo('    <td>' . $r->date_added . '</td>');
            $billingAddress = '';
            if ($r->billing_firstname || $r->billing_lastname) {
                $billingAddress .= $r->billing_firstname . ' ' . $r->billing_lastname . '<br>';
            }
            if ($r->billing_company) {
                $billingAddress .= $r->billing_company . '<br>';
            }
            if ($r->billing_street) {
                $billingAddress .= $r->billing_street . '<br>';
            }
            if ($r->billing_street2) {
                $billingAddress .= $r->billing_street2 . '<br>';
            }
            if ($r->billing_city || $r->billing_state || $r->billing_zip) {
                $billingAddress .= $r->billing_city . ', ' . $r->billing_state . '&nbsp;&nbsp;' . $r->billing_zip . '<br>';
            }
            if ($r->billing_country) {
                $billingAddress .= $r->billing_country . '<br>';
            }
            if ($r->billing_phone) {
                $billingAddress .= $r->billing_phone . '<br>';
            }
            //echo('    <td>' . $billingAddress . '</td>');
            $shippingAddress = '';
            if ($r->shipping_firstname || $r->shipping_lastname) {
                $shippingAddress .= $r->shipping_firstname . ' ' . $r->shipping_lastname . '<br>';
            }
            if ($r->shipping_company) {
                $shippingAddress .= $r->shipping_company . '<br>';
            }
            if ($r->shipping_street) {
                $shippingAddress .= $r->shipping_street . '<br>';
            }
            if ($r->shipping_street2) {
                $shippingAddress .= $r->shipping_street2 . '<br>';
            }
            if ($r->shipping_city || $r->shipping_state || $r->shipping_zip) {
                $shippingAddress .= $r->shipping_city . ', ' . $r->shipping_state . '&nbsp;&nbsp;' . $r->shipping_zip . '<br>';
            }
            if ($r->shipping_country) {
                $shippingAddress .= $r->shipping_country . '<br>';
            }
            if ($r->shipping_phone) {
                $shippingAddress .= $r->shipping_phone . '<br>';
            }
            //echo('    <td>' . $shippingAddress . '</td>');
            //echo('    <td><span class="label">created:</span>' . $r->date_added . '<br>'
            //    . '<span class="label">modified:</span>' . $r->date_modified . '</td>');
            echo('</tr>');
        }

        echo('</table>');
        echo('<div><p>Total approved $' . $total_amount_approved . '</p></div>');
*/
        echo('<div style="display:none;">');
        echo('    <div id="dialog" title="Transaction Detail"></div>');
        echo('</div>');

    //}

    ?>
</div>
