<?php

namespace Lkn\RecurrencyGive\Includes;

use Lkn\RecurrencyGive\Admin\LknRecurrencyGiveAdmin;
use Lkn\RecurrencyGive\PublicView\LknRecurrencyGivePublic;
use Give\Donations\Models\Donation;
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

        register_rest_route('lkn-recurrency/v1', '/update/', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_content')
        ));

        register_rest_route('lkn-recurrency/v1', '/verify/', array(
            'methods' => 'POST',
            'callback' => array($this, 'verify_content')
        ));
    }

    public function verify_content()
    {
        global $wpdb;

        // Query to get subscription_ids from meta
        $results = $wpdb->get_results(
            "
                SELECT DISTINCT dm1.donation_id,
                                dm3.meta_value AS subscription_id,
                                dm2.meta_value AS give_cielo_response
                FROM {$wpdb->prefix}give_donationmeta AS dm1
                INNER JOIN {$wpdb->prefix}give_donationmeta AS dm2
                    ON dm1.donation_id = dm2.donation_id
                LEFT JOIN {$wpdb->prefix}give_donationmeta AS dm3
                    ON dm1.donation_id = dm3.donation_id
                    AND dm3.meta_key = 'subscription_id'
                WHERE dm1.meta_key = '_give_is_donation_recurring'
                AND dm1.meta_value = '1'
                AND dm2.meta_key = 'give_cielo_response'
            "
        );

        if (!empty($results)) {
            foreach ($results as $result) {
                // Decode the give_cielo_response value
                $give_cielo_response = json_decode($result->give_cielo_response, true);

                // Check if the 'subscription_id' field exists in the JSON
                if (isset($give_cielo_response['subscription_id'])) {
                    $subscription_id = $give_cielo_response['subscription_id'];
                    $donation_id = $result->donation_id;

                    // Query to get the expiration date
                    $expiration_date = $wpdb->get_var(
                        $wpdb->prepare(
                            "
                                SELECT expiration
                                FROM {$wpdb->prefix}give_subscriptions
                                WHERE id = %d
                            ",
                            $subscription_id
                        )
                    );

                    // Part 1: If subscription_id is 0, update the subscription_id meta
                    if ($result->subscription_id == '0') {
                        return new WP_REST_Response([
                            'status' => true,
                            'message' => __("No subscription found.", 'lkn-recurrency-give'),
                        ], 200);
                    }

                    // Part 2: If expiration date is less than current date, update the expiration date
                    if ($expiration_date) {
                        // Convert the expiration date to a DateTime object
                        $expiration_datetime = new \DateTime($expiration_date);
                        $current_datetime = new \DateTime(); // Current date and time

                        // Compare the expiration date with the current date
                        if ($expiration_datetime <= $current_datetime) {
                            return new WP_REST_Response([
                                'status' => true,
                                'message' => __("No subscription found.", 'lkn-recurrency-give'),
                            ], 200);
                        }
                    }
                }
            }
        }

        return new WP_REST_Response([
            'status' => false,
            'message' => __("No recurring subscriptions found.", 'lkn-recurrency-give'),
        ], 200);
    }

    public function update_content()
    {
        global $wpdb;

        // Query to fetch results
        $results = $wpdb->get_results(
            "
                SELECT DISTINCT dm1.donation_id,
                                dm3.meta_value AS subscription_id,
                                dm2.meta_value AS give_cielo_response
                FROM {$wpdb->prefix}give_donationmeta AS dm1
                INNER JOIN {$wpdb->prefix}give_donationmeta AS dm2
                    ON dm1.donation_id = dm2.donation_id
                LEFT JOIN {$wpdb->prefix}give_donationmeta AS dm3
                    ON dm1.donation_id = dm3.donation_id
                    AND dm3.meta_key = 'subscription_id'
                WHERE dm1.meta_key = '_give_is_donation_recurring'
                AND dm1.meta_value = '1'
                AND dm2.meta_key = 'give_cielo_response'
            "
        );

        $updated_count = 0;

        if (!empty($results)) {
            foreach ($results as $result) {
                // Decode the give_cielo_response value
                $give_cielo_response = json_decode($result->give_cielo_response, true);

                // Check if the 'subscription_id' field exists in the JSON
                if (isset($give_cielo_response['subscription_id'])) {
                    $subscription_id = $give_cielo_response['subscription_id'];
                    $donation_id = $result->donation_id;

                    // Query to get the expiration date
                    $expiration_date = $wpdb->get_var(
                        $wpdb->prepare(
                            "
                                SELECT expiration
                                FROM {$wpdb->prefix}give_subscriptions
                                WHERE id = %d
                            ",
                            $subscription_id
                        )
                    );

                    // Part 1: If subscription_id is 0, update the subscription_id meta
                    if ($result->subscription_id == '0') {
                        $updated_meta_key = $wpdb->query(
                            $wpdb->prepare(
                                "UPDATE {$wpdb->prefix}give_donationmeta SET meta_value = %s WHERE donation_id = %d AND meta_key = %s",
                                $subscription_id,
                                $donation_id,
                                'subscription_id'
                            )
                        );

                        if ($updated_meta_key !== false) {
                            $updated_count++;
                        }
                    }

                    // Part 2: If expiration date is less than current date, update the expiration date
                    if ($expiration_date) {
                        // Convert the expiration date to a DateTime object
                        $expiration_datetime = new \DateTime($expiration_date);
                        $current_datetime = new \DateTime(); // Current date and time

                        // Compare the expiration date with the current date
                        if ($expiration_datetime <= $current_datetime) {
                            // Calculate the difference in months to adjust the expiration
                            $months_to_add = ($current_datetime->format('Y') - $expiration_datetime->format('Y')) * 12;
                            $months_to_add += ($current_datetime->format('m') - $expiration_datetime->format('m')) + 1;

                            // Get the potential new month and year after adding months
                            $new_month = (int) $expiration_datetime->format('m') + $months_to_add;
                            $new_year = (int) $expiration_datetime->format('Y');

                            // Adjust year and month if the new month exceeds 12
                            while ($new_month > 12) {
                                $new_month -= 12;
                                $new_year++;
                            }

                            // Check if the new month is February and the current day exceeds 28/29
                            if ($new_month === 2) {
                                // Determine if the new year is a leap year
                                $is_leap_year = ($new_year % 4 === 0 && $new_year % 100 !== 0) || ($new_year % 400 === 0);
                                $last_day_of_february = $is_leap_year ? 29 : 28;

                                // If the current day exceeds the last day of February, adjust it
                                if ((int) $expiration_datetime->format('d') > $last_day_of_february) {
                                    $expiration_datetime->setDate($new_year, $new_month, $last_day_of_february);
                                } else {
                                    $expiration_datetime->setDate($new_year, $new_month, (int) $expiration_datetime->format('d'));
                                }
                            } else {
                                // For other months, ensure the day doesn't exceed the last day of the month
                                $last_day_of_month = cal_days_in_month(CAL_GREGORIAN, $new_month, $new_year);

                                if ((int) $expiration_datetime->format('d') > $last_day_of_month) {
                                    $expiration_datetime->setDate($new_year, $new_month, $last_day_of_month);
                                } else {
                                    $expiration_datetime->setDate($new_year, $new_month, (int) $expiration_datetime->format('d'));
                                }
                            }

                            // Update the expiration date in the give_subscriptions table
                            $new_expiration_date = $expiration_datetime->format('Y-m-d H:i:s'); // Format for MySQL
                            $updated_expiration = $wpdb->query(
                                $wpdb->prepare(
                                    "UPDATE {$wpdb->prefix}give_subscriptions SET expiration = %s WHERE id = %d",
                                    $new_expiration_date,
                                    $subscription_id
                                )
                            );

                            // Check if the expiration date update was successful
                            if ($updated_expiration !== false) {
                                $updated_count++;
                            }
                        }
                    }
                }
            }
        }

        // Return response based on whether updates were made
        if ($updated_count > 0) {
            return new WP_REST_Response([
                'status' => true,
                'message' => __('Subscriptions updated successfully.', 'lkn-recurrency-give'),
            ], 200);
        } else {
            return new WP_REST_Response([
                'status' => false,
                'message' => __('No subscriptions require updates.', 'lkn-recurrency-give'),
            ], 200);
        }

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

        $subscriptions_results = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT DISTINCT
                    meta_donor.donation_id,
                    meta_billing_first_name.meta_value AS billing_first_name,
                    meta_billing_last_name.meta_value AS billing_last_name,
                    meta_email.meta_value AS donor_email,
                    meta_currency.meta_value AS payment_currency,
                    meta_recurring.meta_value AS is_recurring,
                    subs.id AS subscription_id,
                    subs.customer_id,
                    subs.frequency,
                    subs.recurring_amount,
                    subs.payment_mode,
                    subs.created,
                    subs.expiration,
                    subs.profile_id
                FROM
                    {$wpdb->prefix}give_subscriptions AS subs
                INNER JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_donor
                    ON subs.id = meta_donor.meta_value
                    AND meta_donor.meta_key = 'subscription_id'
                INNER JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_billing_first_name
                    ON meta_donor.donation_id = meta_billing_first_name.donation_id
                    AND meta_billing_first_name.meta_key = '_give_donor_billing_first_name'
                INNER JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_billing_last_name
                    ON meta_donor.donation_id = meta_billing_last_name.donation_id
                    AND meta_billing_last_name.meta_key = '_give_donor_billing_last_name'
                INNER JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_email
                    ON meta_donor.donation_id = meta_email.donation_id
                    AND meta_email.meta_key = '_give_payment_donor_email'
                INNER JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_currency
                    ON meta_donor.donation_id = meta_currency.donation_id
                    AND meta_currency.meta_key = '_give_payment_currency'
                INNER JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_recurring
                    ON meta_donor.donation_id = meta_recurring.donation_id
                    AND meta_recurring.meta_key = '_give_is_donation_recurring'
                INNER JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_completed_date
                    ON meta_donor.donation_id = meta_completed_date.donation_id
                    AND meta_completed_date.meta_key = '_give_completed_date'
                WHERE
                    subs.status = 'active'
                    AND meta_recurring.meta_value = '1'
                    AND (%s BETWEEN subs.created AND subs.expiration OR %s BETWEEN subs.created AND subs.expiration)
                    AND subs.payment_mode = %s
                    AND meta_currency.meta_value = %s
                ",
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
                "
                SELECT SUM(subs.recurring_amount) AS total_amount
                FROM
                    {$wpdb->prefix}give_subscriptions AS subs
                INNER JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_donor
                    ON subs.id = meta_donor.meta_value
                    AND meta_donor.meta_key = 'subscription_id'
                WHERE
                    (%s BETWEEN subs.created AND subs.expiration OR %s BETWEEN subs.created AND subs.expiration)
                    AND subs.status = 'active'
                ",
                $start_next_month,
                $end_next_month
            )
        );
        $total_month_amount = number_format($result_month_amount, 2);


        // Start between initial year and final year
        $start_year = "{$year_param}-01-01 00:00:00";
        $end_year = "{$year_param}-12-31 23:59:59";
        $total_annual_amount = 0;

        $annual_results = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    subs.recurring_amount,
                    subs.created,
                    subs.expiration
                FROM
                    {$wpdb->prefix}give_subscriptions AS subs
                INNER JOIN
                    {$wpdb->prefix}give_donationmeta AS meta_donor
                    ON subs.id = meta_donor.meta_value
                    AND meta_donor.meta_key = 'subscription_id'
                WHERE
                    (subs.expiration BETWEEN %s AND %s OR subs.created BETWEEN %s AND %s)
                    AND subs.status = 'active'
                ",
                $start_year,
                $end_year,
                $start_year,
                $end_year
            )
        );

        foreach ($annual_results as $result) {
            $created_date = new \DateTime($result->created);
            $expiration_date = new \DateTime($result->expiration);

            // Definir o início e o fim do ano corrente
            $start_of_year = new \DateTime($start_year);
            $end_of_year = new \DateTime($end_year);

            // Ajustar as datas de criação e expiração para ficarem dentro do intervalo
            $effective_start = max($created_date, $start_of_year);
            $effective_end = min($expiration_date, $end_of_year);

            // Calcular os meses válidos apenas no intervalo
            if ($effective_start <= $effective_end) {
                $interval = $effective_start->diff($effective_end);
                $months_in_period = ($interval->y * 12) + $interval->m + 1;

                // Adicionar o valor ao total anual
                $total_annual_amount += $result->recurring_amount * $months_in_period;
            }
        }

        // Formatar o valor total
        $total_annual_amount = number_format($total_annual_amount, 2);

        // Process the results
        if (count($subscriptions_results) > 0) {
            foreach ($subscriptions_results as $result) {
                $created_date = \DateTime::createFromFormat('Y-m-d H:i:s', $result->created);
                $result->day = $created_date ? $created_date->format('d') : null;

                $data_grouped['total'] += $result->recurring_amount;
                $data_grouped['donations'][] = $result;
            }
            $data_grouped['date'] = $date_key;
            $data_grouped['next_month_total'] = $total_month_amount;
            $data_grouped['annual_total'] = $total_annual_amount;

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
