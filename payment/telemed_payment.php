<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class
telemed_payment
{

    protected $option_name = "clearent_opts";

    public $telemed_default_atts = array(
        "amount" => 0,
        "sales_tax_amount" => 0,
        "test" => null,
        // labels
        "title" => "Complete Transaction Details Below",
        "button_text" => "Pay Now",
        "amount_label" => "Amount",
        "card_label" => "Card Number",
        "exp_date_label" => "Card Expiration Date",
        "csc_label" => "Card Security Code",
        "invoice_label" => "Invoice Number",
        "purchase_order_label" => "Purchase Order",
        "email_address_label" => "Email Address",
        "customer_id_label" => "Customer ID",
        "order_id_label" => "Order ID",
        "description_label" => "Description",
        "comments_label" => "Comments",
        "billing_address_label" => "Billing Address",
        "billing_first_name_label" => "First Name",
        "billing_last_name_label" => "Last Name",
        "billing_company_label" => "Company",
        "billing_street_label" => "Address",
        "billing_street2_label" => "Address Line 2",
        "billing_city_label" => "City",
        "billing_state_label" => "State",
        "billing_zip_label" => "Zip",
        "billing_country_label" => "Country",
        "billing_phone_label" => "Phone",
        "shipping_address_label" => "Shipping",
        "billing_is_shipping_label" => "Same as billing address",
        "shipping_first_name_label" => "First Name",
        "shipping_last_name_label" => "Last Name",
        "shipping_company_label" => "Company",
        "shipping_street_label" => "Address",
        "shipping_street2_label" => "Address Line 2",
        "shipping_city_label" => "City",
        "shipping_state_label" => "State",
        "shipping_zip_label" => "Zip",
        "shipping_country_label" => "Country",
        "shipping_phone_label" => "Phone",
        // optional fields
        "invoice" => false,
        "purchase_order" => false,
        "email_address" => false,
        "customer_id" => false,
        "order_id" => false,
        "description" => false,
        "comments" => false,
        // shipping/billing
        "billing_address" => false,
        "shipping_address" => false,
        // field options
        "require_billing_address" => false,
        "require_shipping_address" => false,
        "require_email_address" => false,
        "require_csc" => true
    );

    public function __construct()
    {
        require_once(dirname(__FILE__) . "/../telemed_util.php");
        //include(dirname(__FILE__) . "/../clearent_util.php");
        $this->clearent_util = new telemed_util();
    }

    public function telemed_validate_shortcode($atts)
    {
        $error_atts = array();
        foreach ($atts as $key => $value) {
            if (!array_key_exists($key, $this->telemed_default_atts)) {
                array_push($error_atts, $key);
            }
        }
        return $error_atts;
    }

    public function telemed_pay_form($atts, $content, $tag)
    {

        // set up directories
        $plugins_url = plugins_url();
        $get_admin_url = get_admin_url();

        $js_path = $plugins_url . "/telemed-payments/js/";
        $css_path = $plugins_url . "/telemed-payments/css/";
        $image_path = $plugins_url . "/telemed-payments/image/";

        wp_enqueue_script("jquery-ui-autocomplete");
        wp_enqueue_style("jquery-ui", $css_path . "jquery-ui.min.css");

        // verify shortcode attributes
        $error_atts = $this->telemed_validate_shortcode($atts);

        $form = "";

        if (count($error_atts) > 0) {
            // dump errors and do not build form
            $form .= '<link type="text/css" rel="stylesheet" href="' . $css_path . 'clearent.css" />';
            $form .= '<div class="clearent-warning">Webmaster: The following attributes in your Telemeds plugin shortcode are invalid.
                        Please remove or correct these invalid entries to display the payment form:</div>
                        <div id="clearent-invalid-shortcode-block" class="clearent-invalid-shortcode-block">';

            foreach ($error_atts as &$value) {
                $form .= '<div class="clearent-invalid-shortcode">' . $value . '</div>';
            }

            $form .= '</div><div id="errors_message_bottom" class="clearent-warning"><span>Please correct errors noted above.</span></div>';

            return $form;
        }

        // get shortcode options
        $a = $this->telemed_parse_form_options($atts);

        if ((is_bool($a['test']) && $a['test'])) {
            $_SESSION["test"] = true;
        } else {
            $_SESSION["test"] = false;
        }

        // get year dropdown options
        $year_options = $this->clearent_util->telemed_get_year_options();

        $_SESSION["clearent.amount"] = $a["amount"];

        if (floatval($a["sales-tax-amount"]) > 0) {
            $_SESSION["clearent.sales-tax-amount"] = $a["sales-tax-amount"];
        } else {
            unset($_SESSION["clearent.sales-tax-amount"]);
        }

        $_SESSION["clearent.require-csc"] = (is_bool($a["require-csc"]) && $a["require-csc"] != false);
        $_SESSION["clearent.require-billing-address"] = (is_bool($a["require-billing-address"]) && $a["require-billing-address"] != false);
        $_SESSION["clearent.require-shipping-address"] = (is_bool($a["require-shipping-address"]) && $a["require-shipping-address"] != false);
        $_SESSION["clearent.require-email-address"] = (is_bool($a["require-email-address"]) && $a["require-email-address"] != false);

        $this->clearent_util->telemed_logger("--------------------- begin parsed attributes (merged with default values) ---------------------");
        $this->clearent_util->telemed_logger($a);
        $this->clearent_util->telemed_logger("--------------------- end parsed attributes ---------------------");

        $trans_url = $get_admin_url . "admin-post.php";
        $form .= '<script type="text/javascript" src="' . $js_path . 'clearent.js" ></script>';
        $form .= '<script type="text/javascript" src="' . $js_path . 'loading.js" ></script>';
        $form .= '<script type="text/javascript" src="' . $js_path . 'telemed.js" ></script>';
        $form .= '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
        $form .= '<link type="text/css" rel="stylesheet" href="' . $css_path . 'clearent.css" />';
        $form .= '<link type="text/css" rel="stylesheet" href="' . $css_path . 'loading.css" />';
        $form .= '<script type="text/javascript">
                    var  trans_url =  "' . $trans_url . '";
                    function onSubmit(token) {
                        Clearent.pay();
                    }
                  </script>
                  <div class="wp_clearent_button">
                    <h3 class="clearent_pay_box">' . $a['title'] . '</h3>
                    <div id="errors" class="hidden clearent-warning"><span id="errors_message"></span></div>
                    <form action="' . $get_admin_url . 'admin-post.php" method="POST" class="clearent-payment-form" autocomplete="off">
                      <div class="clearent-card-acceptance">
                          <img class="acceptedCards" src="' . $image_path . 'clearent-cards.png">
                      </div>
                      <span class="clearent_required_note">* indicates required field</span>
                      <input style="display: none;" type="text" autocomplete="foo" />
                      <table class="clearent-table">
                        <tbody>';
        /* if developer set amount to input then show amount field  */
        if (floatval($a['amount']) <= 0) {
            $form .= '<tr>
                            <td><label for="amount">* ' . $a['amount-label'] . '</label></td>
                            <td>
                              <input type="text" id="amount" name="amount" value="" />
                            </td>
                          </tr>';
        }
        $form .= '<tr>
                            <td>
                              <label for="card">* ' . $a['card-label'] . '</label>
                            </td>
                            <td>
                              <input type="text" id="card" name="card" value="" maxlength="27" />
                            </td>
                          </tr>
                          <tr>
                            <td>
                              <label for="expire-date-month">* ' . $a['exp-date-label'] . '</label>
                            </td>
                            <td>
                              <select name="expire-date-month" id="expire-date-month" class="clearent-select-field telemed-expiration-date">
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08">August</option>
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                              </select>
                              /
                              <select name="expire-date-year" id="expire-date-year" class="clearent-select-field telemed-expiration-date">
                              ' . $year_options . '
                              </select>
                            </td>
                          </tr>
                          <tr>
                            <td>
                              <label for="csc">' . ((is_bool($a['require-csc']) && $a['require-csc'] != false) ? '* ' : '&nbsp;&nbsp; ') . $a['csc-label'] . '</label>
                            </td>
                            <td>
                              <input type="text" id="csc" name="csc" value="" />
                            </td>
                          </tr>';

        /* optional field - show if set to true in shortcode - hidden if value set in short code - not present if set to false in shortcode or not set  */
        if (is_bool($a['invoice']) && $a['invoice'] != false) {
            $form .= '<tr>
                        <td><label for="invoice">' . $a['invoice-label'] . '</label></td>
                        <td>
                          <input type="text" id="invoice" name="invoice" value="" />
                        </td>
                      </tr>';
        } else if (!is_bool($a['invoice']) && isset($a['invoice'])) {
            $form .= '<input type="hidden" id="invoice" name="invoice" value="' . ($a['invoice']) . '" />';
        }

        /* optional field - show if set to true in shortcode - hidden if value set in short code - not present if set to false in shortcode or not set  */
        if (is_bool($a['purchase-order']) && $a['purchase-order'] != false) {
            $form .= '<tr>
                        <td><label for="purchase-order">' . $a['purchase-order-label'] . '</label></td>
                        <td>
                          <input type="text" id="purchase-order" name="purchase-order" value="" />
                        </td>
                      </tr>';
        } else if (!is_bool($a['purchase-order']) && isset($a['purchase-order'])) {
            $form .= '<input type="hidden" id="purchase-order" name="purchase-order" value="' . ($a['purchase-order'] == 'true' ? "" : $a['purchase-order']) . '" />';
        }

        /* optional field - show if set to true in shortcode - hidden if value set in short code - not present if set to false in shortcode or not set  */
        if (is_bool($a['email-address']) && $a['email-address'] != false || (is_bool($a['require-email-address']) && $a['require-email-address'] != false)) {
            $form .= '<tr>
                        <td><label for="email-address">' . ((is_bool($a['require-email-address']) && $a['require-email-address'] != false) ? '* ' : '') . $a['email-address-label'] . '</label></td>
                        <td>
                          <input type="text" id="email-address" name="email-address" value="" />
                        </td>
                      </tr>';
        } else if (!is_bool($a['email-address']) && isset($a['email-address'])) {
            $form .= '<input type="hidden" id="email-address" name="email-address" value="' . ($a['email-address'] == 'true' ? "" : $a['email-address']) . '" />';
        }

        /* optional field - show if set to true in shortcode - hidden if value set in short code - not present if set to false in shortcode or not set  */
        if (is_bool($a['customer-id']) && $a['customer-id'] != false) {
            $form .= '<tr>
                        <td><label for="customer-id">' . $a['customer-id-label'] . '</label></td>
                        <td>
                          <input type="text" id="customer-id" name="customer-id" value="" />
                        </td>
                      </tr>';
        } else if (!is_bool($a['customer-id']) && isset($a['customer-id'])) {
            $form .= '<input type="hidden" id="customer-id" name="customer-id" value="' . ($a['customer-id'] == 'true' ? "" : $a['customer-id']) . '" />';
        }

        /* optional field - show if set to true in shortcode - hidden if value set in short code - not present if set to false in shortcode or not set  */
        if (is_bool($a['order-id']) && $a['order-id'] != false) {
            $form .= '<tr>
                        <td><label for="order-id">' . $a['order-id-label'] . '</label></td>
                        <td>
                          <input type="text" id="order-id" name="order-id" value="" />
                        </td>
                      </tr>';
        } else if (!is_bool($a['order-id']) && isset($a['order-id'])) {
            $form .= '<input type="hidden" id="order-id" name="order-id" value="' . ($a['order-id'] == 'true' ? "" : $a['order-id']) . '" />';
        }

        /* optional field - show if set to true in shortcode - hidden if value set in short code - not present if set to false in shortcode or not set  */
        if (is_bool($a['description']) && $a['description'] != false) {
            $form .= '<tr>
                        <td><label for="description">' . $a['description-label'] . '</label></td>
                        <td>
                          <input type="text" id="description" name="description" value="" />
                        </td>
                      </tr>';
        } else if (!is_bool($a['description']) && isset($a['description'])) {
            $form .= '<input type="hidden" id="description" name="description" value="' . ($a['description'] == 'true' ? "" : $a['description']) . '" />';
        }

        /* optional field - show if set to true in shortcode - hidden if value set in short code - not present if set to false in shortcode or not set  */
        if (is_bool($a['comments']) && $a['comments'] != false) {
            $form .= '<tr>
                        <td><label for="comments">' . $a['comments-label'] . '</label></td>
                        <td>
                          <input type="text" id="comments" name="comments" value="" />
                        </td>
                      </tr>';
        } else if (!is_bool($a['comments']) && isset($a['comments'])) {
            $form .= '<input type="hidden" id="comments" name="comments" value="' . ($a['comments'] == 'true' ? "" : $a['comments']) . '" />';
        }

        if ((is_bool($a['billing-address']) && $a['billing-address'] != false) || (is_bool($a['require-billing-address']) && $a['require-billing-address'] != false)) {
            $form .= '
                          <tr>
                            <td class="clearent-table-heading">' . ((is_bool($a['require-billing-address']) && $a['require-billing-address'] != false) ? '* ' : '') . $a['billing-address-label'] . '</td>
                            <td></td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="billing-first-name">' . $a['billing-first-name-label'] . '</label></td>
                            <td>
                              <input type="text" id="billing-first-name" name="billing-first-name" value="" />
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="billing-last-name">' . $a['billing-last-name-label'] . '</label></td>
                            <td>
                              <input type="text" id="billing-last-name" name="billing-last-name" value="" />
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="billing-company">' . $a['billing-company-label'] . '</label></td>
                            <td>
                              <input type="text" id="billing-company" name="billing-company" value="" />
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="billing-street">' . $a['billing-street-label'] . '</label></td>
                            <td>
                              <input type="text" id="billing-street" name="billing-street" value="" />
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="billing-street2">' . $a['billing-street2-label'] . '</label></td>
                            <td>
                              <input type="text" id="billing-street2" name="billing-street2" value="" />
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="billing-city">' . $a['billing-city-label'] . '</label></td>
                            <td>
                              <input type="text" name="billing-city" id="billing-city" value="" />
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="billing-state">' . $a['billing-state-label'] . '</label></td>
                            <td>
                              <!-- <input autocomplete="off" type="text" name="billing-state" id="billing-state" /> -->
                              <select name="billing-state" id="billing-state" class="form-control">'.$this->getStatesForSelect().'</select>
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="billing-zip">' . $a['billing-zip-label'] . '</label></td>
                            <td>
                              <input type="text" name="billing-zip" id="billing-zip" value="" />
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="billing-country">' . $a['billing-country-label'] . '</label></td>
                            <td>
                              <!-- <input type="text" name="billing-country" id="billing-country" value="" /> -->
                              <select name="billing-country" id="billing-country" class="form-control">'.$this->getCountriesForSelect().'</select>
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="billing-phone">' . $a['billing-phone-label'] . '</label></td>
                            <td>
                              <input type="text" name="billing-phone" id="billing-phone" value="" />
                            </td>
                          </tr>
                          ';
        }

        if ((is_bool($a['shipping-address']) && $a['shipping-address'] != false) || (is_bool($a['require-shipping-address']) && $a['require-shipping-address'] != false)) {
            $form .= '
                          <tr>
                            <td class="clearent-table-heading">' . ((is_bool($a['require-shipping-address']) && $a['require-shipping-address'] != false) ? '* ' : '') . $a['shipping-address-label'] . '</td>
                            <td>'
                .
                (((is_bool($a['billing-address']) && $a['billing-address'] != false) || (is_bool($a['require-billing-address']) && $a['require-billing-address'] != false)) ? '<input type="checkbox" name="billing-is-shipping" id="billing-is-shipping" value="true"  />&nbsp;<label class="clearent-inline-label" for="billing-is-shipping">' . $a['billing-is-shipping-label'] . '</label>' : '')
                .
                '</td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="shipping-first-name">' . $a['shipping-first-name-label'] . '</label></td>
                            <td>
                              <input type="text" id="shipping-first-name" name="shipping-first-name" value="" />
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="shipping-last-name">' . $a['shipping-last-name-label'] . '</label></td>
                            <td>
                              <input type="text" id="shipping-last-name" name="shipping-last-name" value="" />
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="shipping-company">' . $a['shipping-company-label'] . '</label></td>
                            <td>
                              <input type="text" id="shipping-company" name="shipping-company" value="" />
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="shipping-street">' . $a['shipping-street-label'] . '</label></td>
                            <td>
                              <input type="text" name="shipping-street" id="shipping-street" value="" />
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="shipping-street2">' . $a['shipping-street2-label'] . '</label></td>
                            <td>
                              <input type="text" name="shipping-street2" id="shipping-street2" value="" />
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="shipping-city">' . $a['shipping-city-label'] . '</label></td>
                            <td>
                              <input type="text" name="shipping-city" id="shipping-city" value="" />
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="shipping-state">' . $a['shipping-state-label'] . '</label></td>
                            <td>
                              <!-- <input type="text" name="shipping-state" id="shipping-state" /> -->
                              <select name="shipping-state" id="shipping-state" class="form-control">'.$this->getStatesForSelect().'</select>
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="shipping-zip">' . $a['shipping-zip-label'] . '</label></td>
                            <td>
                              <input type="text" name="shipping-zip" id="shipping-zip" value="" />
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="shipping-country">' . $a['shipping-country-label'] . '</label></td>
                            <td>
                              <!-- <input type="text" name="shipping-country" id="shipping-country" value="" /> -->
                              <select name="shipping-country" id="shipping-country" class="form-control">'.$this->getCountriesForSelect().'</select>
                            </td>
                          </tr>
                          <tr>
                            <td><label class="clearent-address-label" for="shipping-phone">' . $a['shipping-phone-label'] . '</label></td>
                            <td>
                              <input type="text" name="shipping-phone" id="shipping-phone" value="" />
                            </td>
                          </tr>
                          ';
        }

        $form .= '<tr>
                    <td></td>
                    <td>
                        <button
                            id="wp_clearent_submit"
                            name="wp_clearent_submit"
                            class="submit_wp_clearent g-recaptcha"
                            data-sitekey="' . $this->telemed_getCaptchaPublicKey() . '"
                            data-callback="onSubmit">
                            ' . $a['button-text'] . '
                        </button>
                    </td>
                  </tr>
                </tbody>
              </table>
              <div id="errors_message_bottom" class="hidden clearent-warning"><span>Please correct errors noted above.</span></div>
              <div class="clearent-security">
                <div class="telemed_tag">Telemed Processing </div><div class="clearent-lock" aria-hidden="true"></div>Secured by <a title="http://www.clearent.com/" href="http://www.clearent.com/" target="_blank"><div class="clearent-logo logo"></div></a>
              </div>
            </form>
          </div>';

        return $form;
    }

    public function telemed_parse_form_options($atts)
    {
        // get shortcode properties
        $atts = shortcode_atts($this->telemed_default_atts, $atts);

        $a = array();

        foreach ($atts as $key => $value) {
            // wordpress before 4.3 does not support hypens in shortcode attribute names
            // our api uses hypens so to keep the internal code clean I am converting
            // shortcode underscores to hyphens in the resulting array
            //$key = str_replace ( "_" , "-", $key);

            $this->clearent_util->telemed_logger("BEFORE: " . $key . " = " . json_encode($value));

            $newKey = str_replace("_", "-", $key);

            if ($value === "true" || $value === true) {
                $newValue = true;
                $this->clearent_util->telemed_logger("converting to boolean: true");
            } elseif ($value === "false" || $value === false) {
                $newValue = false;
                $this->clearent_util->telemed_logger("converting to boolean: false");
            } else {
                $newValue = $value;
            }

            $a[$newKey] = $newValue;

            if ($newKey != $key || $newValue != $value) {
                $this->clearent_util->telemed_logger(" AFTER: " . $newKey . " = " . json_encode($newValue));
            }

        }

        return $a;
    }

    function telemed_getRealIpAddr()
    {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            //check ip from share internet
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            //to check ip is pass from proxy
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        return $ip;
    }

    public function telemed_validate()
    {

        $this->clearent_util->telemed_logger("validating transaction data");

        $has_errors = false;
        $response = array();
        $response["error"] = "";

        // check Amount
        if (!$_REQUEST["amount"]) {
            $amount = sanitize_text_field($_SESSION["clearent.amount"]);
        } else {
            $amount = sanitize_text_field($_REQUEST["amount"]);
        }

        if (floatval($amount) <= 0) {
            $message = "Amount is required.";
            $this->clearent_util->telemed_logger($message);
            $response["error"] = $response["error"] . $message . "<br>";
            $has_errors = true;
        }

        // check Card
        if (!$_REQUEST["card"]) {
            $message = "Card Number is required.";
            $this->clearent_util->telemed_logger($message);
            $response["error"] = $response["error"] . $message . "<br>";
            $has_errors = true;
        } else if (strlen(preg_replace("/[^0-9]/", "", sanitize_text_field($_REQUEST["card"]))) < 13 || strlen(preg_replace("/[^0-9]/", "", sanitize_text_field($_REQUEST["card"]))) > 19) {
            $message = "Card Number must be between 13 and 19 characters in length.";
            $this->clearent_util->telemed_logger($message);
            $response["error"] = $response["error"] . $message . "<br>";
            $has_errors = true;
        }

        // check Date
        $today = getdate();
        $selected_month = intval(sanitize_text_field($_REQUEST["expire-date-month"]));
        $current_month = $today["mon"];
        $selected_year = sanitize_text_field($_REQUEST["expire-date-year"]);
        $current_year = strftime("%y", mktime(0, 0, 0, 1, 1, $today["year"]));

        if ($selected_year < $current_year || ($selected_month < $current_month && $selected_year == $current_year)) {
            $message = "Card Expiration Date can not be in the past.";
            $this->clearent_util->telemed_logger($message);
            $this->clearent_util->telemed_logger("selected month/year = " . $selected_month . " / " . $selected_year);
            $this->clearent_util->telemed_logger("current month/year = " . $current_month . " / " . $current_year);
            $response["error"] = $response["error"] . $message . "<br>";
            $has_errors = true;
        }

        // check CSC
        if (is_bool($_SESSION["clearent.require-csc"]) && $_SESSION["clearent.require-csc"] != false) {
            // check for csc
            if (strlen(sanitize_text_field($_REQUEST["csc"])) == 0) {
                $message = "Card Security Code is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            } else if (isset($_REQUEST["csc"]) && !in_array(strlen($_REQUEST["csc"]), [3, 4])) {
                // required - must be 3 or 4 characters
                $message = "Card Security Code must be 3 or 4 characters.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
        } else if (isset($_REQUEST["csc"]) && !in_array(strlen($_REQUEST["csc"]), [0, 3, 4])) {
            // not required - must be 0, 3 or 4 characters
            $message = "Card Security Code must be 3 or 4 characters.";
            $this->clearent_util->telemed_logger($message);
            $response["error"] = $response["error"] . $message . "<br>";
            $has_errors = true;
        }

        // check billing address
        $require_billing_address = is_bool($_SESSION["clearent.require-billing-address"]) && $_SESSION["clearent.require-billing-address"] != false;
        $require_shipping_address = is_bool($_SESSION["clearent.require-shipping-address"]) && $_SESSION["clearent.require-shipping-address"] != false;
        $require_email_address = is_bool($_SESSION["clearent.require-email-address"]) && $_SESSION["clearent.require-email-address"] != false;
        // request params hit server as strings so we test for "false" not false
        $billing_is_shipping = $_REQUEST["billing-is-shipping"] && $_REQUEST["billing-is-shipping"] != "false";

        if ($require_billing_address || ($require_shipping_address && $billing_is_shipping)) {
            // require fields if(require-billing-address=true || (require-shipping-address=true && billing-is-shipping=true))
            if (!$_REQUEST["billing-first-name"]) {
                $message = "Billing Address First Name is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
            if (!$_REQUEST["billing-last-name"]) {
                $message = "Billing Address Last Name is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
            if (!$_REQUEST["billing-street"]) {
                $message = "Billing Address Street is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
            if (!$_REQUEST["billing-city"]) {
                $message = "Billing Address City is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
            if (!$_REQUEST["billing-state"]) {
                $message = "Billing Address State is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
            if (!$_REQUEST["billing-zip"]) {
                $message = "Billing Address Zip is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
            if (!$_REQUEST["billing-country"]) {
                $message = "Billing Address Country is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
            if (!$_REQUEST["billing-phone"]) {
                $message = "Billing Address Phone is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
        }

        // check shipping address
        if ($require_shipping_address && !$billing_is_shipping) {
            // require fields if(require-shipping-address=true && billing-is-shipping=false)
            if (!$_REQUEST["shipping-first-name"]) {
                $message = "Shipping Address First Name is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
            if (!$_REQUEST["shipping-last-name"]) {
                $message = "Shipping Address Last Name is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
            if (!$_REQUEST["shipping-street"]) {
                $message = "Shipping Address Street is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
            if (!$_REQUEST["shipping-city"]) {
                $message = "Shipping Address City is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
            if (!$_REQUEST["shipping-state"]) {
                $message = "Shipping Address State is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
            if (!$_REQUEST["shipping-zip"]) {
                $message = "Shipping Address Zip is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
            if (!$_REQUEST["shipping-country"]) {
                $message = "Shipping Address Country is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
            if (!$_REQUEST["shipping-phone"]) {
                $message = "Shipping Address Phone is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
        }

        if ($require_email_address) {
            // require fields if(require_email_address) is true
            if (!$_REQUEST["email-address"]) {
                $message = "Email is required.";
                $this->clearent_util->telemed_logger($message);
                $response["error"] = $response["error"] . $message . "<br>";
                $has_errors = true;
            }
        }

        if ($has_errors) {
            $this->clearent_util->telemed_logger("response=" . json_encode($response));
            echo json_encode($response);
        } else {
            $this->telemed_send();
        }

    }

    public function telemed_send()
    {
        //session_start();

        $this->clearent_util->telemed_logger("beginning send function");
        $options = get_option($this->option_name);

        $payment_data = array();

        if ($_SESSION["test"]) {
            $this->clearent_util->telemed_logger("PLUGIN IS RUNNING IN TEST MODE");
            $url = telemed_clearent::TESTING_API_URL;
            $payment_data["api-key"] = $options["sb_api_key"];
            $_SESSION["clearent.environment"] = "sandbox";
        } elseif ($options["environment"] == "sandbox") {
            $this->clearent_util->telemed_logger("PLUGIN IS RUNNING IN SANDBOX MODE");
            $url = telemed_clearent::SANDBOX_API_URL;
            $payment_data["api-key"] = $options["sb_api_key"];
            $_SESSION["clearent.environment"] = "sandbox";
        } else {
            $this->clearent_util->telemed_logger("PLUGIN IS RUNNING IN PRODUCTION MODE");
            $url = telemed_clearent::PRODUCTION_API_URL;
            $payment_data["api-key"] = $options["prod_api_key"];
            $_SESSION["clearent.environment"] = "production";
        }

        // transaction data
        $payment_data["type"] = "SALE";
        $payment_data["software-type"] = "wordpress";
        $payment_data["software-type-version"] = PLUGIN_VERSION;
        $payment_data["g-recaptcha-response"] = sanitize_text_field($_REQUEST["g-recaptcha-response"]);

        if (!isset($_REQUEST["amount"])) {
            $amount = sanitize_text_field($_SESSION["clearent.amount"]);
        } else {
            $amount = sanitize_text_field($_REQUEST["amount"]);
        }
        $payment_data["amount"] = $amount;

        if (isset($_SESSION["clearent.sales-tax-amount"])) {
            $payment_data["sales-tax-amount"] = sanitize_text_field($_SESSION["clearent.sales-tax-amount"]);
            $payment_data["sales-tax-type"] = "LOCAL_SALES_TAX";
        }
        $payment_data["card"] = preg_replace("/[^0-9]/", "", sanitize_text_field($_REQUEST["card"]));
        $payment_data["exp-date"] = sanitize_text_field($_REQUEST["expire-date-month"]) . sanitize_text_field($_REQUEST["expire-date-year"]);
        $payment_data["csc"] = sanitize_text_field($_REQUEST["csc"]);

        // transaction metadata
        $payment_data["invoice"] = sanitize_text_field($_REQUEST["invoice"]);
        $payment_data["purchase-order"] = sanitize_text_field($_REQUEST["purchase-order"]);
        $email = sanitize_text_field($_REQUEST["email-address"]);
        $payment_data["email-address"] = is_email($email)? $email : "";
        $payment_data["customer-id"] = sanitize_text_field($_REQUEST["customer-id"]);
        $payment_data["order-id"] = sanitize_text_field($_REQUEST["order-id"]);
        $payment_data["client-ip"] = $this->telemed_getRealIpAddr();
        $payment_data["description"] = sanitize_text_field($_REQUEST["description"]);
        $payment_data["comments"] = sanitize_text_field($_REQUEST["comments"]);

        $billing = array(
            "first-name" => sanitize_text_field($_REQUEST["billing-first-name"]),
            "last-name" => sanitize_text_field($_REQUEST["billing-last-name"]),
            "company" => sanitize_text_field($_REQUEST["billing-company"]),
            "street" => sanitize_text_field($_REQUEST["billing-street"]),
            "street2" => sanitize_text_field($_REQUEST["billing-street2"]),
            "city" => sanitize_text_field($_REQUEST["billing-city"]),
            "state" => sanitize_text_field($_REQUEST["billing-state"]),
            "zip" => sanitize_text_field($_REQUEST["billing-zip"]),
            "country" => sanitize_text_field($_REQUEST["billing-country"]),
            "phone" => sanitize_text_field($_REQUEST["billing-phone"]),
        );
        $payment_data["billing"] = $billing;

        if (isset($_REQUEST["billing-is-shipping"]) && $_REQUEST["billing-is-shipping"] == "true") {
            $this->clearent_util->telemed_logger("HasShipping is false");
            $payment_data["billing-is-shipping"] = "true";
        } else {
            $this->clearent_util->telemed_logger("HasShipping is true");
            $payment_data["billing-is-shipping"] = "false";
            $shipping = array(
                "first-name" => sanitize_text_field($_REQUEST["shipping-first-name"]),
                "last-name" => sanitize_text_field($_REQUEST["shipping-last-name"]),
                "company" => sanitize_text_field($_REQUEST["shipping-company"]),
                "street" => sanitize_text_field($_REQUEST["shipping-street"]),
                "street2" => sanitize_text_field($_REQUEST["shipping-street2"]),
                "city" => sanitize_text_field($_REQUEST["shipping-city"]),
                "state" => sanitize_text_field($_REQUEST["shipping-state"]),
                "zip" => sanitize_text_field($_REQUEST["shipping-zip"]),
                "country" => sanitize_text_field($_REQUEST["shipping-country"]),
                "phone" => sanitize_text_field($_REQUEST["shipping-phone"]),
            );
            $payment_data["shipping"] = $shipping;
        }

        $this->clearent_util->telemed_logger("-------------------- begin payment_data --------------------");
        $this->clearent_util->telemed_logger($payment_data);
        $this->clearent_util->telemed_logger("--------------------- end payment_data ---------------------");

        $db_response_data = $this->clearent_util->telemed_sendPayment($url, $payment_data);
        $responseDataAsJSON = json_decode($db_response_data);

        $this->clearent_util->telemed_logger($db_response_data);

        $response = array();

        // 1 - Put together a debug log message that is logged when debug logging is turned on
        if (isset($responseDataAsJSON->payload->transaction) && isset($responseDataAsJSON->payload->transaction->{"display-message"})) {
            $db_result_code = $responseDataAsJSON->payload->transaction->{"result-code"};
            $db_display_message = $responseDataAsJSON->payload->transaction->{"display-message"};
        } else {
            $db_result_code = $responseDataAsJSON->payload->error->{"result-code"};
            $db_display_message = $responseDataAsJSON->payload->error->{"error-message"};
        }
        $message = "";
        $message .= "Result:" . $responseDataAsJSON->payload->transaction->result . "; ";
        $message .= "Status:" . $db_result_code . " - " . $db_display_message . "; ";
        $message .= "Exchange ID:" . $responseDataAsJSON->{"exchange-id"} . "; ";
        $message .= "Transaction ID:" . $responseDataAsJSON->payload->transaction->id . "; ";
        $message .= "Authorization Code:" . $responseDataAsJSON->payload->transaction->{"authorization-code"} . "; ";
        $message .= "Amount:" . $responseDataAsJSON->payload->transaction->amount . "; ";
        $message .= "Card:" . $responseDataAsJSON->payload->transaction->card . "; ";
        $message .= "Expiration Date:" . $responseDataAsJSON->payload->transaction->{"exp-date"};
        $this->clearent_util->telemed_logger($message);

        // 2 - log order details in database
        $table_name = "telemed_transaction";
        $db_record_date = current_time("mysql", 0);
        $db_id = date("YmdHis") . "_" . rand(1111111, 9999999);

        if (isset($responseDataAsJSON->payload->transaction->type)) {
            $db_type = $responseDataAsJSON->payload->transaction->{"type"};
        } else {
            $db_type = $payment_data["type"];
        }

        if (isset($responseDataAsJSON->payload->transaction->amount)) {
            $db_amount = $responseDataAsJSON->payload->transaction->amount;
        } else {
            $db_amount = $amount;
        }

        if (isset($responseDataAsJSON->payload->transaction->{"sales-tax-amount"})) {
            $db_sales_tax_amount = $responseDataAsJSON->payload->transaction->{"sales-tax-amount"};
        } else {
            $db_sales_tax_amount = null;
        }

        if (isset($responseDataAsJSON->payload->transaction->card)) {
            $db_card = $responseDataAsJSON->payload->transaction->card;
        } else {
            $db_card = substr($payment_data["card"], -4);
        }

        if (isset($responseDataAsJSON->payload->transaction->{"exp-date"})) {
            $db_exp_date = $responseDataAsJSON->payload->transaction->{"exp-date"};
        } else {
            $db_exp_date = $payment_data["exp-date"];
        }

        if (isset($responseDataAsJSON->payload->transaction->{"result"})) {
            $db_result = $responseDataAsJSON->payload->transaction->{"result"};
        } else {
            $db_result = $responseDataAsJSON->status;
        }

        $values = array(
            "id" => $db_id,
            "environment" => $_SESSION["clearent.environment"],
            "transaction_type" => $db_type,
            "amount" => $db_amount,
            "sales_tax_amount" => $db_sales_tax_amount,
            "card" => $db_card,
            "exp_date" => $db_exp_date,
            "invoice" => $responseDataAsJSON->payload->transaction->{"invoice"},
            "purchase_order" => $responseDataAsJSON->payload->transaction->{"purchase-order"},
            "email_address" => $responseDataAsJSON->payload->transaction->{"email-address"},
            "customer_id" => $responseDataAsJSON->payload->transaction->{"customer-id"},
            "order_id" => $responseDataAsJSON->payload->transaction->{"order-id"},
            "description" => $responseDataAsJSON->payload->transaction->{"description"},
            "comments" => $responseDataAsJSON->payload->transaction->{"comments"},
            "billing_firstname" => $responseDataAsJSON->payload->transaction->billing->{"first-name"},
            "billing_lastname" => $responseDataAsJSON->payload->transaction->billing->{"last-name"},
            "billing_company" => $responseDataAsJSON->payload->transaction->billing->{"company"},
            "billing_street" => $responseDataAsJSON->payload->transaction->billing->{"street"},
            "billing_street2" => $responseDataAsJSON->payload->transaction->billing->{"street2"},
            "billing_city" => $responseDataAsJSON->payload->transaction->billing->{"city"},
            "billing_state" => $responseDataAsJSON->payload->transaction->billing->{"state"},
            "billing_zip" => $responseDataAsJSON->payload->transaction->billing->{"zip"},
            "billing_country" => $responseDataAsJSON->payload->transaction->billing->{"country"},
            "billing_phone" => $responseDataAsJSON->payload->transaction->billing->{"phone"},
            "billing_is_shipping" => $payment_data["billing-is-shipping"],
            "shipping_firstname" => $responseDataAsJSON->payload->transaction->shipping->{"first-name"},
            "shipping_lastname" => $responseDataAsJSON->payload->transaction->shipping->{"last-name"},
            "shipping_company" => $responseDataAsJSON->payload->transaction->shipping->{"company"},
            "shipping_street" => $responseDataAsJSON->payload->transaction->shipping->{"street"},
            "shipping_street2" => $responseDataAsJSON->payload->transaction->shipping->{"street2"},
            "shipping_city" => $responseDataAsJSON->payload->transaction->shipping->{"city"},
            "shipping_state" => $responseDataAsJSON->payload->transaction->shipping->{"state"},
            "shipping_zip" => $responseDataAsJSON->payload->transaction->shipping->{"zip"},
            "shipping_country" => $responseDataAsJSON->payload->transaction->shipping->{"country"},
            "shipping_phone" => $responseDataAsJSON->payload->transaction->shipping->{"phone"},
            "client_ip" => $this->telemed_getRealIpAddr(),
            "transaction_id" => $responseDataAsJSON->payload->transaction->id,
            "authorization_code" => $responseDataAsJSON->payload->transaction->{"authorization-code"},
            "result" => $db_result,
            "result_code" => $db_result_code,
            "exchange_id" => $responseDataAsJSON->{"exchange-id"},
            "display_message" => $db_display_message,
            "response_raw" => $db_response_data,
            "user_agent" => $_SERVER["HTTP_USER_AGENT"],
            "date_added" => $db_record_date,
            "date_modified" => $db_record_date,
        );

        $this->clearent_util->telemed_add_record($table_name, $values);

        if ($responseDataAsJSON->{"code"} == "200") {
            // 3a - add success redirect url to response
            $success_url = $options["success_url"];
            //Todo: Add customer email
            require_once (__DIR__.'/../admin/telemed_transaction_email.php');
            $transaction_email = new telemed_transaction_email($responseDataAsJSON, $db_record_date);
            $transaction_email->sendHtmlCustomerEmail();

            if ($success_url == "-1") {
                $response["redirect"] = get_home_url();
            } else {
                $response["redirect"] = get_permalink($success_url);
            }
        } else {
            // 3b - add error to response
            $response["error"] = "We were unable to process your payment. Please verify your card details and try again or contact us to complete your order.";
        }
        echo json_encode($response);

    }

    private function telemed_getCaptchaPublicKey()
    {
        $options = get_option($this->option_name);
        if ($options["environment"] == "sandbox") {
            return "6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI";
        } else {
            return "6LcgVRwUAAAAABeB_ioEneNky4ucz5X5eYjwWRzf";
        }
    }

    private function getStatesForSelect()
    {
        $state_select_values = <<<EOF
    <option value="AL">Alabama</option>
	<option value="AK">Alaska</option>
	<option value="AZ">Arizona</option>
	<option value="AR">Arkansas</option>
	<option value="CA">California</option>
	<option value="CO">Colorado</option>
	<option value="CT">Connecticut</option>
	<option value="DE">Delaware</option>
	<option value="DC">District of Columbia</option>
	<option value="FL">Florida</option>
	<option value="GA">Georgia</option>
	<option value="HI">Hawaii</option>
	<option value="ID">Idaho</option>
	<option value="IL">Illinois</option>
	<option value="IN">Indiana</option>
	<option value="IA">Iowa</option>
	<option value="KS">Kansas</option>
	<option value="KY">Kentucky</option>
	<option value="LA">Louisiana</option>
	<option value="ME">Maine</option>
	<option value="MD">Maryland</option>
	<option value="MA">Massachusetts</option>
	<option value="MI">Michigan</option>
	<option value="MN">Minnesota</option>
	<option value="MS">Mississippi</option>
	<option value="MO">Missouri</option>
	<option value="MT">Montana</option>
	<option value="NE">Nebraska</option>
	<option value="NV">Nevada</option>
	<option value="NH">New Hampshire</option>
	<option value="NJ">New Jersey</option>
	<option value="NM">New Mexico</option>
	<option value="NY">New York</option>
	<option value="NC">North Carolina</option>
	<option value="ND">North Dakota</option>
	<option value="OH">Ohio</option>
	<option value="OK">Oklahoma</option>
	<option value="OR">Oregon</option>
	<option value="PA">Pennsylvania</option>
	<option value="RI">Rhode Island</option>
	<option value="SC">South Carolina</option>
	<option value="SD">South Dakota</option>
	<option value="TN">Tennessee</option>
	<option value="TX">Texas</option>
	<option value="UT">Utah</option>
	<option value="VT">Vermont</option>
	<option value="VA">Virginia</option>
	<option value="WA">Washington</option>
	<option value="WV">West Virginia</option>
	<option value="WI">Wisconsin</option>
	<option value="WY">Wyoming</option>
EOF;
        return $state_select_values;
    }

    private function getCountriesForSelect() {
        $countries_for_select = <<<EOF
    <option value="Afghanistan">Afghanistan</option>
    <option value="Albania">Albania</option>
    <option value="Algeria">Algeria</option>
    <option value="American Samoa">American Samoa</option>
    <option value="Andorra">Andorra</option>
    <option value="Angola">Angola</option>
    <option value="Anguilla">Anguilla</option>
    <option value="Antartica">Antarctica</option>
    <option value="Antigua and Barbuda">Antigua and Barbuda</option>
    <option value="Argentina">Argentina</option>
    <option value="Armenia">Armenia</option>
    <option value="Aruba">Aruba</option>
    <option value="Australia">Australia</option>
    <option value="Austria">Austria</option>
    <option value="Azerbaijan">Azerbaijan</option>
    <option value="Bahamas">Bahamas</option>
    <option value="Bahrain">Bahrain</option>
    <option value="Bangladesh">Bangladesh</option>
    <option value="Barbados">Barbados</option>
    <option value="Belarus">Belarus</option>
    <option value="Belgium">Belgium</option>
    <option value="Belize">Belize</option>
    <option value="Benin">Benin</option>
    <option value="Bermuda">Bermuda</option>
    <option value="Bhutan">Bhutan</option>
    <option value="Bolivia">Bolivia</option>
    <option value="Bosnia and Herzegowina">Bosnia and Herzegowina</option>
    <option value="Botswana">Botswana</option>
    <option value="Bouvet Island">Bouvet Island</option>
    <option value="Brazil">Brazil</option>
    <option value="British Indian Ocean Territory">British Indian Ocean Territory</option>
    <option value="Brunei Darussalam">Brunei Darussalam</option>
    <option value="Bulgaria">Bulgaria</option>
    <option value="Burkina Faso">Burkina Faso</option>
    <option value="Burundi">Burundi</option>
    <option value="Cambodia">Cambodia</option>
    <option value="Cameroon">Cameroon</option>
    <option value="Canada">Canada</option>
    <option value="Cape Verde">Cape Verde</option>
    <option value="Cayman Islands">Cayman Islands</option>
    <option value="Central African Republic">Central African Republic</option>
    <option value="Chad">Chad</option>
    <option value="Chile">Chile</option>
    <option value="China">China</option>
    <option value="Christmas Island">Christmas Island</option>
    <option value="Cocos Islands">Cocos (Keeling) Islands</option>
    <option value="Colombia">Colombia</option>
    <option value="Comoros">Comoros</option>
    <option value="Congo">Congo</option>
    <option value="Congo">Congo, the Democratic Republic of the</option>
    <option value="Cook Islands">Cook Islands</option>
    <option value="Costa Rica">Costa Rica</option>
    <option value="Cota D'Ivoire">Cote d'Ivoire</option>
    <option value="Croatia">Croatia (Hrvatska)</option>
    <option value="Cuba">Cuba</option>
    <option value="Cyprus">Cyprus</option>
    <option value="Czech Republic">Czech Republic</option>
    <option value="Denmark">Denmark</option>
    <option value="Djibouti">Djibouti</option>
    <option value="Dominica">Dominica</option>
    <option value="Dominican Republic">Dominican Republic</option>
    <option value="East Timor">East Timor</option>
    <option value="Ecuador">Ecuador</option>
    <option value="Egypt">Egypt</option>
    <option value="El Salvador">El Salvador</option>
    <option value="Equatorial Guinea">Equatorial Guinea</option>
    <option value="Eritrea">Eritrea</option>
    <option value="Estonia">Estonia</option>
    <option value="Ethiopia">Ethiopia</option>
    <option value="Falkland Islands">Falkland Islands (Malvinas)</option>
    <option value="Faroe Islands">Faroe Islands</option>
    <option value="Fiji">Fiji</option>
    <option value="Finland">Finland</option>
    <option value="France">France</option>
    <option value="France Metropolitan">France, Metropolitan</option>
    <option value="French Guiana">French Guiana</option>
    <option value="French Polynesia">French Polynesia</option>
    <option value="French Southern Territories">French Southern Territories</option>
    <option value="Gabon">Gabon</option>
    <option value="Gambia">Gambia</option>
    <option value="Georgia">Georgia</option>
    <option value="Germany">Germany</option>
    <option value="Ghana">Ghana</option>
    <option value="Gibraltar">Gibraltar</option>
    <option value="Greece">Greece</option>
    <option value="Greenland">Greenland</option>
    <option value="Grenada">Grenada</option>
    <option value="Guadeloupe">Guadeloupe</option>
    <option value="Guam">Guam</option>
    <option value="Guatemala">Guatemala</option>
    <option value="Guinea">Guinea</option>
    <option value="Guinea-Bissau">Guinea-Bissau</option>
    <option value="Guyana">Guyana</option>
    <option value="Haiti">Haiti</option>
    <option value="Heard and McDonald Islands">Heard and Mc Donald Islands</option>
    <option value="Holy See">Holy See (Vatican City State)</option>
    <option value="Honduras">Honduras</option>
    <option value="Hong Kong">Hong Kong</option>
    <option value="Hungary">Hungary</option>
    <option value="Iceland">Iceland</option>
    <option value="India">India</option>
    <option value="Indonesia">Indonesia</option>
    <option value="Iran">Iran (Islamic Republic of)</option>
    <option value="Iraq">Iraq</option>
    <option value="Ireland">Ireland</option>
    <option value="Israel">Israel</option>
    <option value="Italy">Italy</option>
    <option value="Jamaica">Jamaica</option>
    <option value="Japan">Japan</option>
    <option value="Jordan">Jordan</option>
    <option value="Kazakhstan">Kazakhstan</option>
    <option value="Kenya">Kenya</option>
    <option value="Kiribati">Kiribati</option>
    <option value="Democratic People's Republic of Korea">Korea, Democratic People's Republic of</option>
    <option value="Korea">Korea, Republic of</option>
    <option value="Kuwait">Kuwait</option>
    <option value="Kyrgyzstan">Kyrgyzstan</option>
    <option value="Lao">Lao People's Democratic Republic</option>
    <option value="Latvia">Latvia</option>
    <option value="Lebanon">Lebanon</option>
    <option value="Lesotho">Lesotho</option>
    <option value="Liberia">Liberia</option>
    <option value="Libyan Arab Jamahiriya">Libyan Arab Jamahiriya</option>
    <option value="Liechtenstein">Liechtenstein</option>
    <option value="Lithuania">Lithuania</option>
    <option value="Luxembourg">Luxembourg</option>
    <option value="Macau">Macau</option>
    <option value="Macedonia">Macedonia, The Former Yugoslav Republic of</option>
    <option value="Madagascar">Madagascar</option>
    <option value="Malawi">Malawi</option>
    <option value="Malaysia">Malaysia</option>
    <option value="Maldives">Maldives</option>
    <option value="Mali">Mali</option>
    <option value="Malta">Malta</option>
    <option value="Marshall Islands">Marshall Islands</option>
    <option value="Martinique">Martinique</option>
    <option value="Mauritania">Mauritania</option>
    <option value="Mauritius">Mauritius</option>
    <option value="Mayotte">Mayotte</option>
    <option value="Mexico">Mexico</option>
    <option value="Micronesia">Micronesia, Federated States of</option>
    <option value="Moldova">Moldova, Republic of</option>
    <option value="Monaco">Monaco</option>
    <option value="Mongolia">Mongolia</option>
    <option value="Montserrat">Montserrat</option>
    <option value="Morocco">Morocco</option>
    <option value="Mozambique">Mozambique</option>
    <option value="Myanmar">Myanmar</option>
    <option value="Namibia">Namibia</option>
    <option value="Nauru">Nauru</option>
    <option value="Nepal">Nepal</option>
    <option value="Netherlands">Netherlands</option>
    <option value="Netherlands Antilles">Netherlands Antilles</option>
    <option value="New Caledonia">New Caledonia</option>
    <option value="New Zealand">New Zealand</option>
    <option value="Nicaragua">Nicaragua</option>
    <option value="Niger">Niger</option>
    <option value="Nigeria">Nigeria</option>
    <option value="Niue">Niue</option>
    <option value="Norfolk Island">Norfolk Island</option>
    <option value="Northern Mariana Islands">Northern Mariana Islands</option>
    <option value="Norway">Norway</option>
    <option value="Oman">Oman</option>
    <option value="Pakistan">Pakistan</option>
    <option value="Palau">Palau</option>
    <option value="Panama">Panama</option>
    <option value="Papua New Guinea">Papua New Guinea</option>
    <option value="Paraguay">Paraguay</option>
    <option value="Peru">Peru</option>
    <option value="Philippines">Philippines</option>
    <option value="Pitcairn">Pitcairn</option>
    <option value="Poland">Poland</option>
    <option value="Portugal">Portugal</option>
    <option value="Puerto Rico">Puerto Rico</option>
    <option value="Qatar">Qatar</option>
    <option value="Reunion">Reunion</option>
    <option value="Romania">Romania</option>
    <option value="Russia">Russian Federation</option>
    <option value="Rwanda">Rwanda</option>
    <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option> 
    <option value="Saint LUCIA">Saint LUCIA</option>
    <option value="Saint Vincent">Saint Vincent and the Grenadines</option>
    <option value="Samoa">Samoa</option>
    <option value="San Marino">San Marino</option>
    <option value="Sao Tome and Principe">Sao Tome and Principe</option> 
    <option value="Saudi Arabia">Saudi Arabia</option>
    <option value="Senegal">Senegal</option>
    <option value="Seychelles">Seychelles</option>
    <option value="Sierra">Sierra Leone</option>
    <option value="Singapore">Singapore</option>
    <option value="Slovakia">Slovakia (Slovak Republic)</option>
    <option value="Slovenia">Slovenia</option>
    <option value="Solomon Islands">Solomon Islands</option>
    <option value="Somalia">Somalia</option>
    <option value="South Africa">South Africa</option>
    <option value="South Georgia">South Georgia and the South Sandwich Islands</option>
    <option value="Span">Spain</option>
    <option value="SriLanka">Sri Lanka</option>
    <option value="St. Helena">St. Helena</option>
    <option value="St. Pierre and Miguelon">St. Pierre and Miquelon</option>
    <option value="Sudan">Sudan</option>
    <option value="Suriname">Suriname</option>
    <option value="Svalbard">Svalbard and Jan Mayen Islands</option>
    <option value="Swaziland">Swaziland</option>
    <option value="Sweden">Sweden</option>
    <option value="Switzerland">Switzerland</option>
    <option value="Syria">Syrian Arab Republic</option>
    <option value="Taiwan">Taiwan, Province of China</option>
    <option value="Tajikistan">Tajikistan</option>
    <option value="Tanzania">Tanzania, United Republic of</option>
    <option value="Thailand">Thailand</option>
    <option value="Togo">Togo</option>
    <option value="Tokelau">Tokelau</option>
    <option value="Tonga">Tonga</option>
    <option value="Trinidad and Tobago">Trinidad and Tobago</option>
    <option value="Tunisia">Tunisia</option>
    <option value="Turkey">Turkey</option>
    <option value="Turkmenistan">Turkmenistan</option>
    <option value="Turks and Caicos">Turks and Caicos Islands</option>
    <option value="Tuvalu">Tuvalu</option>
    <option value="Uganda">Uganda</option>
    <option value="Ukraine">Ukraine</option>
    <option value="United Arab Emirates">United Arab Emirates</option>
    <option value="United Kingdom">United Kingdom</option>
    <option value="United States" selected>United States</option>
    <option value="United States Minor Outlying Islands">United States Minor Outlying Islands</option>
    <option value="Uruguay">Uruguay</option>
    <option value="Uzbekistan">Uzbekistan</option>
    <option value="Vanuatu">Vanuatu</option>
    <option value="Venezuela">Venezuela</option>
    <option value="Vietnam">Viet Nam</option>
    <option value="Virgin Islands (British)">Virgin Islands (British)</option>
    <option value="Virgin Islands (U.S)">Virgin Islands (U.S.)</option>
    <option value="Wallis and Futana Islands">Wallis and Futuna Islands</option>
    <option value="Western Sahara">Western Sahara</option>
    <option value="Yemen">Yemen</option>
    <option value="Yugoslavia">Yugoslavia</option>
    <option value="Zambia">Zambia</option>
    <option value="Zimbabwe">Zimbabwe</option>
EOF;
        return $countries_for_select;
    }

}

?>