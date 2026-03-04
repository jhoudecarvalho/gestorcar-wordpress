<?php
/**
 * Histórico de sincronizações (tabela wp_cdw_veiculos_logs).
 *
 * @package CDW\Veiculos
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'cdw_veiculos_logs';
$per_page = 20;
$paged = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
$offset = ($paged - 1) * $per_page;
$total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
$items = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id, sincronizado_em, total, criados, atualizados, deletados, erros, mensagem FROM {$table} ORDER BY sincronizado_em DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ),
    ARRAY_A
);
$total_pages = $per_page > 0 ? (int) ceil($total / $per_page) : 1;
?>

<div class="wrap">
    <h1><?php esc_html_e('Logs de sincronização — CDW Veículos', 'cdw-veiculos'); ?></h1>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Data/Hora', 'cdw-veiculos'); ?></th>
                <th><?php esc_html_e('Total', 'cdw-veiculos'); ?></th>
                <th><?php esc_html_e('Criados', 'cdw-veiculos'); ?></th>
                <th><?php esc_html_e('Atualizados', 'cdw-veiculos'); ?></th>
                <th><?php esc_html_e('Deletados', 'cdw-veiculos'); ?></th>
                <th><?php esc_html_e('Erros', 'cdw-veiculos'); ?></th>
                <th><?php esc_html_e('Mensagem', 'cdw-veiculos'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="7"><?php esc_html_e('Nenhum registro ainda.', 'cdw-veiculos'); ?></td></tr>
            <?php else: ?>
                <?php foreach ($items as $row): ?>
                    <tr>
                        <td><?php echo esc_html($row['sincronizado_em'] ?? ''); ?></td>
                        <td><?php echo esc_html($row['total'] ?? 0); ?></td>
                        <td><?php echo esc_html($row['criados'] ?? 0); ?></td>
                        <td><?php echo esc_html($row['atualizados'] ?? 0); ?></td>
                        <td><?php echo esc_html($row['deletados'] ?? 0); ?></td>
                        <td><?php echo esc_html($row['erros'] ?? 0); ?></td>
                        <td><?php echo esc_html($row['mensagem'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <span class="displaying-num"><?php echo esc_html(sprintf(/* translators: %d: count */ _n('%d item', '%d itens', $total, 'cdw-veiculos'), $total)); ?></span>
            <span class="pagination-links">
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $paged,
                ]);
                ?>
            </span>
        </div>
    <?php endif; ?>
</div>
