<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Created by PhpStorm.
 * User: BWoods
 * Date: 7/6/2017
 * Time: 11:13 AM
 */
class telemed_transaction_email
{
    protected $option_name = 'clearent_opts';

    private $email;
    private $result;
    private $status;
    private $amount;
    private $card;
    private $expiration_date;
    private $invoice;
    private $purchase_order;
    private $order_id;
    private $description;
    private $comments;
    private $date_added;

    private $billing_fullname;
    private $billing_firstname;
    private $billing_lastname;
    private $billing_company;
    private $billing_street;
    private $billing_street2;
    private $billing_city;
    private $billing_state;
    private $billing_zip;
    private $billing_country;
    private $billing_phone;

    private $shipping_fullname;
    private $shipping_firstname;
    private $shipping_lastname;
    private $shipping_company;
    private $shipping_street;
    private $shipping_street2;
    private $shipping_city;
    private $shipping_state;
    private $shipping_zip;
    private $shipping_country;
    private $shipping_phone;

    public function __construct($jsonResponseData, $record_date) {
        $this->setEmail($jsonResponseData->payload->transaction->{"email-address"});
        $this->setResult($jsonResponseData->payload->transaction->result);
        $this->setStatus($jsonResponseData->payload->transaction);
        $this->setAmount($jsonResponseData->payload->transaction->amount);
        $this->setCard($jsonResponseData->payload->transaction->card);
        $this->setExpirationDate($jsonResponseData->payload->transaction->{"exp-date"});
        $this->setInvoice($jsonResponseData->payload->transaction->{"invoice"});
        $this->setPurchaseOrder($jsonResponseData->payload->transaction->{"purchase-order"});
        $this->setOrderId($jsonResponseData->payload->transaction->{"order-id"});
        $this->setDescription($jsonResponseData->payload->transaction->{"description"});
        $this->setComments($jsonResponseData->payload->transaction->{"comments"});
        $this->setDateAdded($record_date);

        $this->setBillingFirstname($jsonResponseData->payload->transaction->billing->{"first-name"});
        $this->setBillingLastname($jsonResponseData->payload->transaction->billing->{"last-name"});
        $this->setBillingCompany($jsonResponseData->payload->transaction->billing->{"company"});
        $this->setBillingStreet($jsonResponseData->payload->transaction->billing->{"street"});
        $this->setBillingStreet2($jsonResponseData->payload->transaction->billing->{"street2"});
        $this->setBillingCity($jsonResponseData->payload->transaction->billing->{"city"});
        $this->setBillingState($jsonResponseData->payload->transaction->billing->{"state"});
        $this->setBillingZip($jsonResponseData->payload->transaction->billing->{"zip"});
        $this->setBillingCountry($jsonResponseData->payload->transaction->billing->{"country"});
        $this->setBillingPhone($jsonResponseData->payload->transaction->billing->{"phone"});

        $this->setShippingFirstname($jsonResponseData->payload->transaction->shipping->{"first-name"});
        $this->setShippingLastname($jsonResponseData->payload->transaction->shipping->{"last-name"});
        $this->setShippingCompany($jsonResponseData->payload->transaction->shipping->{"company"});
        $this->setShippingStreet($jsonResponseData->payload->transaction->shipping->{"street"});
        $this->setShippingStreet2($jsonResponseData->payload->transaction->shipping->{"street2"});
        $this->setShippingCity($jsonResponseData->payload->transaction->shipping->{"city"});
        $this->setShippingState($jsonResponseData->payload->transaction->shipping->{"state"});
        $this->setShippingZip($jsonResponseData->payload->transaction->shipping->{"zip"});
        $this->setShippingCountry($jsonResponseData->payload->transaction->shipping->{"country"});
        $this->setShippingPhone($jsonResponseData->payload->transaction->shipping->{"phone"});

        if ($this->getBillingFirstname() != null && $this->getBillingLastname() != null){
            $this->setBillingFullname();
        }

        if ($this->getShippingFirstname() != null && $this->getShippingLastname() != null){
            $this->setShippingFullname();
        }
    }

    public function sendPlainCustomerEmail(){

        $options = get_option($this->option_name);

        if ($this->getEmail() && strlen($this->getEmail()) > 0) {
            $replace = array(
                '{company_name}',
                '{charge_from_name}',
                '{email_address}',
                '{billing_name}',
                '{billing_street}',
                '{billing_street2}',
                '{billing_city}',
                '{billing_state}',
                '{billing_zip}',
                '{billing_country}',
                '{shipping_name}',
                '{shipping_street}',
                '{shipping_street2}',
                '{shipping_city}',
                '{shipping_state}',
                '{shipping_zip}',
                '{shipping_country}',
                '{amount}',
                '{created_date}'
            );
            $with = array(
                isset($options["email_company_name"])? $options["email_company_name"]: "",
                isset($options["email_charge_from"])? $options["email_charge_from"]: "",
                $this->getEmail(),
                $this->getBillingFullname(),
                $this->getBillingStreet(),
                $this->getBillingStreet2(),
                $this->getBillingCity(),
                $this->getBillingState(),
                $this->getBillingZip(),
                $this->getBillingCountry(),
                $this->getShippingFullname(),
                $this->getShippingStreet(),
                $this->getShippingStreet2(),
                $this->getShippingCity(),
                $this->getShippingState(),
                $this->getShippingZip(),
                $this->getShippingCountry(),
                $this->getAmount(),
                $this->getDateAdded()

            );

            ob_start();
            include(__DIR__ . '/../templates/email/customer_email_plain.tpl');
            $ob = ob_get_clean();

            $to = $this->getEmail();
            $subject = isset($options["notification_email_subject"])? $options["notification_email_subject"]: "Payment Info";

            if (isset($options["notification_email"])) {
                $headers[] = "Bcc:".$options["notification_email"];
            }

            if (isset($options["notification_email_from_name"]) && isset($options["notification_email_from"])) {
                $headers[] = "From: " . $options["notification_email_from_name"] . " <" . $options["notification_email_from"] . ">";
            }

            $message = str_replace($replace, $with, $ob);

            isset($headers)? wp_mail($to, $subject, $message, $headers) : wp_mail($to, $subject, $message);
        }
    }

    public function sendHtmlCustomerEmail(){

        $options = get_option($this->option_name);

        if ($this->getEmail() && strlen($this->getEmail()) > 0) {
            $replace = array(
                '{company_name}',
                '{charge_from_name}',
                '{email_address}',
                '{billing_name}',
                '{billing_street}',
                '{billing_street2}',
                '{billing_city}',
                '{billing_state}',
                '{billing_zip}',
                '{billing_country}',
                '{shipping_name}',
                '{shipping_street}',
                '{shipping_street2}',
                '{shipping_city}',
                '{shipping_state}',
                '{shipping_zip}',
                '{shipping_country}',
                '{amount}',
                '{created_date}'
            );
            $with = array(
                isset($options["email_company_name"])? $options["email_company_name"]: "",
                isset($options["email_charge_from"])? $options["email_charge_from"]: "",
                $this->getEmail(),
                $this->getBillingFullname(),
                $this->getBillingStreet(),
                $this->getBillingStreet2(),
                $this->getBillingCity(),
                $this->getBillingState(),
                $this->getBillingZip(),
                $this->getBillingCountry(),
                $this->getShippingFullname(),
                $this->getShippingStreet(),
                $this->getShippingStreet2(),
                $this->getShippingCity(),
                $this->getShippingState(),
                $this->getShippingZip(),
                $this->getShippingCountry(),
                $this->getAmount(),
                $this->getDateAdded()

            );

            ob_start();
            include(__DIR__ . '/../templates/email/customer_email_html.tpl');
            $ob = ob_get_clean();

            $to = $this->getEmail();
            $subject = isset($options["notification_email_subject"])? $options["notification_email_subject"]: "Payment Info";

            $headers[] = "Content-Type: text/html; charset=UTF-8";
            if (isset($options["notification_email"])) {
                $headers[] = "Bcc:".$options["notification_email"];
            }

            if (isset($options["notification_email_from_name"]) && isset($options["notification_email_from"])) {
                $headers[] = "From: " . $options["notification_email_from_name"] . " <" . $options["notification_email_from"] . ">";
            }

            $message = str_replace($replace, $with, $ob);

            isset($headers)? wp_mail($to, $subject, $message, $headers) : wp_mail($to, $subject, $message);
        }
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status->{'result-code'} . " - " . $status->{'display-message'};
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->amount = '$'. $amount;
    }

    /**
     * @return mixed
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * @param mixed $card
     */
    public function setCard($card)
    {
        $this->card = $card;
    }

    /**
     * @return mixed
     */
    public function getExpirationDate()
    {
        return $this->expiration_date;
    }

    /**
     * @param mixed $expiration_date
     */
    public function setExpirationDate($expiration_date)
    {
        $this->expiration_date = $expiration_date;
    }

    /**
     * @return mixed
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @param mixed $invoice
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * @return mixed
     */
    public function getPurchaseOrder()
    {
        return $this->purchase_order;
    }

    /**
     * @param mixed $purchase_order
     */
    public function setPurchaseOrder($purchase_order)
    {
        $this->purchase_order = $purchase_order;
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @param mixed $order_id
     */
    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param mixed $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->date_added;
    }

    /**
     * @param mixed $date_added
     */
    public function setDateAdded($date_added)
    {
        $this->date_added = $date_added;
    }

    /**
     * @return mixed
     */
    public function getBillingFullname()
    {
        return $this->billing_fullname;
    }

    /**
     * @param mixed $billing_fullname
     */
    public function setBillingFullname()
    {
        $billing_fullname = $this->getBillingFirstname() . ' ' . $this->getBillingLastname();
        $this->billing_fullname = $billing_fullname;
    }

    /**
     * @return mixed
     */
    public function getBillingFirstname()
    {
        return $this->billing_firstname;
    }

    /**
     * @param mixed $billing_firstname
     */
    public function setBillingFirstname($billing_firstname)
    {
        $this->billing_firstname = $billing_firstname;
    }

    /**
     * @return mixed
     */
    public function getBillingLastname()
    {
        return $this->billing_lastname;
    }

    /**
     * @param mixed $billing_lastname
     */
    public function setBillingLastname($billing_lastname)
    {
        $this->billing_lastname = $billing_lastname;
    }

    /**
     * @return mixed
     */
    public function getBillingCompany()
    {
        return $this->billing_company;
    }

    /**
     * @param mixed $billing_company
     */
    public function setBillingCompany($billing_company)
    {
        $this->billing_company = $billing_company;
    }

    /**
     * @return mixed
     */
    public function getBillingStreet()
    {
        return $this->billing_street;
    }

    /**
     * @param mixed $billing_street
     */
    public function setBillingStreet($billing_street)
    {
        $this->billing_street = $billing_street;
    }

    /**
     * @return mixed
     */
    public function getBillingStreet2()
    {
        return $this->billing_street2;
    }

    /**
     * @param mixed $billing_street2
     */
    public function setBillingStreet2($billing_street2)
    {
        $this->billing_street2 = $billing_street2;
    }

    /**
     * @return mixed
     */
    public function getBillingCity()
    {
        return $this->billing_city;
    }

    /**
     * @param mixed $billing_city
     */
    public function setBillingCity($billing_city)
    {
        $this->billing_city = $billing_city;
    }

    /**
     * @return mixed
     */
    public function getBillingState()
    {
        return $this->billing_state;
    }

    /**
     * @param mixed $billing_state
     */
    public function setBillingState($billing_state)
    {
        $this->billing_state = $billing_state;
    }

    /**
     * @return mixed
     */
    public function getBillingZip()
    {
        return $this->billing_zip;
    }

    /**
     * @param mixed $billing_zip
     */
    public function setBillingZip($billing_zip)
    {
        $this->billing_zip = $billing_zip;
    }

    /**
     * @return mixed
     */
    public function getBillingCountry()
    {
        return $this->billing_country;
    }

    /**
     * @param mixed $billing_country
     */
    public function setBillingCountry($billing_country)
    {
        $this->billing_country = $billing_country;
    }

    /**
     * @return mixed
     */
    public function getBillingPhone()
    {
        return $this->billing_phone;
    }

    /**
     * @param mixed $billing_phone
     */
    public function setBillingPhone($billing_phone)
    {
        $this->billing_phone = $billing_phone;
    }

    /**
     * @return mixed
     */
    public function getShippingFullname()
    {
        return $this->shipping_fullname;
    }

    /**
     * @param mixed $shipping_fullname
     */
    public function setShippingFullname()
    {
        $shipping_fullname = $this->getShippingFirstname() . ' ' . $this->getShippingLastname();
        $this->shipping_fullname = $shipping_fullname;
    }

    /**
     * @return mixed
     */
    public function getShippingFirstname()
    {
        return $this->shipping_firstname;
    }

    /**
     * @param mixed $shipping_firstname
     */
    public function setShippingFirstname($shipping_firstname)
    {
        $this->shipping_firstname = $shipping_firstname;
    }

    /**
     * @return mixed
     */
    public function getShippingLastname()
    {
        return $this->shipping_lastname;
    }

    /**
     * @param mixed $shipping_lastname
     */
    public function setShippingLastname($shipping_lastname)
    {
        $this->shipping_lastname = $shipping_lastname;
    }

    /**
     * @return mixed
     */
    public function getShippingCompany()
    {
        return $this->shipping_company;
    }

    /**
     * @param mixed $shipping_company
     */
    public function setShippingCompany($shipping_company)
    {
        $this->shipping_company = $shipping_company;
    }

    /**
     * @return mixed
     */
    public function getShippingStreet()
    {
        return $this->shipping_street;
    }

    /**
     * @param mixed $shipping_street
     */
    public function setShippingStreet($shipping_street)
    {
        $this->shipping_street = $shipping_street;
    }

    /**
     * @return mixed
     */
    public function getShippingStreet2()
    {
        return $this->shipping_street2;
    }

    /**
     * @param mixed $shipping_street2
     */
    public function setShippingStreet2($shipping_street2)
    {
        $this->shipping_street2 = $shipping_street2;
    }

    /**
     * @return mixed
     */
    public function getShippingCity()
    {
        return $this->shipping_city;
    }

    /**
     * @param mixed $shipping_city
     */
    public function setShippingCity($shipping_city)
    {
        $this->shipping_city = $shipping_city;
    }

    /**
     * @return mixed
     */
    public function getShippingState()
    {
        return $this->shipping_state;
    }

    /**
     * @param mixed $shipping_state
     */
    public function setShippingState($shipping_state)
    {
        $this->shipping_state = $shipping_state;
    }

    /**
     * @return mixed
     */
    public function getShippingZip()
    {
        return $this->shipping_zip;
    }

    /**
     * @param mixed $shipping_zip
     */
    public function setShippingZip($shipping_zip)
    {
        $this->shipping_zip = $shipping_zip;
    }

    /**
     * @return mixed
     */
    public function getShippingCountry()
    {
        return $this->shipping_country;
    }

    /**
     * @param mixed $shipping_country
     */
    public function setShippingCountry($shipping_country)
    {
        $this->shipping_country = $shipping_country;
    }

    /**
     * @return mixed
     */
    public function getShippingPhone()
    {
        return $this->shipping_phone;
    }

    /**
     * @param mixed $shipping_phone
     */
    public function setShippingPhone($shipping_phone)
    {
        $this->shipping_phone = $shipping_phone;
    }



}