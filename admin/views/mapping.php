<?php
/**
 * Tela de mapeamento: carrega colunas da view dinamicamente e exibe selects.
 *
 * @package CDW\Veiculos
 */

if (!defined('ABSPATH')) {
    exit;
}

use CDW\Veiculos\Database;
use CDW\Veiculos\CPT;
use CDW\Veiculos\Sync;

$db = Database::get_instance();
$columns = $db->get_view_columns();
$has_connection = !empty($columns);
$map_options = [
    '' => __('— Não mapear —', 'cdw-veiculos'),
    'post_title' => __('Título do post', 'cdw-veiculos'),
    'id_externo' => __('ID único externo (_cdw_id_externo)', 'cdw-veiculos'),
    'tax_marca' => __('Taxonomia: Marca', 'cdw-veiculos'),
    'tax_modelo' => __('Taxonomia: Modelo', 'cdw-veiculos'),
    'tax_categoria' => __('Taxonomia: Categoria', 'cdw-veiculos'),
    'tax_tipo' => __('Taxonomia: Tipo', 'cdw-veiculos'),
    'tax_combustivel' => __('Taxonomia: Combustível', 'cdw-veiculos'),
    'tax_cambio' => __('Taxonomia: Câmbio', 'cdw-veiculos'),
    'tax_acessorio' => __('Taxonomia: Acessório (optionals)', 'cdw-veiculos'),
    'meta_preco' => __('Meta: Preço', 'cdw-veiculos'),
    'meta_km' => __('Meta: KM', 'cdw-veiculos'),
    'meta_ano_fab' => __('Meta: Ano fabricação', 'cdw-veiculos'),
    'meta_ano_mod' => __('Meta: Ano modelo', 'cdw-veiculos'),
    'meta_cor' => __('Meta: Cor', 'cdw-veiculos'),
    'meta_placa' => __('Meta: Placa', 'cdw-veiculos'),
    'meta_portas' => __('Meta: Portas', 'cdw-veiculos'),
    'meta_imagens' => __('Meta: Imagens (URLs)', 'cdw-veiculos'),
];
$default_map = [
    'id' => 'id_externo',
    'version_name' => 'post_title',
    'make_name' => 'tax_marca',
    'model_name' => 'tax_modelo',
    'category_name' => 'tax_categoria',
    'carrocery_name' => 'tax_tipo',
    'fuel_name' => 'tax_combustivel',
    'transmission_name' => 'tax_cambio',
    'optionals' => 'tax_acessorio',
    'price' => 'meta_preco',
    'km' => 'meta_km',
    'year_fab' => 'meta_ano_fab',
    'year_mod' => 'meta_ano_mod',
    'color_name' => 'meta_cor',
    'plaque' => 'meta_placa',
    'doors' => 'meta_portas',
    'images' => 'meta_imagens',
];
?>

<div class="wrap">
    <h1><?php esc_html_e('Mapeamento de colunas — CDW Veículos', 'cdw-veiculos'); ?></h1>

    <?php if (!$has_connection): ?>
        <div class="notice notice-warning">
            <p><?php esc_html_e('Configure as credenciais do banco na aba Configurações e teste a conexão para carregar as colunas da view.', 'cdw-veiculos'); ?></p>
        </div>
    <?php else: ?>
        <p><?php esc_html_e('Colunas da view atual (carregadas dinamicamente). O plugin usa por padrão o mapeamento fixo abaixo; esta tela serve como referência e para futura configuração persistente.', 'cdw-veiculos'); ?></p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Coluna na view', 'cdw-veiculos'); ?></th>
                    <th><?php esc_html_e('Mapeamento sugerido', 'cdw-veiculos'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($columns as $col): ?>
                <tr>
                    <td><code><?php echo esc_html($col); ?></code></td>
                    <td>
                        <select name="map_<?php echo esc_attr($col); ?>" disabled="disabled" style="min-width:260px;">
                            <?php foreach ($map_options as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected(isset($default_map[$col]) ? $default_map[$col] : '', $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="description"><?php esc_html_e('O mapeamento efetivo está definido no código (class-sync.php). Alterações aqui são apenas visuais até implementação de opções salvas.', 'cdw-veiculos'); ?></p>
    <?php endif; ?>
</div>
