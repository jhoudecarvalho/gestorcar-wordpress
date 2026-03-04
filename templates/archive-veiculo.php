<?php
/**
 * Template do plugin para listagem de veículos (archive).
 * Lista apenas veículos publicados.
 *
 * @package CDW\Veiculos
 */

if (!defined('ABSPATH')) {
    exit;
}

use CDW\Veiculos\CPT;
use CDW\Veiculos\Sync;

get_header();

$marcas = get_terms([
    'taxonomy'   => CPT::TAX_MARCA,
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);
?>

<div class="cdw-veiculos-archive">
    <header class="cdw-veiculos-archive-header">
        <h1 class="page-title"><?php post_type_archive_title(); ?></h1>

        <?php if (!empty($marcas) && !is_wp_error($marcas)): ?>
            <nav class="cdw-marcas-list" aria-label="<?php esc_attr_e('Filtrar por marca', 'cdw-veiculos'); ?>">
                <span class="cdw-marcas-label"><?php esc_html_e('Marcas:', 'cdw-veiculos'); ?></span>
                <ul>
                    <li><a href="<?php echo esc_url(get_post_type_archive_link(CPT::POST_TYPE)); ?>"><?php esc_html_e('Todas', 'cdw-veiculos'); ?></a></li>
                    <?php foreach ($marcas as $term): ?>
                        <li><a href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a> (<?php echo (int) $term->count; ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </header>

    <?php if (have_posts()): ?>
        <ul class="cdw-veiculos-list">
            <?php
            while (have_posts()) {
                the_post();
                $post_id = get_the_ID();
                $preco  = get_post_meta($post_id, Sync::META_PRECO, true);
                $ano    = get_post_meta($post_id, Sync::META_ANO_MOD, true);
                $imagens = get_post_meta($post_id, Sync::META_IMAGENS, true);
                if (!is_array($imagens)) {
                    $imagens = [];
                }
                $thumb = !empty($imagens[0]) ? $imagens[0] : '';
                ?>
                <li class="cdw-veiculo-item">
                    <a href="<?php the_permalink(); ?>">
                        <?php if ($thumb): ?>
                            <img src="<?php echo esc_url($thumb); ?>" alt="" loading="lazy" class="cdw-veiculo-thumb" />
                        <?php endif; ?>
                        <span class="cdw-veiculo-title"><?php the_title(); ?></span>
                        <?php if ($ano): ?>
                            <span class="cdw-veiculo-ano"><?php echo esc_html((string) $ano); ?></span>
                        <?php endif; ?>
                        <?php if ($preco !== '' && $preco !== null): ?>
                            <span class="cdw-veiculo-preco">R$ <?php echo esc_html(number_format((float) $preco, 2, ',', '.')); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php } ?>
        </ul>

        <?php
        the_posts_pagination([
            'mid_size'  => 2,
            'prev_text' => __('Anterior', 'cdw-veiculos'),
            'next_text' => __('Próximo', 'cdw-veiculos'),
        ]);
        ?>
    <?php else: ?>
        <p class="cdw-veiculos-empty"><?php esc_html_e('Nenhum veículo publicado encontrado.', 'cdw-veiculos'); ?></p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
