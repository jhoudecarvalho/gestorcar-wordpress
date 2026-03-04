<?php
/**
 * Custom Post Type "veiculo" e taxonomias.
 *
 * @package CDW\Veiculos
 */

declare(strict_types=1);

namespace CDW\Veiculos;

final class CPT {

    public const POST_TYPE = 'veiculo';

    public const TAX_MARCA       = 'marca_veiculo';
    public const TAX_MODELO      = 'modelo_veiculo';
    public const TAX_CATEGORIA   = 'categoria_veiculo';
    public const TAX_TIPO        = 'tipo_veiculo';
    public const TAX_COMBUSTIVEL = 'combustivel_veiculo';
    public const TAX_CAMBIO      = 'cambio_veiculo';
    public const TAX_ACESSORIO   = 'acessorio_veiculo';

    private static ?self $instance = null;

    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    /** Query var para páginas que listam todos os termos de uma taxonomia (ex.: /marca_veiculo/, /categoria_veiculo/). Valor = slug da taxonomia. */
    public const QUERY_VAR_LISTA_TAX = 'cdw_lista_tax';

    /** Taxonomias que têm página "lista todos" na raiz do slug. */
    public const TAXONOMIES_COM_LISTA = [self::TAX_MARCA, self::TAX_CATEGORIA, self::TAX_CAMBIO, self::TAX_COMBUSTIVEL];

    public function register(): void {
        add_action('init', [$this, 'register_cpt'], 5);
        add_action('init', [$this, 'register_taxonomies'], 6);
        add_action('init', [$this, 'add_rewrite_rules'], 9);
        add_filter('query_vars', [$this, 'query_vars']);
        add_filter('template_include', [$this, 'template_include'], 99);
        add_action('pre_get_posts', [$this, 'archive_only_published'], 10);
    }

    /**
     * Regras para /marca_veiculo/, /categoria_veiculo/, /cambio_veiculo/, /combustivel_veiculo/ listarem todos os termos.
     */
    public function add_rewrite_rules(): void {
        foreach (self::TAXONOMIES_COM_LISTA as $tax) {
            add_rewrite_rule('^' . preg_quote($tax, '/') . '/?$', 'index.php?' . self::QUERY_VAR_LISTA_TAX . '=' . $tax, 'top');
        }
    }

    public function query_vars(array $vars): array {
        $vars[] = self::QUERY_VAR_LISTA_TAX;
        return $vars;
    }

    /**
     * No archive de veículos e nas taxonomias do CPT, listar apenas posts com status "publish".
     */
    public function archive_only_published(\WP_Query $query): void {
        if (!$query->is_main_query()) {
            return;
        }
        if ($query->is_post_type_archive(self::POST_TYPE)) {
            $query->set('post_status', 'publish');
            return;
        }
        $our_taxonomies = [self::TAX_MARCA, self::TAX_MODELO, self::TAX_CATEGORIA, self::TAX_TIPO, self::TAX_COMBUSTIVEL, self::TAX_CAMBIO, self::TAX_ACESSORIO];
        if ($query->is_tax($our_taxonomies)) {
            $query->set('post_status', 'publish');
        }
    }

    /**
     * Usa o template do plugin para single e archive de veiculo se o tema não tiver.
     */
    public function template_include(string $template): string {
        if (is_singular(self::POST_TYPE)) {
            $plugin_template = CDW_VEICULOS_PATH . 'templates/single-veiculo.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        if (is_post_type_archive(self::POST_TYPE)) {
            $plugin_template = CDW_VEICULOS_PATH . 'templates/archive-veiculo.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        $lista_tax = get_query_var(self::QUERY_VAR_LISTA_TAX);
        if ($lista_tax && in_array($lista_tax, self::TAXONOMIES_COM_LISTA, true)) {
            $plugin_template = CDW_VEICULOS_PATH . 'templates/taxonomy-lista.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        $our_taxonomies = [self::TAX_MARCA, self::TAX_MODELO, self::TAX_CATEGORIA, self::TAX_TIPO, self::TAX_COMBUSTIVEL, self::TAX_CAMBIO, self::TAX_ACESSORIO];
        if (is_tax($our_taxonomies)) {
            $plugin_template = CDW_VEICULOS_PATH . 'templates/taxonomy-term-veiculos.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    public function register_cpt(): void {
        $labels = [
            'name'               => _x('Veículos', 'post type general name', 'cdw-veiculos'),
            'singular_name'      => _x('Veículo', 'post type singular name', 'cdw-veiculos'),
            'menu_name'          => __('Veículos', 'cdw-veiculos'),
            'add_new'            => __('Adicionar novo', 'cdw-veiculos'),
            'add_new_item'       => __('Adicionar novo veículo', 'cdw-veiculos'),
            'edit_item'          => __('Editar veículo', 'cdw-veiculos'),
            'new_item'           => __('Novo veículo', 'cdw-veiculos'),
            'view_item'          => __('Ver veículo', 'cdw-veiculos'),
            'view_items'         => __('Ver veículos', 'cdw-veiculos'),
            'search_items'       => __('Buscar veículos', 'cdw-veiculos'),
            'not_found'          => __('Nenhum veículo encontrado.', 'cdw-veiculos'),
            'not_found_in_trash' => __('Nenhum veículo na lixeira.', 'cdw-veiculos'),
            'all_items'          => __('Todos os veículos', 'cdw-veiculos'),
        ];
        register_post_type(self::POST_TYPE, [
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,
            'menu_icon'           => 'dashicons-car',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => ['title', 'editor', 'thumbnail', 'revisions'],
            'has_archive'         => true,
            'rewrite'             => ['slug' => 'veiculos'],
            'menu_position'       => 25,
        ]);
    }

    public function register_taxonomies(): void {
        $hierarchical = [
            self::TAX_MARCA     => __('Marca', 'cdw-veiculos'),
            self::TAX_MODELO   => __('Modelo', 'cdw-veiculos'),
            self::TAX_CATEGORIA => __('Categoria', 'cdw-veiculos'),
            self::TAX_TIPO     => __('Tipo', 'cdw-veiculos'),
        ];
        foreach ($hierarchical as $tax => $label) {
            register_taxonomy($tax, self::POST_TYPE, [
                'labels'            => [
                    'name'          => $label,
                    'singular_name' => $label,
                ],
                'hierarchical'      => true,
                'public'            => true,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'rewrite'           => ['slug' => $tax],
            ]);
        }
        $flat = [
            self::TAX_COMBUSTIVEL => __('Combustível', 'cdw-veiculos'),
            self::TAX_CAMBIO      => __('Câmbio', 'cdw-veiculos'),
            self::TAX_ACESSORIO   => __('Acessório', 'cdw-veiculos'),
        ];
        foreach ($flat as $tax => $label) {
            register_taxonomy($tax, self::POST_TYPE, [
                'labels'            => [
                    'name'          => $label,
                    'singular_name' => $label,
                ],
                'hierarchical'      => false,
                'public'            => true,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'rewrite'           => ['slug' => $tax],
            ]);
        }
    }

    /**
     * Título da página "lista todos" para cada taxonomia (ex.: /marca_veiculo/).
     *
     * @return array<string, string>
     */
    public static function get_lista_taxonomy_titles(): array {
        return [
            self::TAX_MARCA       => __('Marcas de veículos', 'cdw-veiculos'),
            self::TAX_CATEGORIA   => __('Categorias de veículos', 'cdw-veiculos'),
            self::TAX_CAMBIO      => __('Câmbio', 'cdw-veiculos'),
            self::TAX_COMBUSTIVEL => __('Combustível', 'cdw-veiculos'),
        ];
    }

    /**
     * Retorna ou cria um termo na taxonomy. Retorna o term_id ou 0.
     */
    public static function get_or_create_term(string $taxonomy, string $name, int $parent = 0): int {
        $name = trim($name);
        if ($name === '') {
            return 0;
        }
        $term = get_term_by('name', $name, $taxonomy);
        if ($term instanceof \WP_Term) {
            return (int) $term->term_id;
        }
        $slug = sanitize_title($name);
        $r = wp_insert_term($name, $taxonomy, ['parent' => $parent, 'slug' => $slug]);
        if (is_wp_error($r)) {
            return 0;
        }
        return (int) $r['term_id'];
    }
}
