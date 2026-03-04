<?php
/**
 * Template para exibição de um único veículo (CPT veiculo).
 * Usado pelo plugin quando o tema não possui single-veiculo.php.
 *
 * @package CDW\Veiculos
 */

if (!defined('ABSPATH')) {
    exit;
}

use CDW\Veiculos\CPT;
use CDW\Veiculos\Sync;
use CDW\Veiculos\Tracker;

$post_id = get_the_ID();
$preco   = get_post_meta($post_id, Sync::META_PRECO, true);
$km      = get_post_meta($post_id, Sync::META_KM, true);
$ano_fab = get_post_meta($post_id, Sync::META_ANO_FAB, true);
$ano_mod = get_post_meta($post_id, Sync::META_ANO_MOD, true);
$cor     = get_post_meta($post_id, Sync::META_COR, true);
$placa   = get_post_meta($post_id, Sync::META_PLACA, true);
$portas  = get_post_meta($post_id, Sync::META_PORTAS, true);
$imagens = get_post_meta($post_id, Sync::META_IMAGENS, true);
if (!is_array($imagens)) {
    $imagens = [];
}
// Só exibir URLs absolutas (evitar que paths relativos virem wp-content/uploads)
$imagens = array_filter($imagens, static function ($url) {
    return is_string($url) && (str_starts_with($url, 'http://') || str_starts_with($url, 'https://'));
});
get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('cdw-veiculo-single'); ?>>
    <header class="entry-header">
        <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
        <?php
        $cliques = (int) get_post_meta($post_id, Tracker::META_CLIQUES, true);
        if ($cliques > 0) {
            echo '<span class="cdw-cliques">👁 ' . esc_html(number_format($cliques, 0, ',', '.')) . ' ' . esc_html__('visualizações', 'cdw-veiculos') . '</span>';
        }
        ?>
    </header>

    <?php if (!empty($imagens)): ?>
        <div class="cdw-veiculo-gallery">
            <?php foreach (array_slice($imagens, 0, 10) as $url): ?>
                <img src="<?php echo esc_url($url); ?>" alt="" loading="lazy" class="cdw-veiculo-img" />
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="entry-content">
        <?php the_content(); ?>
    </div>

    <dl class="cdw-veiculo-meta">
        <?php if ($preco !== '' && $preco > 0): ?>
            <dt><?php esc_html_e('Preço', 'cdw-veiculos'); ?></dt>
            <dd>R$ <?php echo esc_html(number_format((float) $preco, 2, ',', '.')); ?></dd>
        <?php endif; ?>
        <?php if ($km !== '' && (int) $km > 0): ?>
            <dt><?php esc_html_e('Quilometragem', 'cdw-veiculos'); ?></dt>
            <dd><?php echo esc_html(number_format((int) $km, 0, '', '.')); ?> km</dd>
        <?php endif; ?>
        <?php if ($ano_fab !== '' || $ano_mod !== ''): ?>
            <dt><?php esc_html_e('Ano', 'cdw-veiculos'); ?></dt>
            <dd><?php echo esc_html($ano_fab . '/' . $ano_mod); ?></dd>
        <?php endif; ?>
        <?php if ($cor !== ''): ?>
            <dt><?php esc_html_e('Cor', 'cdw-veiculos'); ?></dt>
            <dd><?php echo esc_html($cor); ?></dd>
        <?php endif; ?>
        <?php if ($placa !== ''): ?>
            <dt><?php esc_html_e('Placa', 'cdw-veiculos'); ?></dt>
            <dd><?php echo esc_html($placa); ?></dd>
        <?php endif; ?>
        <?php if ($portas !== '' && (int) $portas > 0): ?>
            <dt><?php esc_html_e('Portas', 'cdw-veiculos'); ?></dt>
            <dd><?php echo esc_html($portas); ?></dd>
        <?php endif; ?>
    </dl>

    <?php
    $taxonomies = [
        CPT::TAX_MARCA       => __('Marca', 'cdw-veiculos'),
        CPT::TAX_MODELO     => __('Modelo', 'cdw-veiculos'),
        CPT::TAX_CATEGORIA   => __('Categoria', 'cdw-veiculos'),
        CPT::TAX_TIPO       => __('Tipo', 'cdw-veiculos'),
        CPT::TAX_COMBUSTIVEL => __('Combustível', 'cdw-veiculos'),
        CPT::TAX_CAMBIO     => __('Câmbio', 'cdw-veiculos'),
        CPT::TAX_ACESSORIO  => __('Acessórios', 'cdw-veiculos'),
    ];
    foreach ($taxonomies as $tax => $label):
        $terms = get_the_terms($post_id, $tax);
        if (!$terms || is_wp_error($terms)) {
            continue;
        }
        $names = array_map(function ($t) { return $t->name; }, $terms);
        if (empty($names)) {
            continue;
        }
        ?>
        <p class="cdw-veiculo-terms"><strong><?php echo esc_html($label); ?>:</strong> <?php echo esc_html(implode(', ', $names)); ?></p>
    <?php endforeach; ?>
</article>

<?php get_footer(); ?>
