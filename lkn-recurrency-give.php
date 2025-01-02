<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://linknacional.com.br
 * @since             1.0.0
 * @package           LknRecurrencyGive
 *
 * @wordpress-plugin
 * Plugin Name:       Link Nacional GiveWP Recurrency
 * Plugin URI:        https://www.linknacional.com.br/wordpress/givewp/
 * Description:       Plugin created to list and retrieve information about the list of recurring donors in GiveWP.
 * Version:           1.0.0
 * Author:            Link Nacional
 * Requires Plugins:  give
 * Author URI:        https://linknacional.com.br/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lkn-recurrency-give
 * Domain Path:       /Languages
 */

use Lkn\RecurrencyGive\Includes\LknRecurrencyGive;
use Lkn\RecurrencyGive\Includes\LknRecurrencyGiveActivator;
use Lkn\RecurrencyGive\Includes\LknRecurrencyGiveDeactivator;

require_once __DIR__ . '/vendor/autoload.php';

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

// Consts
if (! defined('LKN_RECURRENCY_GIVE_VERSION')) {
    define('LKN_RECURRENCY_GIVE_VERSION', '1.0.0');
}

if (! defined('LKN_RECURRENCY_GIVE_MIN_GIVE_VERSION')) {
    define('LKN_RECURRENCY_GIVE_MIN_GIVE_VERSION', '2.3.0');
}

if (! defined('LKN_RECURRENCY_GIVE_FILE')) {
    define('LKN_RECURRENCY_GIVE_FILE', __DIR__ . '/lkn-recurrency-give.php');
}

if (! defined('LKN_RECURRENCY_GIVE_DIR')) {
    define('LKN_RECURRENCY_GIVE_DIR', plugin_dir_path(LKN_RECURRENCY_GIVE_FILE));
}

if (! defined('LKN_RECURRENCY_GIVE_URL')) {
    define('LKN_RECURRENCY_GIVE_URL', plugin_dir_url(LKN_RECURRENCY_GIVE_FILE));
}

if (! defined('LKN_RECURRENCY_GIVE_BASENAME')) {
    define('LKN_RECURRENCY_GIVE_BASENAME', plugin_basename(LKN_RECURRENCY_GIVE_FILE));
}

/**
 * The code that runs during plugin activation.
 * This action is documented in Includes/LknRecurrencyGiveActivator.php
 */
function activate_lkn_recurrency_give()
{
    LknRecurrencyGiveActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in Includes/LknRecurrencyGiveDeactivator.php
 */
function deactivate_lkn_recurrency_give()
{
    LknRecurrencyGiveDeactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_lkn_recurrency_give');
register_deactivation_hook(__FILE__, 'deactivate_lkn_recurrency_give');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_lkn_recurrency_give()
{

    $plugin = new LknRecurrencyGive();
    $plugin->run();

}
run_lkn_recurrency_give();
