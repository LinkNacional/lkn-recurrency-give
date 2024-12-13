<?php

namespace Lkn\RecurrencyGive\Includes;

use Lkn\RecurrencyGive\Admin\LknRecurrencyGiveAdmin;
use Lkn\RecurrencyGive\PublicView\LknRecurrencyGivePublic;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://linknacional.com.br
 * @since      1.0.0
 *
 * @package    LknRecurrencyGive
 * @subpackage LknRecurrencyGive/Includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    LknRecurrencyGive
 * @subpackage LknRecurrencyGive/Includes
 * @author     Link Nacional <contato@linknacional.com>
 */
class LknRecurrencyGive
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      LknRecurrencyGiveLoader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('LKN_RECURRENCY_GIVE_VERSION')) {
            $this->version = LKN_RECURRENCY_GIVE_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'lkn-recurrency-give';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - LknRecurrencyGiveLoader. Orchestrates the hooks of the plugin.
     * - LknRecurrencyGiveI18n. Defines internationalization functionality.
     * - LknRecurrencyGiveAdmin. Defines all hooks for the admin area.
     * - LknRecurrencyGivePublic. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        $this->loader = new LknRecurrencyGiveLoader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the LknRecurrencyGiveI18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new LknRecurrencyGiveI18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {

        $plugin_admin = new LknRecurrencyGiveAdmin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $this, 'lkn_recurrency_settings_page');
        $this->loader->add_action('wp_ajax_lkn_get_recurrency_data', $this, 'lkn_handle_get_recurrency_data');
        $this->loader->add_action('admin_init', $this, 'add_givewp_hooks');
    }

    public function add_givewp_hooks()
    {
        add_filter('give_settings', [$this, 'test_function']);
    }

    public function test_function($menu_items)
    {
        error_log('passei');
        // $menu_items['nova-guia'] = esc_html__('Minha Nova Guia', 'textdomain');
        return $menu_items;
    }

    public function lkn_recurrency_settings_page()
    {
        add_menu_page(
            'Lkn Recurrency',          // Título da página
            'Lkn Recurrency',          // Nome do menu
            'manage_options',          // Permissão necessária
            'lkn-recurrency',          // Slug único
            array($this, 'lkn_recurrency_render_page'), // Função de renderização
            'dashicons-chart-bar',     // Ícone
            25                         // Posição no menu
        );
    }

    public function lkn_recurrency_render_page()
    {
        require LKN_RECURRENCY_GIVE_DIR . 'Admin/partials/LknRecurrencyGiveGraphDisplay.php';
    }

    public function lkn_handle_get_recurrency_data()
    {
        global $wpdb;

        // Get year, month, currency, and mode parameters
        $month_param = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : '';
        $year_param = isset($_GET['year']) ? sanitize_text_field($_GET['year']) : '';
        $currency_param = isset($_GET['currency']) ? sanitize_text_field($_GET['currency']) : '';
        $mode_param = isset($_GET['mode']) ? sanitize_text_field($_GET['mode']) : '';

        // Check if month parameter is provided
        if (empty($month_param)) {
            wp_send_json_error(['message' => sprintf(__('No parameter for <strong>%s</strong> was found.', 'lkn-recurrency-give'), 'month')]);
        }

        // Check if year parameter is provided
        if (empty($year_param)) {
            wp_send_json_error(['message' => sprintf(__('No parameter for <strong>%s</strong> was found.', 'lkn-recurrency-give'), 'year')]);
        }

        // Check if currency parameter is provided
        if (empty($currency_param)) {
            wp_send_json_error(['message' => sprintf(__('No parameter for <strong>%s</strong> was found.', 'lkn-recurrency-give'), 'currency')]);
        }

        // Check if mode parameter is provided
        if (empty($mode_param)) {
            wp_send_json_error(['message' => sprintf(__('No parameter for <strong>%s</strong> was found.', 'lkn-recurrency-give'), 'mode')]);
        }

        $month_key = "{$year_param}-{$month_param}";

        $data_grouped = [
            $month_key => [
                'total' => 0,
                'donations' => []
            ]
        ];

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'lkn-recurrency-give')], 403);
        }

        $start_date = "{$year_param}-{$month_param}-01 00:00:00";
        $end_date = date("Y-m-t 23:59:59", strtotime($start_date));

        // Custom query
        $sql = $wpdb->prepare(
            "
        SELECT DISTINCT donation_id
        FROM {$wpdb->prefix}give_donationmeta
        WHERE (meta_key = '_give_is_donation_recurring' AND meta_value = 1)
           OR (meta_key = '_give_payment_currency' AND meta_value = %s)
           OR (meta_key = '_give_payment_mode' AND meta_value = %s)
           OR (meta_key = '_give_completed_date' AND meta_value BETWEEN %s AND %s)
        GROUP BY donation_id
        HAVING COUNT(DISTINCT meta_key) = 4
        ",
            $currency_param,
            $mode_param,
            $start_date,
            $end_date
        );

        $results = $wpdb->get_results($sql);

        if (!empty($results)) {
            $donation_ids = array_map(function ($result) {
                return $result->donation_id;
            }, $results);
        } else {
            wp_send_json_success($data_grouped);
        }

        if (!empty($donation_ids)) {
            $placeholders = implode(',', array_fill(0, count($donation_ids), '%d'));

            $sql = "
            SELECT dm.donation_id, dm.meta_key, dm.meta_value
            FROM {$wpdb->prefix}give_donationmeta dm
            WHERE dm.donation_id IN ($placeholders)
            AND dm.meta_key IN (
                '_give_payment_total',
                '_give_payment_currency',
                '_give_payment_donor_id',
                '_give_donor_billing_first_name',
                '_give_donor_billing_last_name',
                '_give_payment_donor_email',
                '_give_payment_mode',
                '_give_completed_date'
            )
        ";

            $results = $wpdb->get_results($wpdb->prepare($sql, ...$donation_ids));

            $donation_data = [];
            foreach ($results as $result) {
                if (!isset($donation_data[$result->donation_id])) {
                    $donation_data[$result->donation_id] = [];
                }
                $donation_data[$result->donation_id][$result->meta_key] = $result->meta_value;
            }

            $data = [];
            foreach ($donation_data as $donation_id => $donation_info) {
                $completed_date = isset($donation_info['_give_completed_date']) ? $donation_info['_give_completed_date'] : null;
                $completed_date_plus_month = null;

                if ($completed_date) {
                    $date = new \DateTime($completed_date);
                    $date->modify('+1 month');
                    $completed_date_plus_month = $date->format('Y-m-d H:i:s');
                }

                if ($completed_date_plus_month) {
                    $sql_sub = "
                SELECT period, frequency, profile_id, expiration
                FROM {$wpdb->prefix}give_subscriptions
                WHERE expiration = %s
            ";
                    $sub_results = $wpdb->get_results($wpdb->prepare($sql_sub, $completed_date_plus_month, $donation_id));

                    $subscription_data = [];
                    if (!empty($sub_results)) {
                        $subscription_data = $sub_results[0];
                    }

                    $data[] = [
                        'donation_id' => $donation_id,
                        'user_id' => $donation_info['_give_payment_donor_id'],
                        'total' => isset($donation_info['_give_payment_total']) ? number_format($donation_info['_give_payment_total'], 2, ',', '.') : __('N/A', 'lkn-recurrency-give'),
                        'currency' => $donation_info['_give_payment_currency'] ?? __('N/A', 'lkn-recurrency-give'),
                        'first_name' => $donation_info['_give_donor_billing_first_name'] ?? __('N/A', 'lkn-recurrency-give'),
                        'last_name' => $donation_info['_give_donor_billing_last_name'] ?? __('N/A', 'lkn-recurrency-give'),
                        'email' => $donation_info['_give_payment_donor_email'] ?? __('N/A', 'lkn-recurrency-give'),
                        'payment_mode' => $donation_info['_give_payment_mode'] ?? __('N/A', 'lkn-recurrency-give'),
                        'completed_date' => $donation_info['_give_completed_date'] ?? __('N/A', 'lkn-recurrency-give'),
                        'subscription_period' => $subscription_data->period ?? __('N/A', 'lkn-recurrency-give'),
                        'subscription_frequency' => $subscription_data->frequency ?? __('N/A', 'lkn-recurrency-give'),
                        'subscription_profile_id' => $subscription_data->profile_id ?? __('N/A', 'lkn-recurrency-give'),
                        'expiration' => $subscription_data->expiration ?? __('N/A', 'lkn-recurrency-give')
                    ];
                }
            }

            foreach ($data as $entry) {
                $month = date('Y-m', strtotime($entry['completed_date']));
                if (!isset($data_grouped[$month])) {
                    $data_grouped[$month] = [
                        'total' => 0,
                        'donations' => [],
                    ];
                }
                $data_grouped[$month]['total'] += floatval(str_replace(',', '.', $entry['total']));
                $data_grouped[$month]['donations'][] = $entry;
            }

            wp_send_json_success($data_grouped);
        } else {
            wp_send_json_error(['message' => __('No donation ID found.', 'lkn-recurrency-give')]);
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new LknRecurrencyGivePublic($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    LknRecurrencyGiveLoader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

}
