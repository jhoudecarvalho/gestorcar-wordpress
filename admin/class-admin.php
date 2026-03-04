<?php
/**
 * Painel administrativo do CDW Veículos.
 *
 * @package CDW\Veiculos
 */

declare(strict_types=1);

namespace CDW\Veiculos;

final class Admin {

    private const SLUG = 'cdw-veiculos';
    private const CAPABILITY = 'manage_options';

    private static ?self $instance = null;

    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'handle_actions']);
        add_filter('manage_veiculo_posts_columns', [$this, 'add_cliques_column']);
        add_action('manage_veiculo_posts_custom_column', [$this, 'render_cliques_column'], 10, 2);
        add_filter('manage_edit-veiculo_sortable_columns', [$this, 'sortable_cliques_column']);
        add_action('pre_get_posts', [$this, 'orderby_cliques']);
    }

    public function add_cliques_column(array $columns): array {
        $new = [];
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'taxonomy-acessorio_veiculo') {
                $new['cliques'] = '<span title="' . esc_attr__('Total de visualizações', 'cdw-veiculos') . '">👁 ' . esc_html__('Cliques', 'cdw-veiculos') . '</span>';
            }
        }
        if (!isset($new['cliques'])) {
            $new['cliques'] = '<span title="' . esc_attr__('Total de visualizações', 'cdw-veiculos') . '">👁 ' . esc_html__('Cliques', 'cdw-veiculos') . '</span>';
        }
        return $new;
    }

    public function render_cliques_column(string $column, int $post_id): void {
        if ($column !== 'cliques') {
            return;
        }
        $total = (int) get_post_meta($post_id, Tracker::META_CLIQUES, true);
        $color = match (true) {
            $total >= 100 => '#1a7f37',
            $total >= 50  => '#2da44e',
            $total >= 20  => '#e36209',
            $total >= 1   => '#0969da',
            default      => '#6e7781',
        };
        printf(
            '<strong style="color:%s;font-size:14px;">%s</strong>',
            esc_attr($color),
            esc_html(number_format($total, 0, ',', '.'))
        );
    }

    public function sortable_cliques_column(array $columns): array {
        $columns['cliques'] = 'cliques';
        return $columns;
    }

    public function orderby_cliques(\WP_Query $query): void {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        if ($query->get('orderby') !== 'cliques') {
            return;
        }
        $query->set('meta_key', Tracker::META_CLIQUES);
        $query->set('orderby', 'meta_value_num');
    }

    public function add_menu(): void {
        add_menu_page(
            __('CDW Veículos', 'cdw-veiculos'),
            __('CDW Veículos', 'cdw-veiculos'),
            self::CAPABILITY,
            self::SLUG,
            [$this, 'render_settings'],
            'dashicons-car',
            26
        );
        add_submenu_page(
            self::SLUG,
            __('Configurações', 'cdw-veiculos'),
            __('Configurações', 'cdw-veiculos'),
            self::CAPABILITY,
            self::SLUG,
            [$this, 'render_settings']
        );
        add_submenu_page(
            self::SLUG,
            __('Mapeamento', 'cdw-veiculos'),
            __('Mapeamento', 'cdw-veiculos'),
            self::CAPABILITY,
            self::SLUG . '-mapping',
            [$this, 'render_mapping']
        );
        add_submenu_page(
            self::SLUG,
            __('Logs', 'cdw-veiculos'),
            __('Logs', 'cdw-veiculos'),
            self::CAPABILITY,
            self::SLUG . '-logs',
            [$this, 'render_logs']
        );
    }

    public function handle_actions(): void {
        if (!isset($_GET['page']) || strpos((string) $_GET['page'], self::SLUG) !== 0) {
            return;
        }
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }
        if (isset($_POST['cdw_test_connection']) && isset($_POST['_wpnonce'])) {
            check_admin_referer('cdw_veiculos_settings');
            $result = Database::get_instance()->test_connection();
            $key = $result['success'] ? 'cdw_success' : 'cdw_error';
            wp_safe_redirect(add_query_arg($key, rawurlencode($result['message']), wp_get_referer()));
            exit;
        }
        if (isset($_POST['cdw_sync_now']) && isset($_POST['_wpnonce'])) {
            check_admin_referer('cdw_veiculos_settings');
            Sync::get_instance()->run();
            wp_safe_redirect(add_query_arg('cdw_sync_done', '1', wp_get_referer()));
            exit;
        }
        if (isset($_POST['cdw_save_settings']) && isset($_POST['_wpnonce'])) {
            check_admin_referer('cdw_veiculos_settings');
            $this->save_settings();
            $freq = sanitize_text_field($_POST['cdw_cron_frequency'] ?? 'cdw_1h');
            update_option(Scheduler::option_frequency(), $freq);
            Scheduler::get_instance()->schedule();
            wp_safe_redirect(add_query_arg('cdw_saved', '1', wp_get_referer()));
            exit;
        }
    }

    private function save_settings(): void {
        update_option(Database::option_host(), sanitize_text_field($_POST['cdw_db_host'] ?? ''));
        update_option(Database::option_port(), absint($_POST['cdw_db_port'] ?? 3306) ?: 3306);
        update_option(Database::option_name(), sanitize_text_field($_POST['cdw_db_name'] ?? ''));
        update_option(Database::option_user(), sanitize_text_field($_POST['cdw_db_user'] ?? ''));
        $pass = $_POST['cdw_db_password'] ?? '';
        if ($pass !== '') {
            update_option(Database::option_password(), $pass);
        }
        update_option(Database::option_view(), sanitize_text_field($_POST['cdw_db_view'] ?? 'crm_vehicles_v'));
        update_option(Database::option_images_base_url(), esc_url_raw(trim((string) ($_POST['cdw_images_base_url'] ?? ''))));
        update_option(Database::option_images_use_db(), isset($_POST['cdw_images_use_db']) ? '1' : '0');
        update_option(Database::option_images_include_domain_path(), isset($_POST['cdw_images_include_domain_path']) ? '1' : '0');
        $company_id = isset($_POST['cdw_company_id']) ? sanitize_text_field($_POST['cdw_company_id']) : '';
        update_option(Database::option_company_id(), $company_id === '' || $company_id === '0' ? '' : (string) absint($company_id));
    }

    public function render_settings(): void {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('Sem permissão.', 'cdw-veiculos'));
        }
        if (isset($_GET['cdw_saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Configurações salvas.', 'cdw-veiculos') . '</p></div>';
        }
        if (isset($_GET['cdw_sync_done'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Sincronização executada.', 'cdw-veiculos') . '</p></div>';
        }
        if (!empty($_GET['cdw_success'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(rawurldecode((string) $_GET['cdw_success'])) . '</p></div>';
        }
        if (!empty($_GET['cdw_error'])) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(rawurldecode((string) $_GET['cdw_error'])) . '</p></div>';
        }
        include CDW_VEICULOS_PATH . 'admin/views/settings.php';
    }

    public function render_mapping(): void {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('Sem permissão.', 'cdw-veiculos'));
        }
        include CDW_VEICULOS_PATH . 'admin/views/mapping.php';
    }

    public function render_logs(): void {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('Sem permissão.', 'cdw-veiculos'));
        }
        include CDW_VEICULOS_PATH . 'admin/views/logs.php';
    }
}
