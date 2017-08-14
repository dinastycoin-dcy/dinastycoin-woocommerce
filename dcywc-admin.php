<?php
/*
DinastyCoin for WooCommerce
https://github.com/dinastyoffreedom/dinastycoin-woocommerce/
*/

// Include everything
include (dirname(__FILE__) . '/dcywc-include-all.php');

//===========================================================================
// Global vars.

global $g_DCYWC__plugin_directory_url;
$g_DCYWC__plugin_directory_url = plugins_url ('', __FILE__);

global $g_DCYWC__cron_script_url;
$g_DCYWC__cron_script_url = $g_DCYWC__plugin_directory_url . '/dcywc-cron.php';

//===========================================================================

//===========================================================================
// Global default settings
global $g_DCYWC__config_defaults;
$g_DCYWC__config_defaults = array (

   // ------- Hidden constants
   'assigned_address_expires_in_mins'     =>  12*60,   // 12 hours to pay for order and receive necessary number of confirmations.
   'funds_received_value_expires_in_mins' =>  '5',		// 'received_funds_checked_at' is fresh (considered to be a valid value) if it was last checked within 'funds_received_value_expires_in_mins' minutes.
   'blockchain_api_timeout_secs'          =>  '20',   // Connection and request timeouts for curl operations dealing with blockchain requests.
   'exchange_rate_api_timeout_secs'       =>  '10',   // Connection and request timeouts for curl operations dealing with exchange rate API requests.
   'soft_cron_job_schedule_name'          =>  'minutes_1',   // WP cron job frequency
   'cache_exchange_rates_for_minutes'			=>	10,			// Cache exchange rate for that number of minutes without re-calling exchange rate API's.

   // ------- General Settings
   'service_provider'				 						  =>  'local_wallet',		// 'blockchain_info'
   'address'                              =>  '', 
   'confs_num'                            =>  '4', // number of confirmations required before accepting payment.
   'exchange_multiplier'                  =>  '1.00',

   'delete_db_tables_on_uninstall'        =>  '0',
   'autocomplete_paid_orders'							=>  '1',
   'enable_soft_cron_job'                 =>  '1',    // Enable "soft" Wordpress-driven cron jobs.

   // ------- Coin Settings
   'coin_name'                            =>  '',   
   'coin_symbol'                          =>  '', 
   'coin_decimal_point'                   =>  8, 
   'coin_fee'                             =>  100000,
   'coin_wallet_ip'                       =>  '', 
   'coin_wallet_port'                     =>  '', 

   // ------- Special settings
   'exchange_rates'                       =>  array('EUR' => array('method|type' => array('time-last-checked' => 0, 'exchange_rate' => 1), 'GBP' => array())),
   );
//===========================================================================

//===========================================================================
function DCYWC__GetPluginNameVersionEdition($please_donate = false) // false to turn off
{
  $return_data = '<h2 style="border-bottom:1px solid #DDD;padding-bottom:10px;margin-bottom:20px;">' .
            DCYWC_PLUGIN_NAME . ', version: <span style="color:#EE0000;">' .
            DCYWC_VERSION. '</span>' .
          '</h2>';


  if ($please_donate)
  {
    $return_data .= '<p style="border:1px solid #890e4e;padding:5px 10px;color:#004400;background-color:#FFF;"><u>Please donate DCY to</u>:&nbsp;&nbsp;<span style="color:#d21577;font-size:110%;font-weight:bold;"></span></p>';
  }

  return $return_data;
}
//===========================================================================

//===========================================================================
function DCYWC__withdraw ()
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
      return "DinastyCoin address is not found in wallet.";
    } else {
      $address_balance = $address_balance['availableBalance'];
      //round ( float $val [, int $precision = 0 [, int $mode = PHP_ROUND_HALF_UP ]] )
      
      $withdraw_fee = $dcywc_settings['coin_fee'];
      $coin_decimal_point = $dcywc_settings['coin_decimal_point'];
      $one_unit = pow( 10, $coin_decimal_point );
      $display_address_balance  = sprintf("%.".$coin_decimal_point."f", $address_balance  / $one_unit);  
      $display_fee  = sprintf("%.".$coin_decimal_point."f", $withdraw_fee  / $one_unit);
      $send_amount = $address_balance - (2 * $withdraw_fee );//(floor( $address_balance / 100000000 ) * 100000000 ) - 200000000; // Only allows sending 4 decimal places
      $display_send_amount = sprintf("%.".$coin_decimal_point."f", $send_amount  / $one_unit);
      $send_address = $_POST["withdraw_address"];
      
      try{
        $sent = $wallet_api->sendTransaction( array( $address ), array(array( "amount" => $send_amount, "address" => $send_address)), false, 6, $withdraw_fee, $address );
        return "Withdraw Sent in Transaction: " . $sent["transactionHash"];
        //@TODO Log
      }
      catch(Exception $e) {
        return $e->GetMessage();
      }  
    }
}
//===========================================================================

//===========================================================================
function DCYWC__get_settings ($key=false)
{
  global   $g_DCYWC__plugin_directory_url;
  global   $g_DCYWC__config_defaults;

  $dcywc_settings = get_option (DCYWC_SETTINGS_NAME);
  if (!is_array($dcywc_settings))
    $dcywc_settings = array();

  if ($key)
    return (@$dcywc_settings[$key]);
  else
    return ($dcywc_settings);
}
//===========================================================================

//===========================================================================
function DCYWC__update_settings ($dcywc_use_these_settings=false, $also_update_persistent_settings=false)
{
   if ($dcywc_use_these_settings)
      {
      // if ($also_update_persistent_settings)
      //   DCYWC__update_persistent_settings ($dcywc_use_these_settings);

      update_option (DCYWC_SETTINGS_NAME, $dcywc_use_these_settings);
      return;
      }

   global   $g_DCYWC__config_defaults;

   // Load current settings and overwrite them with whatever values are present on submitted form
   $dcywc_settings = DCYWC__get_settings();

   foreach ($g_DCYWC__config_defaults as $k=>$v)
      {
      if (isset($_POST[$k]))
         {
         if (!isset($dcywc_settings[$k]))
            $dcywc_settings[$k] = ""; // Force set to something.
         DCYWC__update_individual_dcywc_setting ($dcywc_settings[$k], $_POST[$k]);
         }
      // If not in POST - existing will be used.
      }

  update_option (DCYWC_SETTINGS_NAME, $dcywc_settings);
}
//===========================================================================

//===========================================================================
// Takes care of recursive updating
function DCYWC__update_individual_dcywc_setting (&$dcywc_current_setting, $dcywc_new_setting)
{
   if (is_string($dcywc_new_setting))
      $dcywc_current_setting = DCYWC__stripslashes ($dcywc_new_setting);
   else if (is_array($dcywc_new_setting))  // Note: new setting may not exist yet in current setting: curr[t5] - not set yet, while new[t5] set.
      {
      // Need to do recursive
      foreach ($dcywc_new_setting as $k=>$v)
         {
         if (!isset($dcywc_current_setting[$k]))
            $dcywc_current_setting[$k] = "";   // If not set yet - force set it to something.
         DCYWC__update_individual_dcywc_setting ($dcywc_current_setting[$k], $v);
         }
      }
   else
      $dcywc_current_setting = $dcywc_new_setting;
}
//===========================================================================

//===========================================================================
//
// Reset settings only for one screen
function DCYWC__reset_partial_settings ($also_reset_persistent_settings=false)
{
   global   $g_DCYWC__config_defaults;

   // Load current settings and overwrite ones that are present on submitted form with defaults
   $dcywc_settings = DCYWC__get_settings();

   foreach ($_POST as $k=>$v)
      {
      if (isset($g_DCYWC__config_defaults[$k]))
         {
         if (!isset($dcywc_settings[$k]))
            $dcywc_settings[$k] = ""; // Force set to something.
         DCYWC__update_individual_dcywc_setting ($dcywc_settings[$k], $g_DCYWC__config_defaults[$k]);
         }
      }

  update_option (DCYWC_SETTINGS_NAME, $dcywc_settings);

  // if ($also_reset_persistent_settings)
  //   DCYWC__update_persistent_settings ($dcywc_settings);
}
//===========================================================================

//===========================================================================
function DCYWC__reset_all_settings ($also_reset_persistent_settings=false)
{
  global   $g_DCYWC__config_defaults;

  update_option (DCYWC_SETTINGS_NAME, $g_DCYWC__config_defaults);

  // if ($also_reset_persistent_settings)
  //   DCYWC__reset_all_persistent_settings ();
}
//===========================================================================

//===========================================================================
// Recursively strip slashes from all elements of multi-nested array
function DCYWC__stripslashes (&$val)
{
   if (is_string($val))
      return (stripslashes($val));
   if (!is_array($val))
      return $val;

   foreach ($val as $k=>$v)
      {
      $val[$k] = DCYWC__stripslashes ($v);
      }

   return $val;
}
//===========================================================================

//===========================================================================
/*
    ----------------------------------
    : Table 'dcy_payments' :
    ----------------------------------
      status                "unused"      - never been used address with last known zero balance
                            "assigned"    - order was placed and this address was assigned for payment
                            "revalidate"  - assigned/expired, unused or unknown address suddenly got non-zero balance in it. Revalidate it for possible late order payment against meta_data.
                            "used"        - order was placed and this address and payment in full was received. Address will not be used again.
                            "xused"       - address was used (touched with funds) by unknown entity outside of this application. No metadata is present for this address, will not be able to correlated it with any order.
                            "unknown"     - new address was generated but cannot retrieve balance due to blockchain API failure.
*/
function DCYWC__create_database_tables ($dcywc_settings)
{
  global $wpdb;

  $dcywc_settings = DCYWC__get_settings();
  $must_update_settings = false;

  $dcy_payments_table_name             = $wpdb->prefix . 'dcywc_dcy_payments';

  if($wpdb->get_var("SHOW TABLES LIKE '$dcy_payments_table_name'") != $dcy_payments_table_name)
      $b_first_time = true;
  else
      $b_first_time = false;

 //----------------------------------------------------------
 // Create tables
  $query = "CREATE TABLE IF NOT EXISTS `$dcy_payments_table_name` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `dcy_address` char(98) NOT NULL,
    `dcy_payment_id` char(64) NOT NULL,
    `origin_id` char(128) NOT NULL DEFAULT '',
    `index_in_wallet` bigint(20) NOT NULL DEFAULT '0',
    `status` char(16)  NOT NULL DEFAULT 'unknown',
    `last_assigned_to_ip` char(16) NOT NULL DEFAULT '0.0.0.0',
    `assigned_at` bigint(20) NOT NULL DEFAULT '0',
    `total_received_funds` DECIMAL( 16, 8 ) NOT NULL DEFAULT '0.00000000',
    `received_funds_checked_at` bigint(20) NOT NULL DEFAULT '0',
    `address_meta` MEDIUMBLOB NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `dcy_payment_id` (`dcy_payment_id`),
    KEY `index_in_wallet` (`index_in_wallet`),
    KEY `origin_id` (`origin_id`),
    KEY `status` (`status`)
    );";
  $wpdb->query ($query);
 //----------------------------------------------------------
}
//===========================================================================

//===========================================================================
// NOTE: Irreversibly deletes all plugin tables and data
function DCYWC__delete_database_tables ()
{
  global $wpdb;

  $dcy_payments_table_name    = $wpdb->prefix . 'dcywc_dcy_payments';

  $wpdb->query("DROP TABLE IF EXISTS `$dcy_payments_table_name`");
}
//===========================================================================

