<?php
/**
 * Template genérico para listar todos os termos de uma taxonomia.
 * Usado em: /marca_veiculo/, /categoria_veiculo/, /cambio_veiculo/, /combustivel_veiculo/
 *
 * @package CDW\Veiculos
 */

if (!defined('ABSPATH')) {
    exit;
}

use CDW\Veiculos\CPT;

$taxonomy = get_query_var(CPT::QUERY_VAR_LISTA_TAX);
if (!$taxonomy || !in_array($taxonomy, CPT::TAXONOMIES_COM_LISTA, true)) {
    return;
}

$titles = CPT::get_lista_taxonomy_titles();
$page_title = $titles[$taxonomy] ?? $taxonomy;

$terms = get_terms([
    'taxonomy'   => $taxonomy,
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);

get_header();
?>

<div class="cdw-tax-lista cdw-tax-lista-<?php echo esc_attr(str_replace('_', '-', $taxonomy)); ?>">
    <header class="cdw-tax-lista-header">
        <h1 class="page-title"><?php echo esc_html($page_title); ?></h1>
    </header>

    <?php if (!empty($terms) && !is_wp_error($terms)): ?>
        <ul class="cdw-tax-grid">
            <?php foreach ($terms as $term): ?>
                <li class="cdw-tax-item">
                    <a href="<?php echo esc_url(get_term_link($term)); ?>">
                        <span class="cdw-tax-name"><?php echo esc_html($term->name); ?></span>
                        <span class="cdw-tax-count">(<?php echo (int) $term->count; ?> <?php echo (int) $term->count === 1 ? __('veículo', 'cdw-veiculos') : __('veículos', 'cdw-veiculos'); ?>)</span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="cdw-tax-empty"><?php esc_html_e('Nenhum item encontrado. Execute a sincronização em CDW Veículos → Configurações.', 'cdw-veiculos'); ?></p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
