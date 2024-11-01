<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Plugin Name: Telemed Payments
 * Plugin URI: https://wordpress.org/plugins/telemed-payments/
 * Description: Simple Secure, PCI Compliant, Telemedicine payments for your WordPress site. This plugin is maintained by Telemed Processing, LLC., Agent of Clearent PLC, a registered MAP of Central Bank of STL.
 * Version: 1.0
 * Author: Telemed Processing, LLC.
 * Author URI: https://telemedprocessing.com
 */
define('WP_DEBUG', true);
const PLUGIN_VERSION = 1.0; //based on clearent version 1.8

class telemed_clearent {

    const TESTING_API_URL = "https://gateway-dev.clearent.net/rest/v2/transactions";
    const SANDBOX_API_URL = "https://gateway-sb.clearent.net/rest/v2/transactions";
    const PRODUCTION_API_URL = "https://gateway.clearent.net/rest/v2/transactions";

    protected $option_name = 'clearent_opts';

    public function __construct() {
        require_once('admin/telemed_admin.php');
        require_once('admin/telemed_transactions.php');
        require_once('telemed_util.php');
        require_once('payment/telemed_payment.php');

        $admin = new telemed_admin();
        $this->clearent_util = new telemed_util();
        $transactions = new telemed_transactions();
        $payment = new telemed_payment();

        // seession management needed for this plugin
        add_action('init', array($this, 'myStartSession'));                               // Used to create a session for storing tranaaction data
        add_action('wp_login', array($this, 'myEndSession'));                             // Used to destroy session after login
        add_action('wp_logout', array($this, 'myEndSession'));                            // Used to destroy session after logout
        // registration hooks
        register_activation_hook(__FILE__, array($admin, 'telemed_activate'));                    // Activate plugin
        register_activation_hook(__FILE__, array($admin, 'telemed_install_db'));                  // Create database tables
        // admin hooks
        add_action('admin_menu', array($admin, 'telemed_admin_menu'));                            // Creates admin menu page and conditionally loads scripts and styles on admin page
        add_action('admin_init', array($admin, 'telemed_register_settings'));                      // Used for registering settings
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($admin, 'telemed_add_action_links'));
        // transaction detail in settings, hooks
        //add_action('admin_post_nopriv_transaction_detail', array($transactions, 'transaction_detail'));     // hook for transaction calls - non-logged in user
        add_action('admin_post_transaction_detail', array($transactions, 'telemed_transaction_detail'));    // hook for transaction calls - logged in user
        // transaction hooks
        add_action('admin_post_transaction', array($payment, 'telemed_validate'));                   // hook for transaction calls - logged in user
        add_action('admin_post_nopriv_transaction', array($payment, 'telemed_validate'));            // hook for transaction calls - non-logged in user
        add_action( 'admin_action_tmpclearlog', array($admin, 'tmpclearlog_admin_action' )); // hook for form post to clear debug log
        add_action('wp_ajax_get_transaction_history', array($admin, 'get_transaction_history_handler')); // hook for ajax on transactions table
        add_action( 'admin_enqueue_scripts', array($admin, 'enqueue_transactions_js')); // hook for ajax on transactions table

        // shortcode hooks
        add_shortcode('telemed_pay_form', array($payment, 'telemed_pay_form'));            // builds content for embedded form

    }

    // attempt to create a session
    function myStartSession() {
        if (!session_id()) {
            session_start();
        }
    }

    function myEndSession() {
        session_destroy();
    }

}

$wp_clearent = new telemed_clearent();
