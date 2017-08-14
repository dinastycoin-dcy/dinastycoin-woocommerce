<?php
/*
DinastyCoin for WooCommerce
https://github.com/dinastyoffreedom/dinastycoin-woocommerce/
*/

// Include everything
include (dirname(__FILE__) . '/dcywc-include-all.php');

//===========================================================================
function DCYWC__render_general_settings_page ()   { DCYWC__render_settings_page   ('general'); }
//function DCYWC__render_advanced_settings_page ()  { DCYWC__render_settings_page   ('advanced'); }
//===========================================================================

//===========================================================================
function DCYWC__render_settings_page ($menu_page_name)
{
   $dcywc_settings = DCYWC__get_settings ();
   if (isset ($_POST['button_withdraw']))
      {
      $result = DCYWC__withdraw();
echo '
<div align="center" style="background-color:#FFFFE0;padding:5px;font-size:120%;border: 1px solid #E6DB55;margin:5px;border-radius:3px;">
' . $result . '
</div>
';
      }
   else if (isset ($_POST['button_update_dcywc_settings']))
      {
      DCYWC__update_settings ("", false);
echo <<<HHHH
<div align="center" style="background-color:#FFFFE0;padding:5px;font-size:120%;border: 1px solid #E6DB55;margin:5px;border-radius:3px;">
Settings updated!
</div>
HHHH;
      }
   else if (isset($_POST['button_reset_dcywc_settings']))
      {
      DCYWC__reset_all_settings (false);
echo <<<HHHH
<div align="center" style="background-color:#FFFFE0;padding:5px;font-size:120%;border: 1px solid #E6DB55;margin:5px;border-radius:3px;">
All settings reverted to all defaults
</div>
HHHH;
      }
   else if (isset($_POST['button_reset_partial_dcywc_settings']))
      {
      DCYWC__reset_partial_settings (false);
echo <<<HHHH
<div align="center" style="background-color:#FFFFE0;padding:5px;font-size:120%;border: 1px solid #E6DB55;margin:5px;border-radius:3px;">
Settings on this page reverted to defaults
</div>
HHHH;
      }

   // Output full admin settings HTML
  $gateway_status_message = "";
  $gateway_valid_for_use = DCYWC__is_gateway_valid_for_use($gateway_status_message);
  if (!$gateway_valid_for_use)
  {
    $gateway_status_message =
    '<p style="border:1px solid #DDD;padding:5px 10px;font-weight:bold;color:#EE0000;background-color:#FFFFAA;">' .
    "DinastyCoin Payment Gateway is NOT operational (try to re-enter and save settings): " . $gateway_status_message .
    '</p>';
  }
  else
  {
    $dcywc_settings = DCYWC__get_settings();
    $address = $dcywc_settings['address'];

    try{
      $wallet_api = New ForkNoteWalletd("http://127.0.0.1:8071");
      $address_balance = $wallet_api->getBalance($address);
    }
    catch(Exception $e) {
    }          

    if ($address_balance === false)
    {
      $address_balance = __("DinastyCoin address is not found in wallet.", 'woocommerce');
    } else {
      $address_pending_balance = $address_balance['lockedAmount'];
      $withdraw_fee = $dcywc_settings['coin_fee'];
      $coin_decimal_point = $dcywc_settings['coin_decimal_point'];
      $one_unit = pow( 10, $coin_decimal_point );
      $address_pending_balance = sprintf("%.".$coin_decimal_point."f", $address_pending_balance  / $one_unit);
      $address_balance = $address_balance['availableBalance'];
      $display_address_balance  = sprintf("%.".$coin_decimal_point."f", $address_balance  / $one_unit);
      $display_fee  = sprintf("%.".$coin_decimal_point."f", $withdraw_fee  / $one_unit);
    }


    $gateway_status_message =
    '<form method="post" action="' . $_SERVER['REQUEST_URI'] . '">' . 
    '<p style="border:1px solid #DDD;padding:5px 10px;font-weight:bold;color:#004400;background-color:#CCFFCC;">' .
    "DinastyCoin Payment Gateway is operational" .
    "<br>Pending Amount: " . $address_pending_balance . 
    "<br>Available Balance: " . $display_address_balance .
    "<br>Send Balance (Minus Fee:" . $display_fee . ") To Address: " .
    '<textarea style="width:75%;" name="withdraw_address"></textarea>' .
    '<br><input type="submit" class="button-primary" name="button_withdraw" value="Withdraw" />
    </p>
    </form>';
  }

  $currency_code = false;
  if (function_exists('get_woocommerce_currency'))
    $currency_code = @get_woocommerce_currency();
  if (!$currency_code || $currency_code=='DCY')
    $currency_code = 'USD';

  $exchange_rate_message =
    '<p style="border:1px solid #DDD;padding:5px 10px;background-color:#cceeff;">' .
    DCYWC__get_exchange_rate_per_DinastyCoin ($currency_code, 'getfirst', true) .
    '</p>';

   echo '<div class="wrap">';

   switch ($menu_page_name)
      {
      case 'general'     :
        echo  DCYWC__GetPluginNameVersionEdition();
        echo  $gateway_status_message . $exchange_rate_message;
        DCYWC__render_general_settings_page_html();
        break;

      default            :
        break;
      }

   echo '</div>'; // wrap
}
//===========================================================================

//===========================================================================
function DCYWC__render_general_settings_page_html ()
{
  $dcywc_settings = DCYWC__get_settings ();
  global $g_DCYWC__cron_script_url;

?>

    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
      <p class="submit">
        <input type="submit" class="button-primary"    name="button_update_dcywc_settings"        value="<?php _e('Save Changes') ?>"             />
        <input type="submit" class="button-secondary"  style="color:red;" name="button_reset_partial_dcywc_settings" value="<?php _e('Reset settings') ?>" onClick="return confirm('Are you sure you want to reset settings on this page?');" />
      </p>
      <table class="form-table">


        <tr valign="top">
          <th scope="row">Delete all plugin-specific settings, database tables and data on uninstall:</th>
          <td>
            <input type="hidden" name="delete_db_tables_on_uninstall" value="0" /><input type="checkbox" name="delete_db_tables_on_uninstall" value="1" <?php if ($dcywc_settings['delete_db_tables_on_uninstall']) echo 'checked="checked"'; ?> />
            <p class="description">If checked - all plugin-specific settings, database tables and data will be removed from Wordpress database upon plugin uninstall (but not upon deactivation or upgrade).</p>
          </td>
        </tr>

        <tr valign="top">
          <th scope="row">DinastyCoin Service Provider:</th>
          <td>
            <select name="service_provider" class="select ">
              <option <?php if ($dcywc_settings['service_provider'] == 'local_wallet') echo 'selected="selected"'; ?> value="local_wallet">Local wallet (walletd)</option>
            </select>
            <p class="description">
              Please select your DinastyCoin service provider and press [Save changes]. Then fill-in necessary details and press [Save changes] again.
              <br />Recommended setting: <b>Local wallet</b>.
            </p>
          </td>
        </tr>

        <tr valign="top">
          <th scope="row">Wallet Address:</th>
          <td>
            <textarea style="width:75%;" name="address"><?php echo $dcywc_settings['address']; ?></textarea>
            <p class="description">
              Set up your local wallet with the instructions for <a href="http://forknote.net/documentation/rpc-wallet/">the ForkNote RPC Wallet</a> or <a href="https://wiki.bytecoin.org/wiki/Bytecoin_RPC_Wallet">Reference(Bytecoin) RPC Wallet</a>. Then copy in one of your wallet addresses.
            </p>
          </td>
        </tr>

        <tr valign="top">
          <th scope="row">Number of confirmations required before accepting payment:</th>
          <td>
            <input type="text" name="confs_num" value="<?php echo $dcywc_settings['confs_num']; ?>" size="4" />
            <p class="description">
              After a transaction is broadcast to the DinastyCoin network, it may be included in a block that is published
              to the network. When that happens it is said that one <a href="https://en.DinastyCoin.it/wiki/Confirmation"><b>confirmation</b></a> has occurred for the transaction.
              With each subsequent block that is found, the number of confirmations is increased by one. To protect against double spending, a transaction should not be considered as confirmed until a certain number of blocks confirm, or verify that transaction.
              6 is considered very safe number of confirmations, although it takes longer to confirm.
            </p>
          </td>
        </tr>

        <tr valign="top">
          <th scope="row">Payment expiration time (minutes):</th>
          <td>
            <input type="text" name="assigned_address_expires_in_mins" value="<?php echo $dcywc_settings['assigned_address_expires_in_mins']; ?>" size="4" />
            <p class="description">
              Payment must recieve the required number of confirmations within this time. This is so that the exchange rate is current.
            </p>
          </td>
        </tr>

        <tr valign="top">
          <th scope="row">Exchange rate multiplier:</th>
          <td>
            <input type="text" name="exchange_multiplier" value="<?php echo $dcywc_settings['exchange_multiplier']; ?>" size="4" />
            <p class="description">
              Extra multiplier to apply to convert store default currency to DinastyCoin price.
              <br />Example: 1.05 - will add extra 5% to the total price in DinastyCoins.
              May be useful to compensate for market volatility or for merchant's loss to fees when converting DinastyCoins to local currency,
              or to encourage customer to use DinastyCoins for purchases (by setting multiplier to < 1.00 values).
            </p>
          </td>
        </tr>

        <tr valign="top">
          <th scope="row">Auto-complete paid orders:</th>
          <td>
            <input type="hidden" name="autocomplete_paid_orders" value="0" /><input type="checkbox" name="autocomplete_paid_orders" value="1" <?php if ($dcywc_settings['autocomplete_paid_orders']) echo 'checked="checked"'; ?> />
            <p class="description">If checked - fully paid order will be marked as 'completed' and '<i>Your order is complete</i>' email will be immediately delivered to customer.
            	<br />If unchecked: store admin will need to mark order as completed manually - assuming extra time needed to ship physical product after payment is received.
<!--             	<br />Note: virtual/downloadable products will automatically complete upon receiving full payment (so this setting does not have effect in this case). -->
            </p>
          </td>
        </tr>

        <tr valign="top">
            <th scope="row">Cron job type:</th>
            <td>
              <select name="enable_soft_cron_job" class="select ">
                <option <?php if ($dcywc_settings['enable_soft_cron_job'] == '1') echo 'selected="selected"'; ?> value="1">Soft Cron (Wordpress-driven)</option>
                <option <?php if ($dcywc_settings['enable_soft_cron_job'] != '1') echo 'selected="selected"'; ?> value="0">Hard Cron (Cpanel-driven)</option>
              </select>
              <p class="description">
                <?php if ($dcywc_settings['enable_soft_cron_job'] != '1') echo '<p style="background-color:#FFC;color:#2A2;"><b>NOTE</b>: Hard Cron job is enabled: make sure to follow instructions below to enable hard cron job at your hosting panel.</p>'; ?>
                Cron job will take care of all regular DinastyCoin payment processing tasks, like checking if payments are made and automatically completing the orders.<br />
                <b>Soft Cron</b>: - Wordpress-driven (runs on behalf of a random site visitor).
                <br />
                <b>Hard Cron</b>: - Cron job driven by the website hosting system/server (usually via CPanel). <br />
                When enabling Hard Cron job - make this script to run every 5 minutes at your hosting panel cron job scheduler:<br />
                <?php echo '<tt style="background-color:#FFA;color:#B00;padding:0px 6px;">wget -O /dev/null ' . $g_DCYWC__cron_script_url . '?hardcron=1</tt>'; ?>
                <br /><b style="color:red;">NOTE:</b> Cron jobs <b>might not work</b> if your site is password protected with HTTP Basic auth or other methods. This will result in WooCommerce store not seeing received payments (even though funds will arrive correctly to your DinastyCoin addresses).
                <br /><u>Note:</u> You will need to deactivate/reactivate plugin after changing this setting for it to have effect.<br />
                "Hard" cron jobs may not be properly supported by all hosting plans (many shared hosting plans has restrictions in place).
                <br />For secure, fast hosting service optimized for wordpress and 100% compatibility with WooCommerce and DinastyCoin we recommend <b><a href="http://hostrum.com/" target="_blank">Hostrum Hosting</a></b>.
              </p>
            </td>
        </tr>

      </table>

      <p class="submit">
          <input type="submit" class="button-primary"    name="button_update_dcywc_settings"        value="<?php _e('Save Changes') ?>"             />
          <input type="submit" class="button-secondary"  style="color:red;" name="button_reset_partial_dcywc_settings" value="<?php _e('Reset settings') ?>" onClick="return confirm('Are you sure you want to reset settings on this page?');" />
      </p>
    </form>
<?php
}
//===========================================================================
