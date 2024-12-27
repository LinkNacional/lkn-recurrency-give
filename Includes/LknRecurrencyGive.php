<?php

namespace Lkn\RecurrencyGive\Includes;

use Lkn\RecurrencyGive\Admin\LknRecurrencyGiveAdmin;
use Lkn\RecurrencyGive\PublicView\LknRecurrencyGivePublic;
use WP_REST_Response;

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
        $this->loader->add_action('wp_ajax_lkn_get_recurrency_data', $this, 'lkn_handle_get_recurrency_data');
        $this->loader->add_action('rest_api_init', $this, 'registerApiRoute');

    }

    public function registerApiRoute()
    {
        register_rest_route('lkn-recurrency/v1', '/content/', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_content')
        ));
    }

    public function get_content()
    {
        ob_start();
        include LKN_RECURRENCY_GIVE_DIR . 'Admin/partials/LknRecurrencyGiveGraphDisplay.php';
        $content = ob_get_clean();
        return new WP_REST_Response($content, 200);
    }

    public function lkn_recurrency_render_page()
    {
        require LKN_RECURRENCY_GIVE_DIR . 'Admin/partials/LknRecurrencyGiveGraphDisplay.php';
    }

    public function lkn_handle_get_recurrency_data()
    {
        global $wpdb;

        if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'lkn_recurrency_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Nonce verification failed.', 'lkn-recurrency-give')));
            return;
        }

        // Get year, month, currency, and mode parameters
        $month_param = isset($_GET['month']) ? sanitize_text_field(wp_unslash($_GET['month'])) : '';
        $year_param = isset($_GET['year']) ? sanitize_text_field(wp_unslash($_GET['year'])) : '';
        $currency_param = isset($_GET['currency']) ? sanitize_text_field(wp_unslash($_GET['currency'])) : '';
        $mode_param = isset($_GET['mode']) ? sanitize_text_field(wp_unslash($_GET['mode'])) : '';

        // Check if month parameter is provided
        if (empty($month_param)) {
            // translators: %s is the name of the parameter (e.g., "month").
            wp_send_json_error(['message' => sprintf(__('No parameter for <strong>%s</strong> was found.', 'lkn-recurrency-give'), 'month')]);
        }

        // Check if year parameter is provided
        if (empty($year_param)) {
            // translators: %s is the name of the parameter (e.g., "year").
            wp_send_json_error(['message' => sprintf(__('No parameter for <strong>%s</strong> was found.', 'lkn-recurrency-give'), 'year')]);
        }

        // Check if currency parameter is provided
        if (empty($currency_param)) {
            // translators: %s is the name of the parameter (e.g., "currency").
            wp_send_json_error(['message' => sprintf(__('No parameter for <strong>%s</strong> was found.', 'lkn-recurrency-give'), 'currency')]);
        }

        // Check if mode parameter is provided
        if (empty($mode_param)) {
            // translators: %s is the name of the parameter (e.g., "mode").
            wp_send_json_error(['message' => sprintf(__('No parameter for <strong>%s</strong> was found.', 'lkn-recurrency-give'), 'mode')]);
        }

        $data_grouped = [
            'date' => '',
            'total' => 0,
            'donations' => []
        ];

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'lkn-recurrency-give')], 403);
        }

        // Start between initial date
        $start_date = "{$year_param}-{$month_param}-01 00:00:00";
        $end_date = gmdate("Y-m-t 23:59:59", strtotime($start_date));

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    meta_donor.donation_id,
                    meta_billing_first_name.meta_value AS billing_first_name,
                    meta_billing_last_name.meta_value AS billing_last_name,
                    meta_email.meta_value AS donor_email,
                    meta_currency.meta_value AS payment_currency,
                    meta_recurring.meta_value AS is_recurring,
                    subs.customer_id,
                    subs.frequency,
                    subs.recurring_amount,
                    subs.payment_mode,
                    subs.created,
                    subs.expiration,
                    subs.profile_id
                FROM
                    {$wpdb->prefix}give_subscriptions AS subs
                LEFT JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_donor
                    ON subs.customer_id = meta_donor.meta_value
                    AND meta_donor.meta_key = '_give_payment_donor_id'
                LEFT JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_billing_first_name
                    ON meta_donor.donation_id = meta_billing_first_name.donation_id
                    AND meta_billing_first_name.meta_key = '_give_donor_billing_first_name'
                LEFT JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_billing_last_name
                    ON meta_donor.donation_id = meta_billing_last_name.donation_id
                    AND meta_billing_last_name.meta_key = '_give_donor_billing_last_name'
                LEFT JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_email
                    ON meta_donor.donation_id = meta_email.donation_id
                    AND meta_email.meta_key = '_give_payment_donor_email'
                LEFT JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_currency
                    ON meta_donor.donation_id = meta_currency.donation_id
                    AND meta_currency.meta_key = '_give_payment_currency'
                LEFT JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_recurring
                    ON meta_donor.donation_id = meta_recurring.donation_id
                    AND meta_recurring.meta_key = '_give_is_donation_recurring'
                WHERE
                    subs.status = 'active'
                    AND meta_recurring.meta_value = '1'
                    AND (subs.created BETWEEN %s AND %s OR subs.expiration BETWEEN %s AND %s)
                    AND subs.payment_mode = %s
                    AND meta_currency.meta_value = %s
                GROUP BY
                    subs.id
                ",
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $mode_param,
                $currency_param
            )
        );

        $date_key = "{$year_param}-{$month_param}";
        if ($currency_param === 'BRL') {
            $date_key = "{$month_param}-{$year_param}";
        }


        // Start between next month
        $start_next_month = gmdate("Y-m-01 00:00:00", strtotime("+1 month", strtotime($start_date)));
        $end_next_month = gmdate("Y-m-t 23:59:59", strtotime($start_next_month));


        $result_month_amount = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(recurring_amount) as total_amount
                FROM {$wpdb->prefix}give_subscriptions
                WHERE (expiration BETWEEN %s AND %s OR created BETWEEN %s AND %s)",
                $start_next_month,
                $end_next_month,
                $start_next_month,
                $end_next_month
            )
        );
        $total_month_amount = number_format($result_month_amount, 2);


        // Start between initial year and final year
        $start_year = "{$year_param}-01-01 00:00:00";
        $end_year = "{$year_param}-12-31 23:59:59";

        $result_annual_amount = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(recurring_amount) as total_amount
                FROM {$wpdb->prefix}give_subscriptions
                WHERE (expiration BETWEEN %s AND %s OR created BETWEEN %s AND %s)",
                $start_year,
                $end_year,
                $start_year,
                $end_year
            )
        );
        $total_annual_amount = number_format($result_annual_amount, 2);

        // Process the results
        if (!empty($results)) {
            foreach ($results as $result) {
                $created_date = \DateTime::createFromFormat('Y-m-d H:i:s', $result->created);
                $result->day = $created_date ? $created_date->format('d') : null;

                $data_grouped['total'] += $result->recurring_amount;
                $data_grouped['donations'][] = $result;
            }
            $data_grouped['date'] = $date_key;
            $data_grouped['next_month_total'] = (float) $total_month_amount;
            $data_grouped['annual_total'] = (float) $total_annual_amount;

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
