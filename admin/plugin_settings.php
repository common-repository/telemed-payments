<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="postbox">
    <?php settings_fields('telemed_option_group'); ?>

    <h3>Environment</h3>

    <p>By default, the Telemed Payments plugin will perform all transactions against the production
        environment.
        The plugin may be switched to sandbox environment for testing purposes.
    </p>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Environment:</th>
            <td>
                <input id="environment_sandbox" type="radio"
                       name="<?php echo $this->option_name ?>[environment]"
                       value="sandbox" <?php checked('sandbox', $options_opts['environment']); ?> />
                <label for="environment_sandbox">Sandbox</label>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input id="environment_live" type="radio"
                       name="<?php echo $this->option_name ?>[environment]"
                       value="production" <?php checked('production', $options_opts['environment']); ?> />
                <label for="environment_live">Production</label>
            </td>
        </tr>
    </table>

    <h3>Success URL</h3>

    <p>Enter a url for successful transactions (a success page). If no url
        is specified (blank), the user will be redirected to the home page.
    </p>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label for="success_url">Success URL:</label></th>
            <td>
                <?php
                $args = array(
                    'depth' => 0,
                    'child_of' => 0,
                    'selected' => $options_opts['success_url'],
                    'echo' => 1,
                    'name' => 'clearent_opts[success_url]',
                    'id' => 'success_url', // string
                    'class' => 'large', // string
                    'show_option_none' => 'Homepage', // string
                    'show_option_no_change' => null, // string
                    'option_none_value' => '-1', // string
                );
                wp_dropdown_pages($args);
                ?>
            </td>
        </tr>
    </table>

    <h3>API Keys</h3>

    <p>Contact <a target="_blank" href="https://telemedprocessing.com/contact-us/">Telemed Processing</a>
        to obtain
        API keys for Sandbox (testing) and Production. A Telemed Sandbox Account and a Telemed
        Production
        Account will have different API keys.
    </p>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label for="sb_api_key">Sandbox API Key</label></th>
            <td><input type="text" class="large" id="sb_api_key"
                       name="<?php echo $this->option_name ?>[sb_api_key]"
                       value="<?php echo $options_opts['sb_api_key']; ?>"/></td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="prod_api_key">Production API Key</label></th>
            <td><input type="text" class="large" id="prod_api_key"
                       name="<?php echo $this->option_name ?>[prod_api_key]"
                       value="<?php echo $options_opts['prod_api_key']; ?>"/></td>
        </tr>
    </table>

    <h3>Payment Notification</h3>

    <p>Set an email address to be copied when clients make a payment. You will be sent via BCC to this email.
        You can also set a custom subject line for the email that is sent to your clients after a payment is made.
        The From address can be any email you want clients to be able to respond to (default is WordPress admin).</p>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label for="notification_email">Notification Email:</label></th>
            <td><input type="text" class="large" id="notification_email"
                       name="<?php echo $this->option_name ?>[notification_email]"
                       value="<?php echo $options_opts['notification_email']; ?>"/>
                <span class="help-block">The email that will be BCc'd when a client makes a payment.</span>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="notification_email_from_name">From Name:</label></th>
            <td><input type="text" class="large" id="notification_email_from_name"
                       name="<?php echo $this->option_name ?>[notification_email_from_name]"
                       value="<?php echo $options_opts['notification_email_from_name']; ?>"/>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="notification_email_from">From Email:</label></th>
            <td><input type="text" class="large" id="notification_email_from"
                       name="<?php echo $this->option_name ?>[notification_email_from]"
                       value="<?php echo $options_opts['notification_email_from']; ?>"/>
                <span class="help-block">Both the From Email and From Name are needed, otherwise the default WordPress name and email will be used.</span>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="notification_email_subject">Payment Email Subject:</label></th>
            <td><input type="text" class="large" id="notification_email_subject"
                       name="<?php echo $this->option_name ?>[notification_email_subject]"
                       value="<?php echo $options_opts['notification_email_subject']; ?>"/></td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="email_company_name">Company Name to show on email:</label></th>
            <td><input type="text" class="large" id="email_company_name"
                       name="<?php echo $this->option_name ?>[email_company_name]"
                       value="<?php echo $options_opts['email_company_name']; ?>"/>
                <span class="help-block">This will be what is used for the header of the email and thanking customer for shopping with this company name.</span>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="email_charge_from">Charge from name to show on email:</label></th>
            <td><input type="text" class="large" id="email_charge_from"
                       name="<?php echo $this->option_name ?>[email_charge_from]"
                       value="<?php echo $options_opts['email_charge_from']; ?>"/>
                <span class="help-block">The name the customer will see on their credit card statement for the charge.</span>
            </td>
        </tr>
    </table>

    <h3>Debug Logging</h3>

    <p>Enable debug to help diagnose issues or if instructed by Telemed Processing support. <span class="warning">Debug mode can
        quickly fill up php logs and should be disabled unless debugging a specific issue.</span></p>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Enable Debug Logging?</th>
            <td>
                <input id="enable_debug_disabled" type="radio"
                       name="<?php echo $this->option_name ?>[enable_debug]"
                       value="disabled" <?php checked('disabled', $options_opts['enable_debug']); ?> />
                <label for="enable_debug_disabled">Disabled</label>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input id="enable_debug_enabled" type="radio"
                       name="<?php echo $this->option_name ?>[enable_debug]"
                       value="enabled" <?php checked('enabled', $options_opts['enable_debug']); ?> />
                <label for="enable_debug_enabled">Enabled</label>


            </td>
        </tr>
    </table>
</div>