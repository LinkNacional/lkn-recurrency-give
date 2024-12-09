<?php

namespace Lkn\RecurrencyGive\Includes;

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://linknacional.com.br
 * @since      1.0.0
 *
 * @package    LknRecurrencyGive
 * @subpackage LknRecurrencyGive/Includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    LknRecurrencyGive
 * @subpackage LknRecurrencyGive/Includes
 * @author     Link Nacional <contato@linknacional.com>
 */
class LknRecurrencyGiveI18n
{
    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain()
    {

        load_plugin_textdomain(
            'lkn-recurrency-give',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );

    }



}
