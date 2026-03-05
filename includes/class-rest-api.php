<?php
/**
 * REST API do CDW Veículos (endpoints públicos com chave opcional).
 *
 * @package CDW\Veiculos
 */

declare(strict_types=1);

namespace CDW\Veiculos;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class Rest_Api {

    public const NAMESPACE = 'cdw-veiculos/v1';
    private const OPTION_API_KEY = 'cdw_veiculos_rest_api_key';
    private const HEADER_API_KEY = 'X-CDW-API-Key';

    public static function init(): void {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void {
        register_rest_route(self::NAMESPACE, '/cliques/resumo', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [self::class, 'get_resumo'],
            'permission_callback' => [self::class, 'check_permission'],
        ]);
    }

    public static function check_permission(WP_REST_Request $request): bool {
        $configured_key = get_option(self::OPTION_API_KEY, '');
        if ($configured_key === '') {
            return true;
        }
        $header = $request->get_header(self::HEADER_API_KEY);
        return is_string($header) && $header !== '' && hash_equals($configured_key, $header);
    }

    public static function get_resumo(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $results = $wpdb->get_results("
            SELECT
                externo.meta_value AS id_externo,
                COALESCE(cliques.meta_value, '0') AS cliques
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} externo
                ON externo.post_id = p.ID
                AND externo.meta_key = '_cdw_id_externo'
            LEFT JOIN {$wpdb->postmeta} cliques
                ON cliques.post_id = p.ID
                AND cliques.meta_key = '_cdw_cliques'
            WHERE p.post_type   = 'veiculo'
              AND p.post_status = 'publish'
            ORDER BY CAST(COALESCE(cliques.meta_value, '0') AS UNSIGNED) DESC
        ");

        $veiculos = array_map(static function ($row) {
            return [
                'id'      => (int) $row->id_externo,
                'cliques' => (int) $row->cliques,
            ];
        }, $results ?? []);

        return new WP_REST_Response([
            'gerado_em' => (new \DateTime())->format(\DateTime::ATOM),
            'total'     => count($veiculos),
            'veiculos'  => $veiculos,
        ], 200);
    }

    public static function option_api_key(): string {
        return self::OPTION_API_KEY;
    }
}
