<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class telemed_util {

    protected $option_name = 'clearent_opts';

    public function telemed_add_record($table_name, $values) {
        global $wpdb;

        $table = $wpdb->prefix . $table_name;
        $wpdb->insert($table, $values);

    }

    public function telemed_get_year_options() {
        // set up year dropdown for expiration month
        $year_options = '';
        $today = getdate();
        for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
            $year_options .= '<option value="' . strftime('%y', mktime(0, 0, 0, 1, 1, $i)) . '">' . strftime('%Y', mktime(0, 0, 0, 1, 1, $i)) . '</option>';
        }
        return $year_options;
    }

    public function telemed_get_state_options() {
        $states = array(
            "AL" => "Alabama",
            "AK" => "Alaska",
            "AZ" => "Arizona",
            "AR" => "Arkansas",
            "CA" => "California",
            "CO" => "Colorado",
            "CT" => "Connecticut",
            "DE" => "Delaware",
            "DC" => "District Of Columbia",
            "FL" => "Florida",
            "GA" => "Georgia",
            "HI" => "Hawaii",
            "ID" => "Idaho",
            "IL" => "Illinois",
            "IN" => "Indiana",
            "IA" => "Iowa",
            "KS" => "Kansas",
            "KY" => "Kentucky",
            "LA" => "Louisiana",
            "ME" => "Maine",
            "MD" => "Maryland",
            "MA" => "Massachusetts",
            "MI" => "Michigan",
            "MN" => "Minnesota",
            "MS" => "Mississippi",
            "MO" => "Missouri",
            "MT" => "Montana",
            "NE" => "Nebraska",
            "NV" => "Nevada",
            "NH" => "New Hampshire",
            "NJ" => "New Jersey",
            "NM" => "New Mexico",
            "NY" => "New York",
            "NC" => "North Carolina",
            "ND" => "North Dakota",
            "OH" => "Ohio",
            "OK" => "Oklahoma",
            "OR" => "Oregon",
            "PA" => "Pennsylvania",
            "RI" => "Rhode Island",
            "SC" => "South Carolina",
            "SD" => "South Dakota",
            "TN" => "Tennessee",
            "TX" => "Texas",
            "UT" => "Utah",
            "VT" => "Vermont",
            "VA" => "Virginia",
            "WA" => "Washington",
            "WV" => "West Virginia",
            "WI" => "Wisconsin",
            "WY" => "Wyoming"
        );

        $state_options = '<option value="" disabled="disabled" selected="selected" style="display:none">State</option>';
        foreach ($states as $key => $value) {
            $state_options .= '<option value="' . $key . '">' . $value . '</option>';
        }

        return $state_options;

    }

    function telemed_ccMasking($number, $maskingCharacter = '#') {
        return substr($number, 0, 4) . str_repeat($maskingCharacter, strlen($number) - 8) . substr($number, -4);
    }

    public function telemed_sendPayment($url, $payment_data) {

        $headers = array(
            "Accept" => "application/json",
            "Content-Type" => "application/json",
            "Cache-Control" => "no-cache"
        );

        $args = array(
            'body'=>json_encode($payment_data),
            'headers'=>$headers,
            'sslverify' => false
        );

        $raw_response = wp_remote_post($url, $args);
        $response = wp_remote_retrieve_body($raw_response);

        //replaced curl with wordpress http api call

        $this->telemed_logger("--------------------- begin post response ---------------------");
        $this->telemed_logger($response);
        $this->telemed_logger("--------------------- end post response ---------------------");

        return $response;
    }

    public function telemed_logger($message, $prefix = '') {
        // recursively walks message if array is passed in
        $debug = get_option($this->option_name)['enable_debug'] == 'enabled';
        if ($debug) {
            if (is_array($message)) {
                foreach ($message as $key => $value) {
                    if (is_array($value)) {
                        $this->telemed_logger($value, $prefix . $key . '.');
                    } else {
                        if ($key == 'card') {
                            // if array contains card then sanitize output
                            // never, ever, ever, ever, ever, ever, ever log raw card numbers
                            $this->telemed_logMessage($prefix . $key . ' = ' . (str_repeat('X', strlen($value) - 4) . substr($value, -4)));
                        } else if ($key == 'exp-date'|| $key == 'csc' || $key == 'api-key') {
                            $this->telemed_logMessage($prefix . $key . ' = [redacted]');
                        } else {
                            $this->telemed_logMessage($prefix . $key . ' = ' . $value);
                        }
                    }
                }
            } else {
                $this->telemed_logMessage($prefix . $message);
            }
        }
    }

    public function telemed_logMessage($msg, $path = "") {
        if ($path == "") {
            $path = plugin_dir_path(__FILE__);
        }
        $logfile = $path . "log/debug.log";
        $msg = date('Y-m-d H:i:s') . ": " . $msg;
        error_log($msg . "\n", 3, $logfile);
    }

    function array_clone($array) {
        return array_map(function ($element) {
            return ((is_array($element))
                ? call_user_func(__FUNCTION__, $element)
                : ((is_object($element))
                    ? clone $element
                    : $element
                )
            );
        }, $array);
    }

    public function telemed_clearLog($path) {
        $logfile = $path . "log/debug.log";
        file_put_contents($logfile, "");
    }


}

