<?php
/*

Plugin Name: DinastyCoin for WooCommerce
Plugin URI: https://github.com/dinastyoffreedom/dinastycoin-woocommerce/
Description: DinastyCoin for WooCommerce plugin allows you to accept payments in DinastyCoins for physical and digital products at your WooCommerce-powered online store.
Version: 0.01
Author: KittyCatTech
Author URI: https://github.com/dinastyoffreedom/dinastycoin-woocommerce/
License: BipCot NoGov Software License bipcot.org

*/


// Include everything
include (dirname(__FILE__) . '/dcywc-include-all.php');

//---------------------------------------------------------------------------
// Add hooks and filters

// create custom plugin settings menu
add_action( 'admin_menu',                   'DCYWC_create_menu' );

register_activation_hook(__FILE__,          'DCYWC_activate');
register_deactivation_hook(__FILE__,        'DCYWC_deactivate');
register_uninstall_hook(__FILE__,           'DCYWC_uninstall');

add_filter ('cron_schedules',               'DCYWC__add_custom_scheduled_intervals');
add_action ('DCYWC_cron_action',             'DCYWC_cron_job_worker');     // Multiple functions can be attached to 'DCYWC_cron_action' action

DCYWC_set_lang_file();
//---------------------------------------------------------------------------

//===========================================================================
// activating the default values
function DCYWC_activate()
{
    global  $g_DCYWC__config_defaults;

    $dcywc_default_options = $g_DCYWC__config_defaults;

    // This will overwrite default options with already existing options but leave new options (in case of upgrading to new version) untouched.
    $dcywc_settings = DCYWC__get_settings ();

    foreach ($dcywc_settings as $key=>$value)
    	$dcywc_default_options[$key] = $value;

    update_option (DCYWC_SETTINGS_NAME, $dcywc_default_options);

    // Re-get new settings.
    $dcywc_settings = DCYWC__get_settings ();

    // Create necessary database tables if not already exists...
    DCYWC__create_database_tables ($dcywc_settings);
    DCYWC__SubIns ();

    //----------------------------------
    // Setup cron jobs

    if ($dcywc_settings['enable_soft_cron_job'] && !wp_next_scheduled('DCYWC_cron_action'))
    {
    	$cron_job_schedule_name = $dcywc_settings['soft_cron_job_schedule_name'];
    	wp_schedule_event(time(), $cron_job_schedule_name, 'DCYWC_cron_action');
    }
    //----------------------------------

}
//---------------------------------------------------------------------------
// Cron Subfunctions
function DCYWC__add_custom_scheduled_intervals ($schedules)
{
	$schedules['seconds_30']     = array('interval'=>30,     'display'=>__('Once every 30 seconds'));
	$schedules['minutes_1']      = array('interval'=>1*60,   'display'=>__('Once every 1 minute'));
	$schedules['minutes_2.5']    = array('interval'=>2.5*60, 'display'=>__('Once every 2.5 minutes'));
	$schedules['minutes_5']      = array('interval'=>5*60,   'display'=>__('Once every 5 minutes'));

	return $schedules;
}
//---------------------------------------------------------------------------
//===========================================================================

//===========================================================================
// deactivating
function DCYWC_deactivate ()
{
    // Do deactivation cleanup. Do not delete previous settings in case user will reactivate plugin again...

    //----------------------------------
    // Clear cron jobs
    wp_clear_scheduled_hook ('DCYWC_cron_action');
    //----------------------------------
}
//===========================================================================

//===========================================================================
// uninstalling
function DCYWC_uninstall ()
{
    $dcywc_settings = DCYWC__get_settings();

    if ($dcywc_settings['delete_db_tables_on_uninstall'])
    {
        // delete all settings.
        delete_option(DCYWC_SETTINGS_NAME);

        // delete all DB tables and data.
        DCYWC__delete_database_tables ();
    }
}
//===========================================================================

//===========================================================================
function DCYWC_create_menu()
{

    // create new top-level menu
    // http://www.fileformat.info/info/unicode/char/e3f/index.htm
    add_menu_page (
        __('Woo DinastyCoin', DCYWC_I18N_DOMAIN),                    // Page title
        __('DinastyCoin', DCYWC_I18N_DOMAIN),                        // Menu Title - lower corner of admin menu
        'administrator',                                        // Capability
        'dcywc-settings',                                        // Handle - First submenu's handle must be equal to parent's handle to avoid duplicate menu entry.
        'DCYWC__render_general_settings_page',                   // Function
        plugins_url('/images/dinastycoin_16x.png', __FILE__)      // Icon URL
        );

    add_submenu_page (
        'dcywc-settings',                                        // Parent
        __("WooCommerce DinastyCoin Gateway", DCYWC_I18N_DOMAIN),                   // Page title
        __("General Settings", DCYWC_I18N_DOMAIN),               // Menu Title
        'administrator',                                        // Capability
        'dcywc-settings',                                        // Handle - First submenu's handle must be equal to parent's handle to avoid duplicate menu entry.
        'DCYWC__render_general_settings_page'                    // Function
        );

}
//===========================================================================

//===========================================================================
// load language files
function DCYWC_set_lang_file()
{
    # set the language file
    $currentLocale = get_locale();
    if(!empty($currentLocale))
    {
        $moFile = dirname(__FILE__) . "/lang/" . $currentLocale . ".mo";
        if (@file_exists($moFile) && is_readable($moFile))
        {
            load_textdomain(DCYWC_I18N_DOMAIN, $moFile);
        }

    }
}
//===========================================================================

