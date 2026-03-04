<?php
/**
 * Template para listagem de veículos por termo (ex.: /marca_veiculo/chevrolet/).
 * Exibe: imagem, título, ano, tipo de câmbio, valor.
 *
 * @package CDW\Veiculos
 */

if (!defined('ABSPATH')) {
    exit;
}

use CDW\Veiculos\CPT;
use CDW\Veiculos\Sync;

$term = get_queried_object();
if (!$term instanceof \WP_Term) {
    return;
}

get_header();
?>

<style>
.cdw-veiculos-list-term { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.5rem; }
.cdw-veiculo-card .cdw-veiculo-card-link { display: block; text-decoration: none; color: inherit; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
.cdw-veiculo-thumb-wrap { display: block; aspect-ratio: 16/10; background: #f0f0f0; }
.cdw-veiculo-thumb { width: 100%; height: 100%; object-fit: cover; }
.cdw-veiculo-card .cdw-veiculo-title { display: block; font-weight: 600; padding: 0.5rem 0.75rem 0; }
.cdw-veiculo-card .cdw-veiculo-meta { display: block; padding: 0 0.75rem; font-size: 0.9em; }
.cdw-veiculo-card .cdw-veiculo-preco { display: block; padding: 0.5rem 0.75rem; font-weight: 700; color: #0a0; }
</style>

<div class="cdw-veiculos-term-archive">
    <header class="cdw-veiculos-term-header">
        <h1 class="page-title"><?php echo esc_html($term->name); ?></h1>
        <p class="cdw-veiculos-term-count"><?php echo (int) $term->count; ?> <?php echo (int) $term->count === 1 ? __('veículo', 'cdw-veiculos') : __('veículos', 'cdw-veiculos'); ?></p>
    </header>

    <?php if (have_posts()): ?>
        <ul class="cdw-veiculos-list cdw-veiculos-list-term">
            <?php
            while (have_posts()) {
                the_post();
                $post_id = get_the_ID();
                $preco   = get_post_meta($post_id, Sync::META_PRECO, true);
                $ano     = get_post_meta($post_id, Sync::META_ANO_MOD, true);
                $imagens = get_post_meta($post_id, Sync::META_IMAGENS, true);
                if (!is_array($imagens)) {
                    $imagens = [];
                }
                $thumb = !empty($imagens[0]) ? $imagens[0] : '';
                $cambio_terms = get_the_terms($post_id, CPT::TAX_CAMBIO);
                $cambio_name  = '';
                if ($cambio_terms && !is_wp_error($cambio_terms) && !empty($cambio_terms)) {
                    $cambio_name = $cambio_terms[0]->name;
                }
                ?>
                <li class="cdw-veiculo-item cdw-veiculo-card">
                    <a href="<?php the_permalink(); ?>" class="cdw-veiculo-card-link">
                        <?php if ($thumb): ?>
                            <span class="cdw-veiculo-thumb-wrap">
                                <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(['echo' => false]); ?>" loading="lazy" class="cdw-veiculo-thumb" />
                            </span>
                        <?php endif; ?>
                        <span class="cdw-veiculo-title"><?php the_title(); ?></span>
                        <?php if ($ano): ?>
                            <span class="cdw-veiculo-meta cdw-veiculo-ano"><strong><?php esc_html_e('Ano:', 'cdw-veiculos'); ?></strong> <?php echo esc_html((string) $ano); ?></span>
                        <?php endif; ?>
                        <?php if ($cambio_name !== ''): ?>
                            <span class="cdw-veiculo-meta cdw-veiculo-cambio"><strong><?php esc_html_e('Câmbio:', 'cdw-veiculos'); ?></strong> <?php echo esc_html($cambio_name); ?></span>
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
        <p class="cdw-veiculos-empty"><?php esc_html_e('Nenhum veículo publicado nesta categoria.', 'cdw-veiculos'); ?></p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
