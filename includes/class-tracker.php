<?php
/**
 * Rastreamento de cliques (page views) por veículo.
 * Incrementa _cdw_cliques uma vez por sessão/visitante (cookie 1h).
 *
 * @package CDW\Veiculos
 */

declare(strict_types=1);

namespace CDW\Veiculos;

final class Tracker {

    public const META_CLIQUES = '_cdw_cliques';
    private const COOKIE_PREFIX = 'cdw_viewed_';
    private const COOKIE_EXPIRE = 3600; // 1 hora

    public static function init(): void {
        add_action('template_redirect', [self::class, 'track_single_veiculo'], 5);
    }

    /**
     * Em single do CPT veiculo: conta 1 page view por visitante (cookie 1h).
     */
    public static function track_single_veiculo(): void {
        if (!is_singular(CPT::POST_TYPE)) {
            return;
        }

        $post_id = get_the_ID();
        if ($post_id <= 0) {
            return;
        }

        $cookie_name = self::COOKIE_PREFIX . $post_id;
        if (isset($_COOKIE[$cookie_name])) {
            return;
        }

        $atual = (int) get_post_meta($post_id, self::META_CLIQUES, true);
        update_post_meta($post_id, self::META_CLIQUES, $atual + 1);

        $id_externo    = get_post_meta($post_id, '_cdw_id_externo', true);
        $company_token = get_option('cdw_veiculos_company_token', '');
        if ($id_externo !== '' && $company_token !== '') {
            $headers = ['Content-Type' => 'application/json'];
            $cookie  = get_option('cdw_veiculos_gestorcar_cookie', '');
            if ($cookie !== '') {
                $headers['Cookie'] = 'crm_sessions=' . $cookie;
            }
            wp_remote_post(
                'https://novo.gestorcar.com.br/api/track_view',
                [
                    'blocking' => false,
                    'timeout'  => 5,
                    'headers'  => $headers,
                    'body'     => wp_json_encode([
                        'company_token' => $company_token,
                        'id_vehicle'    => (string) $id_externo,
                    ]),
                ]
            );
        }

        $expire = time() + self::COOKIE_EXPIRE;
        $path   = '/';
        $domain = '';
        $secure = is_ssl();
        $httponly = false;
        $samesite = 'Lax';
        if (PHP_VERSION_ID >= 70300) {
            setcookie($cookie_name, '1', [
                'expires'  => $expire,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite,
            ]);
        } else {
            setcookie($cookie_name, '1', $expire, $path . '; samesite=' . $samesite, $domain, $secure, $httponly);
        }
    }
}
