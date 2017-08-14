<?php
/*
DinastyCoin for WooCommerce
https://github.com/dinastyoffreedom/dinastycoin-woocommerce/
*/

//---------------------------------------------------------------------------
// Global definitions
if (!defined('DCYWC_PLUGIN_NAME'))
  {
  define('DCYWC_VERSION',           '0.01');

  //-----------------------------------------------
  define('DCYWC_EDITION',           'Standard');    

  //-----------------------------------------------
  define('DCYWC_SETTINGS_NAME',     'DCYWC-Settings');
  define('DCYWC_PLUGIN_NAME',       'DinastyCoin for WooCommerce');   


  // i18n plugin domain for language files
  define('DCYWC_I18N_DOMAIN',       'dcywc');

  }
//---------------------------------------------------------------------------

//------------------------------------------
// Load wordpress for POSTback, WebHook and API pages that are called by external services directly.
if (defined('DCYWC_MUST_LOAD_WP') && !defined('WP_USE_THEMES') && !defined('ABSPATH'))
   {
   $g_blog_dir = preg_replace ('|(/+[^/]+){4}$|', '', str_replace ('\\', '/', __FILE__)); // For love of the art of regex-ing
   define('WP_USE_THEMES', false);
   require_once ($g_blog_dir . '/wp-blog-header.php');

   // Force-elimination of header 404 for non-wordpress pages.
   header ("HTTP/1.1 200 OK");
   header ("Status: 200 OK");

   require_once ($g_blog_dir . '/wp-admin/includes/admin.php');
   }
//------------------------------------------


// This loads necessary modules
require_once (dirname(__FILE__) . '/libs/forknoteWalletdAPI.php');

require_once (dirname(__FILE__) . '/dcywc-cron.php');
require_once (dirname(__FILE__) . '/dcywc-utils.php');
require_once (dirname(__FILE__) . '/dcywc-admin.php');
require_once (dirname(__FILE__) . '/dcywc-render-settings.php');
require_once (dirname(__FILE__) . '/dcywc-dinastycoin-gateway.php');

?>