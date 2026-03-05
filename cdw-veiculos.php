<?php
/**
 * Plugin Name:       CDW Veículos
 * Plugin URI:        https://github.com/jhoudecarvalho/gestorcar-wordpress
 * Description:       Sincroniza veículos a partir da view crm_vehicles_v (GestorCar). Cria/atualiza/remove posts automaticamente; lista marcas, categorias, câmbio e combustível.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            CDW
 * License:           GPL-2.0-or-later
 * Text Domain:       cdw-veiculos
 */

declare(strict_types=1);

namespace CDW\Veiculos;

if (!defined('ABSPATH')) {
    exit;
}

define('CDW_VEICULOS_VERSION', '1.1.0');
define('CDW_VEICULOS_PLUGIN_FILE', __FILE__);
define('CDW_VEICULOS_PATH', plugin_dir_path(__FILE__));
define('CDW_VEICULOS_URL', plugin_dir_url(__FILE__));

/**
 * Verificação de requisitos mínimos.
 */
add_action('admin_init', function (): void {
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        add_action('admin_notices', function (): void {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('CDW Veículos requer PHP 8.0 ou superior.', 'cdw-veiculos');
            echo '</p></div>';
        });
        deactivate_plugins(plugin_basename(CDW_VEICULOS_PLUGIN_FILE));
        return;
    }
    if (version_compare((string) get_bloginfo('version'), '6.0', '<')) {
        add_action('admin_notices', function (): void {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('CDW Veículos requer WordPress 6.0 ou superior.', 'cdw-veiculos');
            echo '</p></div>';
        });
        deactivate_plugins(plugin_basename(CDW_VEICULOS_PLUGIN_FILE));
        return;
    }
});

require_once CDW_VEICULOS_PATH . 'includes/class-database.php';
require_once CDW_VEICULOS_PATH . 'includes/class-cpt.php';
require_once CDW_VEICULOS_PATH . 'includes/class-sync.php';
require_once CDW_VEICULOS_PATH . 'includes/class-scheduler.php';
require_once CDW_VEICULOS_PATH . 'includes/class-tracker.php';
require_once CDW_VEICULOS_PATH . 'includes/class-rest-api.php';
require_once CDW_VEICULOS_PATH . 'admin/class-admin.php';

/**
 * Inicialização do plugin (após carregar todos os arquivos).
 */
add_action('plugins_loaded', function (): void {
    load_plugin_textdomain('cdw-veiculos', false, dirname(plugin_basename(CDW_VEICULOS_PLUGIN_FILE)) . '/languages');
    CPT::get_instance()->register();
    Scheduler::get_instance()->init();
    Tracker::init();
    Rest_Api::init();
    if (is_admin()) {
        Admin::get_instance()->init();
    }
});

/**
 * Ativação: cria tabela de logs e agenda o cron.
 */
register_activation_hook(CDW_VEICULOS_PLUGIN_FILE, function (): void {
    global $wpdb;
    $table = $wpdb->prefix . 'cdw_veiculos_logs';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        sincronizado_em datetime NOT NULL,
        total int(11) NOT NULL DEFAULT 0,
        criados int(11) NOT NULL DEFAULT 0,
        atualizados int(11) NOT NULL DEFAULT 0,
        deletados int(11) NOT NULL DEFAULT 0,
        erros int(11) NOT NULL DEFAULT 0,
        mensagem text DEFAULT NULL,
        PRIMARY KEY (id),
        KEY sincronizado_em (sincronizado_em)
    ) {$charset};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    Scheduler::get_instance()->schedule();
    flush_rewrite_rules();
});

/**
 * Desativação: remove o agendamento do cron.
 */
register_deactivation_hook(CDW_VEICULOS_PLUGIN_FILE, function (): void {
    Scheduler::get_instance()->unschedule();
});
