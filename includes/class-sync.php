<?php
/**
 * Lógica de sincronização: criar/atualizar/deletar posts a partir da view.
 *
 * @package CDW\Veiculos
 */

declare(strict_types=1);

namespace CDW\Veiculos;

use WP_Post;

final class Sync {

    public const META_ID_EXTERNO = '_cdw_id_externo';
    public const META_PRECO      = '_cdw_preco';
    public const META_KM         = '_cdw_km';
    public const META_ANO_FAB     = '_cdw_ano_fab';
    public const META_ANO_MOD     = '_cdw_ano_mod';
    public const META_COR        = '_cdw_cor';
    public const META_PLACA      = '_cdw_placa';
    public const META_PORTAS    = '_cdw_portas';
    public const META_POTENCIA  = '_cdw_potencia';
    public const META_CODIGO_FIPE = '_cdw_codigo_fipe';
    public const META_IMAGENS   = '_cdw_imagens';

    private const BATCH_SIZE = 50;
    private const BATCH_THRESHOLD = 200;

    private static ?self $instance = null;
    private Database $db;
    private string $view_name = '';

    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = Database::get_instance();
        $this->view_name = $this->db->get_view_name();
    }

    /**
     * Executa a sincronização completa. Retorna array com totais e mensagem.
     */
    public function run(): array {
        $result = [
            'total'      => 0,
            'criados'    => 0,
            'atualizados' => 0,
            'deletados'  => 0,
            'erros'      => 0,
            'mensagem'   => '',
        ];
        $this->db->disconnect();
        $pdo = $this->db->connect();
        if ($pdo === null) {
            $result['mensagem'] = __('Credenciais do banco não configuradas ou conexão falhou.', 'cdw-veiculos');
            $this->log_result($result);
            return $result;
        }
        $company_id = $this->db->get_company_id();
        // id_status = 1 = publicado (apenas veículos publicados na view)
        $total_in_view = $this->db->count_vehicles(1, $company_id);
        $result['total'] = $total_in_view;
        $use_batches = $total_in_view > self::BATCH_THRESHOLD;
        $limit = $use_batches ? self::BATCH_SIZE : 0;
        $offset = 0;
        $ids_externos_atual = [];
        while (true) {
            $rows = $this->db->fetch_vehicles($limit ?: 5000, $offset, 1, $company_id); // 1 = publicado
            if (empty($rows)) {
                break;
            }
            foreach ($rows as $row) {
                $id_externo = isset($row['id']) ? (int) $row['id'] : 0;
                if ($id_externo <= 0) {
                    $result['erros']++;
                    continue;
                }
                $ids_externos_atual[] = $id_externo;
                $post_id = $this->find_post_by_id_externo($id_externo);
                if ($post_id === 0) {
                    $created = $this->create_post($row);
                    if ($created > 0) {
                        $result['criados']++;
                    } else {
                        $result['erros']++;
                    }
                } else {
                    $updated = $this->update_post($post_id, $row);
                    if ($updated) {
                        $result['atualizados']++;
                    } else {
                        $result['erros']++;
                    }
                }
            }
            if (!$use_batches || count($rows) < $limit) {
                break;
            }
            $offset += $limit;
        }
        // Garantir que a taxonomia marca_veiculo tenha todos os termos (marcas distintas dos publicados)
        $marcas = $this->db->get_distinct_makes(1, $company_id);
        foreach ($marcas as $make_name) {
            CPT::get_or_create_term(CPT::TAX_MARCA, $make_name);
        }
        // Remove do WordPress os veículos que não estão mais publicados na view (id_status != 1)
        // ou que saíram da lista (ex.: filtro por empresa).
        $deleted = $this->delete_posts_not_in_list($ids_externos_atual);
        $result['deletados'] = $deleted;
        $result['mensagem'] = sprintf(
            /* translators: 1: total, 2: criados, 3: atualizados, 4: deletados, 5: erros */
            __('Total publicados na view: %1$d. Criados: %2$d, Atualizados: %3$d, Removidos (não publicados/fora da lista): %4$d, Erros: %5$d.', 'cdw-veiculos'),
            $result['total'],
            $result['criados'],
            $result['atualizados'],
            $result['deletados'],
            $result['erros']
        );
        $this->log_result($result);
        $this->db->disconnect();
        return $result;
    }

    private function find_post_by_id_externo(int $id_externo): int {
        $posts = get_posts([
            'post_type'      => CPT::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => self::META_ID_EXTERNO,
                    'value' => (string) $id_externo,
                ],
            ],
        ]);
        return !empty($posts) ? (int) $posts[0] : 0;
    }

    private function create_post(array $row): int {
        $title = $this->get_post_title($row);
        $post_data = [
            'post_type'   => CPT::POST_TYPE,
            'post_title'  => $title,
            'post_name'   => sanitize_title($title . '-' . ($row['id'] ?? '')),
            'post_content' => $this->get_post_content($row),
            'post_status' => 'publish',
            'post_author' => 1,
        ];
        $post_id = wp_insert_post($post_data, true);
        if (is_wp_error($post_id) || $post_id === 0) {
            return 0;
        }
        $this->set_meta_and_taxonomies($post_id, $row);
        return $post_id;
    }

    private function update_post(int $post_id, array $row): bool {
        $title = $this->get_post_title($row);
        wp_update_post([
            'ID'           => $post_id,
            'post_title'   => $title,
            'post_content' => $this->get_post_content($row),
        ]);
        $this->set_meta_and_taxonomies($post_id, $row);
        return true;
    }

    private function get_post_title(array $row): string {
        $version = isset($row['version_name']) ? trim((string) $row['version_name']) : '';
        if ($version !== '') {
            return $version;
        }
        $make = isset($row['make_name']) ? trim((string) $row['make_name']) : '';
        $model = isset($row['model_name']) ? trim((string) $row['model_name']) : '';
        if ($make !== '' || $model !== '') {
            return trim($make . ' ' . $model) ?: (string) ($row['id'] ?? 'Veículo');
        }
        return (string) ($row['id'] ?? 'Veículo');
    }

    private function get_post_content(array $row): string {
        $desc = isset($row['description']) ? (string) $row['description'] : '';
        return wp_kses_post($desc);
    }

    private function set_meta_and_taxonomies(int $post_id, array $row): void {
        $id_externo = isset($row['id']) ? (int) $row['id'] : 0;
        update_post_meta($post_id, self::META_ID_EXTERNO, (string) $id_externo);
        if (isset($row['price'])) {
            update_post_meta($post_id, self::META_PRECO, (float) $row['price']);
        }
        if (isset($row['km'])) {
            update_post_meta($post_id, self::META_KM, (int) $row['km']);
        }
        if (isset($row['year_fab'])) {
            update_post_meta($post_id, self::META_ANO_FAB, (int) $row['year_fab']);
        }
        if (isset($row['year_mod'])) {
            update_post_meta($post_id, self::META_ANO_MOD, (int) $row['year_mod']);
        }
        if (!empty($row['color_name'])) {
            update_post_meta($post_id, self::META_COR, sanitize_text_field((string) $row['color_name']));
        }
        if (isset($row['plaque'])) {
            update_post_meta($post_id, self::META_PLACA, sanitize_text_field((string) $row['plaque']));
        }
        if (isset($row['doors'])) {
            update_post_meta($post_id, self::META_PORTAS, (int) $row['doors']);
        }
        $id_company = isset($row['id_company']) ? (int) $row['id_company'] : 0;
        $imagens = $this->normalize_images($row['images'] ?? '', $id_company);
        update_post_meta($post_id, self::META_IMAGENS, $imagens);

        $taxes = [
            CPT::TAX_MARCA     => $row['make_name'] ?? '',
            CPT::TAX_MODELO   => $row['model_name'] ?? '',
            CPT::TAX_CATEGORIA => $row['category_name'] ?? '',
            CPT::TAX_TIPO     => $row['carrocery_name'] ?? '',
            CPT::TAX_COMBUSTIVEL => $row['fuel_name'] ?? '',
            CPT::TAX_CAMBIO   => $row['transmission_name'] ?? '',
        ];
        $term_ids = [];
        foreach ($taxes as $tax => $name) {
            $name = trim((string) $name);
            if ($name !== '') {
                $tid = CPT::get_or_create_term($tax, $name);
                if ($tid > 0) {
                    $term_ids[$tax] = [$tid];
                }
            }
        }
        $optionals_names = $this->resolve_optionals($row['optionals'] ?? '');
        foreach ($optionals_names as $nome) {
            $tid = CPT::get_or_create_term(CPT::TAX_ACESSORIO, $nome);
            if ($tid > 0) {
                $term_ids[CPT::TAX_ACESSORIO] = array_merge($term_ids[CPT::TAX_ACESSORIO] ?? [], [$tid]);
            }
        }
        foreach ($term_ids as $tax => $ids) {
            wp_set_object_terms($post_id, array_unique($ids), $tax);
        }
    }

    /**
     * optionals na view: JSON array de IDs. Resolve para nomes via tabela.
     */
    private function resolve_optionals(string $raw): array {
        if ($raw === '' || $raw === 'null') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $ids = array_filter(array_map('intval', $decoded));
        if (empty($ids)) {
            return [];
        }
        return $this->db->resolve_optionals_names($ids);
    }

    /**
     * Converte coluna images (JSON array de paths) em array de URLs completas (sempre absolutas).
     * As imagens NÃO são importadas para o WordPress: só guardamos as URLs (link externo).
     *
     * Padrão central (uma URL base para qualquer WordPress):
     * - Se houver "URL base" (ex.: https://app.gestorcar.com.br/imagens/), monta:
     *   base + domínio_da_empresa + "/" + path  →  app.gestorcar.com.br/imagens/dalboscoveiculos.com.br/images/2025/01/xxx.jpeg
     * - Se não houver URL base, usa o domínio da empresa como host (crm_site_cfg): https://dalboscoveiculos.com.br/images/...
     *
     * @param int $id_company Usado para buscar domain em crm_site_cfg (injetar no path ou como host).
     */
    private function normalize_images(string $raw, int $id_company = 0): array {
        if ($raw === '' || $raw === 'null') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $manual_base = $this->db->get_images_base_url();
        $company_base = ($id_company > 0 && $this->db->get_images_use_db())
            ? $this->db->get_images_base_url_for_company($id_company)
            : '';
        $company_domain = '';
        if ($id_company > 0 && $company_base !== '') {
            $company_domain = preg_replace('#^https?://#i', '', rtrim($company_base, '/'));
        }
        if ($manual_base !== '') {
            $base = rtrim($manual_base, '/') . '/';
            if ($this->db->get_images_include_domain_in_path() && $company_domain !== '') {
                $base .= $company_domain . '/';
            }
        } else {
            $base = $company_base !== '' ? rtrim($company_base, '/') . '/' : '';
        }
        $out = [];
        foreach ($decoded as $path) {
            $path = is_string($path) ? trim($path) : '';
            if ($path === '') {
                continue;
            }
            $path = ltrim(str_replace('\\', '/', $path), '/');
            $full = $base . $path;
            if (str_starts_with($full, 'http://') || str_starts_with($full, 'https://')) {
                $out[] = $full;
            }
        }
        return $out;
    }

    /**
     * Remove posts do CPT cujo id_externo não está na lista atual de publicados (id_status=1).
     * Ou seja: despublicar/remover da origem implica em apagar o post aqui (deletar permanentemente).
     */
    private function delete_posts_not_in_list(array $ids_presentes): int {
        $posts = get_posts([
            'post_type'      => CPT::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_key'       => self::META_ID_EXTERNO,
        ]);
        $deleted = 0;
        foreach ($posts as $post_id) {
            $id_externo = get_post_meta($post_id, self::META_ID_EXTERNO, true);
            if ($id_externo === '' || in_array((int) $id_externo, $ids_presentes, true)) {
                continue;
            }
            wp_delete_post($post_id, true);
            $deleted++;
        }
        return $deleted;
    }

    private function log_result(array $result): void {
        global $wpdb;
        $table = $wpdb->prefix . 'cdw_veiculos_logs';
        $wpdb->insert(
            $table,
            [
                'sincronizado_em' => current_time('mysql'),
                'total'           => $result['total'],
                'criados'         => $result['criados'],
                'atualizados'     => $result['atualizados'],
                'deletados'       => $result['deletados'],
                'erros'           => $result['erros'],
                'mensagem'        => $result['mensagem'],
            ],
            ['%s', '%d', '%d', '%d', '%d', '%d', '%s']
        );
    }
}
