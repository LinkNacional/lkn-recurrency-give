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

    }

    public function lkn_recurrency_settings_page()
    {
        add_menu_page(
            'Lkn Recurrency',          // Título da página
            'Lkn Recurrency',          // Nome do menu
            'manage_options',          // Permissão necessária
            'lkn-recurrency',          // Slug único
            array($this, 'lkn_recurrency_render_page'), // Função de renderização
            'dashicons-list-view',     // Ícone
            25                         // Posição no menu
        );
    }

    public function lkn_recurrency_render_page()
    {
        ?>
<div class="wrap">
    <h1><?php esc_html_e('Lkn Recurrency', 'lkn-plugin'); ?>
    </h1>
    <div id="lkn-recurrency-data">
        <p><?php esc_html_e('Carregando dados de pagamentos recorrentes...', 'lkn-plugin'); ?>
        </p>
    </div>
    <div
        id="lkn-select-container"
        style="display: none;"
    >
        <label for="month-select">Selecione o mês:</label>
        <select id="month-select">
            <option value="01">Janeiro</option>
            <option value="02">Fevereiro</option>
            <option value="03">Março</option>
            <option value="04">Abril</option>
            <option value="05">Maio</option>
            <option value="06">Junho</option>
            <option value="07">Julho</option>
            <option value="08">Agosto</option>
            <option value="09">Setembro</option>
            <option value="10">Outubro</option>
            <option value="11">Novembro</option>
            <option value="12">Dezembro</option>
        </select>

        <label for="year-select">Selecione o ano:</label>
        <select id="year-select">
            <?php for ($year = 2020; $year <= 2030; $year++) : ?>
            <option value="<?php echo $year; ?>">
                <?php echo $year; ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="chart-container">
        <canvas id="recurrencyChart"></canvas>
    </div>
</div>

<!-- Incluindo Chart.js e o adaptador para datas -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const apiUrlBase =
            '<?php echo admin_url('admin-ajax.php?action=lkn_get_recurrency_data'); ?>';
        const monthSelect = document.getElementById('month-select');
        const yearSelect = document.getElementById('year-select');
        let chartInstance = null;

        function fetchDataAndRenderChart() {
            const selectedMonth = monthSelect.value;
            const selectedYear = yearSelect.value;

            fetch(`${apiUrlBase}&month=${selectedMonth}&year=${selectedYear}`)
                .then(response => response.json())
                .then(responseData => {
                    if (!responseData.success) {
                        throw new Error(responseData.data?.message || 'Erro desconhecido');
                    }

                    const data = responseData.data[`${selectedYear}-${selectedMonth}`]; // Dados do backend

                    const container = document.getElementById('lkn-recurrency-data');

                    let labels = [];
                    let totals = [];

                    const firstDayOfMonth = new Date(selectedYear, selectedMonth - 1, 1);
                    let donationDate = new Date();

                    if (data) {
                        const totalDiv = document.createElement('div');
                        totalDiv.innerHTML = `<strong>Total do mês:</strong> R$ ${data.total.toFixed(2)}`;
                        container.innerHTML = ''
                        container.appendChild(totalDiv);

                        labels = data.donations.map(item => {
                            donationDate = new Date(item.completed_date);
                            donationDate.setDate(firstDayOfMonth.getDate());
                            return item.completed_date
                        }); // Datas das recorrências
                        totals = data.donations.map(item => parseFloat(item.total.replace(',',
                        '.'))); // Valores arrecadados

                        labels.unshift(donationDate)
                    } else {
                        container.innerHTML = ''
                    }


                    // Exibe os selects de mês e ano
                    const selectContainer = document.getElementById('lkn-select-container');
                    selectContainer.style.display = 'block';

                    updateChart(labels, totals);
                })
                .catch(error => {
                    console.error('Erro ao carregar os dados:', error);
                });
        }

        function updateChart(labels, data) {
            const ctx = document.getElementById('recurrencyChart').getContext('2d');

            if (chartInstance) {
                chartInstance.destroy(); // Destroi o gráfico anterior para criar um novo
            }

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total arrecadado (R$)',
                        data: data,
                        borderColor: 'rgba(52, 59, 69, 1)',          // Preto (#343b45) na linha do gráfico
                        backgroundColor: 'rgba(211, 216, 0, 0.2)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(52, 59, 69, 1)', // Preto (#343b45) nos pontos
                        pointBorderColor: 'rgba(52, 59, 69, 1)',
                        pointRadius: 6,
                        pointHoverRadius: 8,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                parser: 'yyyy-MM-dd HH:mm:ss',
                                tooltipFormat: 'dd/MM/yyyy HH:mm',
                                unit: 'day',
                                displayFormats: {
                                    day: 'dd/MM/yyyy'
                                }
                            },
                            title: {
                                display: true,
                                text: 'Data'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: Math.max(...data) + 5,
                            title: {
                                display: true,
                                text: 'Valor em R$'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return `R$ ${tooltipItem.raw.toFixed(2)}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Event listeners para os selects
        monthSelect.addEventListener('change', fetchDataAndRenderChart);
        yearSelect.addEventListener('change', fetchDataAndRenderChart);

        // Carrega os dados iniciais
        fetchDataAndRenderChart();
    });
</script>
<?php
    }

    public function lkn_handle_get_recurrency_data()
    {
        // Verifica a permissão do usuário
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissão negada.'], 403);
        }

        global $wpdb;

        // Consulta os donation_id onde o meta_key é "_give_is_donation_recurring" e o meta_value >= 1
        $sql = "
        SELECT DISTINCT dm.donation_id
        FROM {$wpdb->prefix}give_donationmeta dm
        WHERE dm.meta_key = '_give_is_donation_recurring'
        AND dm.meta_value = 1
    ";

        // Executa a consulta e retorna os resultados
        $results = $wpdb->get_results($sql);

        // Verifica se existem resultados
        if (!empty($results)) {
            $donation_ids = array_map(function ($result) {
                return $result->donation_id;
            }, $results);
        } else {
            $donation_ids = [];
        }

        if (!empty($donation_ids)) {
            // Gera uma lista de placeholders para a cláusula IN da consulta SQL
            $placeholders = implode(',', array_fill(0, count($donation_ids), '%d'));

            // Consulta os dados de interesse com base nos donation_ids
            $sql = "
            SELECT dm.donation_id, dm.meta_key, dm.meta_value
            FROM {$wpdb->prefix}give_donationmeta dm
            WHERE dm.donation_id IN ($placeholders)
            AND dm.meta_key IN (
                '_give_payment_total',
                '_give_payment_currency',
                '_give_donor_billing_first_name',
                '_give_donor_billing_last_name',
                '_give_payment_donor_email',
                '_give_payment_mode',
                '_give_completed_date'
            )
        ";

            // Executa a consulta com os donation_ids
            $results = $wpdb->get_results($wpdb->prepare($sql, ...$donation_ids));

            // Organiza os resultados por donation_id
            $donation_data = [];
            foreach ($results as $result) {
                // Se o donation_id ainda não foi adicionado ao array, cria uma entrada para ele
                if (!isset($donation_data[$result->donation_id])) {
                    $donation_data[$result->donation_id] = [];
                }

                // Adiciona o valor para a meta_key correspondente
                $donation_data[$result->donation_id][$result->meta_key] = $result->meta_value;
            }

            // Estrutura os dados para exibição
            $data = [];
            foreach ($donation_data as $donation_id => $donation_info) {
                // Adiciona 1 mês à data de completamento
                $completed_date = isset($donation_info['_give_completed_date']) ? $donation_info['_give_completed_date'] : null;
                $completed_date_plus_month = null;

                if ($completed_date) {
                    $date = new \DateTime($completed_date);
                    $date->modify('+1 month'); // Adiciona 1 mês
                    $completed_date_plus_month = $date->format('Y-m-d H:i:s');
                }

                // Consulta na tabela wp_give_subscriptions usando a data ajustada
                if ($completed_date_plus_month) {
                    $sql_sub = "
                    SELECT period, frequency, profile_id, expiration
                    FROM {$wpdb->prefix}give_subscriptions
                    WHERE expiration = %s
                ";
                    $sub_results = $wpdb->get_results($wpdb->prepare($sql_sub, $completed_date_plus_month, $donation_id));

                    // Obtem os dados de assinatura
                    $subscription_data = [];
                    if (!empty($sub_results)) {
                        $subscription_data = $sub_results[0];
                    }

                    // Adiciona os dados ao array final
                    $data[] = [
                        'donation_id' => $donation_id,
                        'total' => isset($donation_info['_give_payment_total']) ? number_format($donation_info['_give_payment_total'], 2, ',', '.') : 'N/A',
                        'currency' => $donation_info['_give_payment_currency'] ?? 'N/A',
                        'first_name' => $donation_info['_give_donor_billing_first_name'] ?? 'N/A',
                        'last_name' => $donation_info['_give_donor_billing_last_name'] ?? 'N/A',
                        'email' => $donation_info['_give_payment_donor_email'] ?? 'N/A',
                        'payment_mode' => $donation_info['_give_payment_mode'] ?? 'N/A',
                        'completed_date' => $donation_info['_give_completed_date'] ?? 'N/A',
                        'subscription_period' => $subscription_data->period ?? 'N/A',
                        'subscription_frequency' => $subscription_data->frequency ?? 'N/A',
                        'subscription_profile_id' => $subscription_data->profile_id ?? 'N/A',
                        'expiration' => $subscription_data->expiration ?? 'N/A'
                    ];
                }
            }

            $data_grouped = [];
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
            wp_send_json_error(['message' => 'Nenhum donation_id encontrado.']);
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
?>