<?php

namespace Lkn\RecurrencyGive\Admin;

use Lkn\RecurrencyGive\Includes\LknRecurrencyGiveHelper;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://linknacional.com.br
 * @since      1.0.0
 *
 * @package    LknRecurrencyGive
 * @subpackage LknRecurrencyGive/Admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    LknRecurrencyGive
 * @subpackage LknRecurrencyGive/Admin
 * @author     Link Nacional <contato@linknacional.com>
 */
class LknRecurrencyGiveAdmin
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in LknRecurrencyGiveLoader as all of the hooks are defined
         * in that particular class.
         *
         * The LknRecurrencyGiveLoader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        if (isset($_GET['page']) && $_GET['page'] === 'lkn-recurrency') {
            wp_enqueue_style('lkn-style-settings', plugin_dir_url(__FILE__) . 'css/LknRecurrencyGiveAdmin.css', array(), $this->version, 'all');
        }

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in LknRecurrencyGiveLoader as all of the hooks are defined
         * in that particular class.
         *
         * The LknRecurrencyGiveLoader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        if (isset($_GET['page']) && $_GET['page'] === 'lkn-recurrency') {
            wp_enqueue_script(
                'lkn-chart',
                plugin_dir_url(__FILE__) . 'js/LknRecurrencyGiveChart.js',
                array('jquery'),
                $this->version,
                false
            );

            wp_enqueue_script(
                'lkn-chart-adapter-date-fns',
                plugin_dir_url(__FILE__) . 'js/LknRecurrencyGiveChartAdapterDate.js',
                array('jquery', 'lkn-chart'),
                $this->version,
                false
            );

            wp_enqueue_script(
                'lkn-settings-graph',
                plugin_dir_url(__FILE__) . 'js/LknRecurrencyGiveSettingsGraph.js',
                array( 'jquery', 'lkn-chart', 'lkn-chart-adapter-date-fns'),
                $this->version,
                false
            );

            wp_localize_script(
                'lkn-settings-graph',
                'lknRecurrencyVars',
                [
                    'apiUrlBase' => admin_url('admin-ajax.php?action=lkn_get_recurrency_data')
                ]
            );

            wp_localize_script(
                'lkn-settings-graph',
                'lknRecurrencyTexts',
                LknRecurrencyGiveHelper::get_texts()
            );
        }


    }

}
