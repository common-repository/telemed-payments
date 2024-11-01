<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class telemed_admin {

    // This function will populate the options if the plugin is activated for the first time.
    // It will also protect the options if the plugin is deactivated (common in troubleshooting WP related issues)
    // We may want to add an option to remove DB entries...
    public function telemed_activate() {

        $option_name = 'clearent_opts';
        $options = get_option($option_name);

        $options['environment'] = isset($options['environment']) ? $options['environment'] : 'sandbox';
        $options['success_url'] = isset($options['success_url']) ? $options['success_url'] : '-1';
        $options['sb_api_key'] = isset($options['sb_api_key']) ? $options['sb_api_key'] : '';
        $options['prod_api_key'] = isset($options['prod_api_key']) ? $options['prod_api_key'] : '';
        $options['enable_debug'] = isset($options['enable_debug']) ? $options['enable_debug'] : 'disabled';

        update_option($option_name, $options);

    }

    // Initialize admin page
    public function telemed_admin_menu(){
        require_once('telemed_settings.php');
        $settings = new telemed_settings();
        // add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function);
        $wp_clearent_page = add_options_page('Telemed Payments', 'Telemed Payments', 'manage_options', 'telemed_option_group', array($settings, 'telemed_settingsPage'));
        add_action('admin_print_scripts-' . $wp_clearent_page, array($this, 'telemed_admin_scripts'));  // Load our admin page scripts (our page only)
        add_action('admin_print_styles-' . $wp_clearent_page, array($this, 'telemed_admin_print_styles'));    // Load our admin page stylesheet (our page only)
    }

    public function telemed_admin_scripts() {
        /*wp_deregister_script('jquery');
        wp_register_script('jquery', '//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js', '', '3.2.1');
        wp_enqueue_script('jquery');*/
        wp_enqueue_script('jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js', array('jquery'), '1.12.1');
        wp_enqueue_script('telemed-bootstrap', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js', array('jquery'), '3.3.7', true);
        wp_enqueue_script('telemed-bootstrap-tables', '//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.js', array('jquery'), '1.11.1', true);
        wp_enqueue_script('admin.js', plugins_url('/js/admin.js', dirname(__FILE__) ));
        wp_enqueue_script('loading.js', plugins_url('/js/loading.js', dirname(__FILE__) ));
        wp_enqueue_script('transaction_table.js', plugins_url('/js/transaction_table.js', dirname(__FILE__) ), array('jquery'));
    }

    public function telemed_admin_print_styles() {
        wp_enqueue_style('telemed-bootstrap', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css', '', '3.3.7');
        wp_enqueue_style('telemed-bootstrap-theme', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css', 'telemed-bootstrap', '3.3.7');
        wp_enqueue_style('bootstrap-tables', '//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.css', 'telemed-bootstrap');
        wp_enqueue_style('admin.css', plugins_url('/css/admin.css', dirname(__FILE__) ));
        wp_enqueue_style('loading.css', plugins_url('/css/loading.css', dirname(__FILE__) ));

    }

    // Register Plugin settings array
    public function telemed_register_settings() {
        $option_name = 'clearent_opts';
        register_setting('telemed_option_group', $option_name, array($this, 'validate_options'));
    }

    public function telemed_validate_options($input) {
        $valid = array();
        $valid['environment'] = isset($input['environment']) ? sanitize_text_field($input['environment']) : 'sandbox';
        $valid['success_url'] = isset($input['success_url']) ? sanitize_text_field($input['success_url']) : '-1';
        $valid['sb_api_key'] = isset($input['sb_api_key']) ? sanitize_text_field($input['sb_api_key']) : '';
        $valid['prod_api_key'] = isset($input['prod_api_key']) ? sanitize_text_field($input['prod_api_key']) : '';
        $valid['enable_debug'] = isset($input['enable_debug']) ? sanitize_text_field($input['enable_debug']) : '';
        $valid['notification_email'] = isset($input['notification_email'])
            && is_email($input['notification_email']) ? sanitize_text_field($input['notification_email']) : '';
        $valid['notification_email_from'] = isset($input['notification_email_from'])
            && is_email($input['notification_email_from']) ? sanitize_text_field($input['notification_email_from']) : '';
        $valid['notification_email_subject'] = isset($input['notification_email_subject']) ? sanitize_text_field($input['notification_email_subject']) : '';
        $valid['notification_email_from_name'] = isset($input['notification_email_from_name']) ? sanitize_text_field($input['notification_email_from_name']) : 'WordPress';
        $valid['email_company_name'] = isset($input['email_company_name']) ? sanitize_text_field($input['email_company_name']) : '';
        $valid['email_charge_from'] = isset($input['email_charge_from']) ? sanitize_text_field($input['email_charge_from']) : '';

        return $valid;
    }

    public function telemed_add_action_links ($links ) {
        $mylinks = array(
            '<a href="' . admin_url( 'options-general.php?page=telemed_option_group' ) . '">Settings</a>',
        );
        return array_merge( $mylinks, $links  );
    }

    public function tmpclearlog_admin_action() {
        // Do your stuff here
        //include plugin_dir_path(__FILE__).'../clearent_util.php';

        $confirmed = sanitize_text_field($_POST['confirm']);
        if ($confirmed == "true") {
            $plugin_path = sanitize_text_field($_POST['plugin_dir_path']);
            $cu = new telemed_util();
            $cu->telemed_clearLog($plugin_path);
            $cu->telemed_logMessage("User requested log file clear.", $plugin_path);
        }

        $redirct_url = esc_url_raw($_POST['redirect_url'] . "options-general.php?page=telemed_option_group&tab=debug_log");

        wp_redirect( $redirct_url );
        exit();
    }

    public function get_transaction_history_handler() {
        check_ajax_referer('tmp_transaction_history');
        $options = get_option("clearent_opts");

        $payment_data = array();
        if ($options['environment'] == "sandbox") {
            $mode = "sandbox";
        } else {
            $mode = "production";
        }

        // Only accept these sort values
        $sortby = array(
            'billing_firstname' => 'billing_firstname',
            'billing_lastname' => 'billing_lastname',
            'date_added'=>'date_added',
            'email_address' => 'email_address',
            'result' => 'result',
            'amount' => 'amount',
            'billing_city' => 'billing_city',
            'billing_state' => 'billing_state'
        );

        // Only accept these order by values
        $orderby = array(
            'desc' => 'DESC',
            'asc' => 'ASC'
        );
        // build search string to use with SQL LIKE, ex: LIKE '%val%' to find partial matches
        $search = '%';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) . '%' : '%';

        $sort = isset($_POST['sort']) && isset($sortby[$_POST['sort']]) ?
            sanitize_text_field($_POST['sort']) : "date_added";
        $order = isset($_POST['order']) && isset($orderby[$_POST['order']]) ?
            sanitize_text_field($_POST['order']) : "DESC";
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 0;



        global $wpdb;
        $table_name = $wpdb->prefix . "telemed_transaction";
        $query = "SELECT *
             FROM $table_name
             WHERE environment = '$mode'
             AND (billing_firstname LIKE %s 
                  OR billing_lastname LIKE %s
                  OR email_address LIKE %s
                  OR result LIKE %s
                  OR amount LIKE %s
                  OR billing_city LIKE %s
                  OR billing_state LIKE %s
                  OR date_added LIKE %s
                  )
             ORDER BY $sort $order LIMIT %d,%d"; //AND date_added > NOW() - INTERVAL 90 DAY
        $recordset = $wpdb->get_results(
            $wpdb->prepare($query, $search, $search, $search, $search, $search, $search, $search, $search, $offset, $limit)
        );

        $query = "SELECT COUNT(*) AS total
             FROM $table_name
             WHERE environment = '$mode'
             AND (billing_firstname LIKE %s 
                  OR billing_lastname LIKE %s
                  OR email_address LIKE %s
                  OR result LIKE %s
                  OR amount LIKE %s
                  OR billing_city LIKE %s
                  OR billing_state LIKE %s
                  OR date_added LIKE %s
                  )
             "; //AND date_added > NOW() - INTERVAL 90 DAY

        $total_count = $wpdb->get_var($wpdb->prepare($query, $search, $search, $search, $search, $search, $search, $search, $search));
        //unset($recordset['total_count']);
        $trnx_data = array(
            'total' => $total_count,
            'data' => $recordset,
        );
        wp_send_json($trnx_data);
    }

    public function enqueue_transactions_js($hook) {

        wp_enqueue_script( 'transaction_table.js', plugins_url( '/../js/transaction_table.js', __FILE__ ), array('jquery') );

        // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
        $trnx_nonce = wp_create_nonce("tmp_transaction_history");
        wp_localize_script( 'transaction_table.js', 'ajax_object',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => $trnx_nonce ) );
    }

    public function telemed_install_db() {
        global $wpdb;

        $table_name = $wpdb->prefix . "telemed_transaction";
        $charset_collate = $wpdb->get_charset_collate();

        // mysql char can hold up to 30 characters - switch to varchar if more is needed
        $sql = "CREATE TABLE $table_name (
            id CHAR(25) NOT NULL,
            environment CHAR(12) NOT NULL,
            display_message VARCHAR(255),
            transaction_type CHAR(15) NOT NULL,
            amount CHAR(10) NOT NULL,
            sales_tax_amount CHAR(10),
            card CHAR(19) NOT NULL,
            exp_date CHAR(4) NOT NULL,
            invoice VARCHAR(32),
            purchase_order VARCHAR(32),
            email_address VARCHAR(96),
            customer_id  VARCHAR(32),
            order_id VARCHAR(32),
            description TEXT,
            comments TEXT,
            billing_firstname VARCHAR(32),
            billing_lastname VARCHAR(32),
            billing_company VARCHAR(32),
            billing_street VARCHAR(128),
            billing_street2 VARCHAR(128),
            billing_city VARCHAR(128),
            billing_state VARCHAR(40),
            billing_zip CHAR(10),
            billing_country VARCHAR(128),
            billing_phone VARCHAR(32),
            billing_is_shipping tinyint(1),
            shipping_firstname VARCHAR(32),
            shipping_lastname VARCHAR(32),
            shipping_company VARCHAR(32),
            shipping_street VARCHAR(128),
            shipping_street2 VARCHAR(128),
            shipping_city VARCHAR(128),
            shipping_state VARCHAR(40),
            shipping_zip CHAR(10),
            shipping_country VARCHAR(128),
            shipping_phone VARCHAR(32),
            client_ip VARCHAR(45),
            transaction_id CHAR(30),
            authorization_code VARCHAR(32),
            exchange_id VARCHAR(128),
            result VARCHAR(32),
            result_code CHAR(10),
            response_raw TEXT,
            user_agent VARCHAR(255),
            date_added DATETIME NOT NULL,
            date_modified DATETIME NOT NULL,
            PRIMARY KEY  (id)
        )  $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

    }


}