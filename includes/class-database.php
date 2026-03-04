<?php
/**
 * Conexão PDO com o banco externo (view crm_vehicles_v).
 *
 * @package CDW\Veiculos
 */

declare(strict_types=1);

namespace CDW\Veiculos;

use PDO;
use PDOException;

final class Database {

    private const OPTION_HOST     = 'cdw_veiculos_db_host';
    private const OPTION_PORT     = 'cdw_veiculos_db_port';
    private const OPTION_NAME     = 'cdw_veiculos_db_name';
    private const OPTION_USER     = 'cdw_veiculos_db_user';
    private const OPTION_PASSWORD = 'cdw_veiculos_db_password';
    private const OPTION_VIEW     = 'cdw_veiculos_db_view';
    private const OPTION_BASE_URL   = 'cdw_veiculos_images_base_url';
    private const OPTION_IMAGES_USE_DB = 'cdw_veiculos_images_use_db';
    private const OPTION_IMAGES_INCLUDE_DOMAIN_IN_PATH = 'cdw_veiculos_images_include_domain_path';
    private const OPTION_COMPANY_ID = 'cdw_veiculos_company_id';

    private const CONNECT_TIMEOUT = 10;

    private static ?self $instance = null;
    private ?PDO $pdo = null;

    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    /**
     * Retorna as opções de conexão (sem senha em log).
     */
    public function get_options(): array {
        return [
            'host'      => get_option(self::OPTION_HOST, ''),
            'port'      => (int) get_option(self::OPTION_PORT, 3306),
            'database'  => get_option(self::OPTION_NAME, 'gstc_app'),
            'user'      => get_option(self::OPTION_USER, ''),
            'password'  => get_option(self::OPTION_PASSWORD, ''),
            'view'      => get_option(self::OPTION_VIEW, 'crm_vehicles_v'),
            'images_base_url' => get_option(self::OPTION_BASE_URL, ''),
            'company_id' => get_option(self::OPTION_COMPANY_ID, ''),
        ];
    }

    /**
     * ID da empresa para filtrar veículos (vazio = todas).
     */
    public function get_company_id(): ?int {
        $v = get_option(self::OPTION_COMPANY_ID, '');
        if ($v === '' || $v === null) {
            return null;
        }
        $id = (int) $v;
        return $id > 0 ? $id : null;
    }

    /**
     * Nome da view configurada.
     */
    public function get_view_name(): string {
        $opts = $this->get_options();
        $view = $opts['view'] ?? 'crm_vehicles_v';
        return is_string($view) && $view !== '' ? $view : 'crm_vehicles_v';
    }

    /**
     * URL base para montar URLs de imagens (paths da coluna images).
     * Usada quando preenchida; placeholder "app.seudominio.com.br" é tratado como vazio.
     */
    public function get_images_base_url(): string {
        $opts = $this->get_options();
        $url = is_string($opts['images_base_url'] ?? '') ? trim($opts['images_base_url']) : '';
        if ($url !== '' && (str_contains($url, 'seudominio') || str_contains($url, 'example.com'))) {
            return '';
        }
        return $url;
    }

    /**
     * Se true, a URL base das imagens é obtida da base (crm_site_cfg.domain por id_company).
     * Padrão true: usar domínio da base.
     */
    public function get_images_use_db(): bool {
        return (bool) get_option(self::OPTION_IMAGES_USE_DB, true);
    }

    /**
     * Se true e houver URL base manual, insere o domínio da empresa no path: base/dominio/path.
     * Se false (padrão), usa só base + path: base/path (ex.: app.gestorcar.com.br/imagens/images/2025/01/xxx.jpeg).
     */
    public function get_images_include_domain_in_path(): bool {
        return (bool) get_option(self::OPTION_IMAGES_INCLUDE_DOMAIN_IN_PATH, false);
    }

    /**
     * Retorna a URL base das imagens para uma empresa usando o domínio em crm_site_cfg.
     * Ex.: https://marcollaveiculos.com.br/
     * Retorna string vazia se não houver domain ou conexão.
     */
    public function get_images_base_url_for_company(int $id_company): string {
        $pdo = $this->connect();
        if ($pdo === null || $id_company <= 0) {
            return '';
        }
        $stmt = $pdo->prepare('SELECT domain FROM crm_site_cfg WHERE id_company = :id LIMIT 1');
        $stmt->execute(['id' => $id_company]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $domain = isset($row['domain']) ? trim((string) $row['domain']) : '';
        if ($domain === '') {
            return '';
        }
        $domain = preg_replace('#^https?://#i', '', $domain);
        return 'https://' . $domain . '/';
    }

    /**
     * Testa a conexão PDO. Retorna array com 'success' e 'message'.
     */
    public function test_connection(): array {
        $this->disconnect();
        try {
            $pdo = $this->connect();
            if ($pdo === null) {
                return ['success' => false, 'message' => __('Falha ao conectar (PDO retornou null).', 'cdw-veiculos')];
            }
            $pdo->query('SELECT 1');
            return ['success' => true, 'message' => __('Conexão realizada com sucesso.', 'cdw-veiculos')];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: error message */
                    __('Erro de conexão: %s', 'cdw-veiculos'),
                    $e->getMessage()
                ),
            ];
        }
    }

    /**
     * Estabelece conexão PDO. Retorna PDO ou null se opções incompletas.
     */
    public function connect(): ?PDO {
        if ($this->pdo !== null) {
            return $this->pdo;
        }
        $opts = $this->get_options();
        if ($opts['host'] === '' || $opts['user'] === '' || $opts['database'] === '') {
            return null;
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $opts['host'],
            $opts['port'],
            $opts['database']
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => self::CONNECT_TIMEOUT,
        ];
        $this->pdo = new PDO($dsn, $opts['user'], $opts['password'], $options);
        return $this->pdo;
    }

    /**
     * Fecha a conexão (útil antes de test_connection ou entre batches).
     */
    public function disconnect(): void {
        $this->pdo = null;
    }

    /**
     * Lista as colunas da view (via LIMIT 1 e fetch associativo).
     * Retorna array de nomes de colunas ou vazio em caso de erro.
     *
     * @return list<string>
     */
    public function get_view_columns(): array {
        $pdo = $this->connect();
        if ($pdo === null) {
            return [];
        }
        $view = $this->get_view_name();
        $stmt = $pdo->prepare(sprintf('SELECT * FROM `%s` LIMIT 1', $this->escape_identifier($view)));
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return [];
        }
        return array_keys($row);
    }

    /**
     * Busca todos os veículos da view (ou em batches).
     * Filtro opcional por id_status e id_company.
     * Na view crm_vehicles_v, id_status = 1 = publicado (apenas esses são importados por padrão).
     *
     * @param int $limit 0 = sem limite (cuidado com memória)
     * @param int $offset
     * @param int|null $id_status Filtrar por id_status (1 = publicado; null = todos)
     * @param int|null $id_company Filtrar por empresa (null = todas)
     * @return list<array<string, mixed>>
     */
    public function fetch_vehicles(int $limit = 0, int $offset = 0, ?int $id_status = 1, ?int $id_company = null): array {
        $pdo = $this->connect();
        if ($pdo === null) {
            return [];
        }
        $view = $this->get_view_name();
        $view_esc = $this->escape_identifier($view);
        $sql = "SELECT * FROM `{$view_esc}`";
        $params = [];
        $where = [];
        if ($id_status !== null) {
            $where[] = 'id_status = :id_status';
            $params['id_status'] = $id_status;
        }
        if ($id_company !== null && $id_company > 0) {
            $where[] = 'id_company = :id_company';
            $params['id_company'] = $id_company;
        }
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY id ASC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
            $sql .= ' OFFSET ' . (int) $offset;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Retorna o total de registros na view (com filtros opcionais).
     * id_status = 1 = publicado (padrão para importar só publicados).
     */
    public function count_vehicles(?int $id_status = 1, ?int $id_company = null): int {
        $pdo = $this->connect();
        if ($pdo === null) {
            return 0;
        }
        $view = $this->get_view_name();
        $view_esc = $this->escape_identifier($view);
        $sql = "SELECT COUNT(*) AS total FROM `{$view_esc}`";
        $params = [];
        $where = [];
        if ($id_status !== null) {
            $where[] = 'id_status = :id_status';
            $params['id_status'] = $id_status;
        }
        if ($id_company !== null && $id_company > 0) {
            $where[] = 'id_company = :id_company';
            $params['id_company'] = $id_company;
        }
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = isset($row['total']) ? (int) $row['total'] : 0;
        return $total;
    }

    /**
     * Lista empresas que possuem veículos na view (id_status=1 = publicado), com contagem.
     * Ordenado por total decrescente. Mínimo 10 veículos para aparecer na lista.
     *
     * @return list<array{id: int, byname: string, total: int}>
     */
    public function get_companies_with_vehicle_count(int $min_vehicles = 10): array {
        $pdo = $this->connect();
        if ($pdo === null) {
            return [];
        }
        $view_esc = $this->escape_identifier($this->get_view_name());
        $sql = "SELECT c.id, c.byname, COUNT(v.id) AS total
                FROM crm_companies c
                INNER JOIN `{$view_esc}` v ON v.id_company = c.id
                WHERE v.id_status = 1
                GROUP BY c.id, c.byname
                HAVING total >= " . (int) $min_vehicles . "
                ORDER BY total DESC";
        $stmt = $pdo->query($sql);
        if ($stmt === false) {
            return [];
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) $row['id'],
                'byname' => (string) ($row['byname'] ?? ''),
                'total' => (int) ($row['total'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * Lista make_name distintos na view (veículos publicados).
     * Usado para garantir que a taxonomia marca_veiculo tenha todos os termos.
     *
     * @param int|null $id_status 1 = publicado (padrão)
     * @param int|null $id_company Filtrar por empresa (null = todas)
     * @return list<string>
     */
    public function get_distinct_makes(?int $id_status = 1, ?int $id_company = null): array {
        $pdo = $this->connect();
        if ($pdo === null) {
            return [];
        }
        $view = $this->get_view_name();
        $view_esc = $this->escape_identifier($view);
        $sql = "SELECT DISTINCT make_name FROM `{$view_esc}` WHERE make_name IS NOT NULL AND TRIM(make_name) != ''";
        $params = [];
        if ($id_status !== null) {
            $sql .= ' AND id_status = :id_status';
            $params['id_status'] = $id_status;
        }
        if ($id_company !== null && $id_company > 0) {
            $sql .= ' AND id_company = :id_company';
            $params['id_company'] = $id_company;
        }
        $sql .= ' ORDER BY make_name ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['make_name'] ?? ''));
            if ($name !== '') {
                $out[] = $name;
            }
        }
        return $out;
    }

    /**
     * Resolve IDs de optionals para nomes (tabela crm_vehicles_optionals).
     * Retorna array de nomes (ou array vazio se falha).
     *
     * @param list<string|int> $ids
     * @return list<string>
     */
    public function resolve_optionals_names(array $ids): array {
        if (empty($ids)) {
            return [];
        }
        $pdo = $this->connect();
        if ($pdo === null) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, name FROM crm_vehicles_optionals WHERE id IN ({$placeholders}) AND name IS NOT NULL";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_map('intval', $ids));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $names = [];
        foreach ($rows as $row) {
            if (!empty($row['name'])) {
                $names[] = trim((string) $row['name']);
            }
        }
        return $names;
    }

    /**
     * Escapa nome de tabela/view (apenas caracteres permitidos).
     */
    private function escape_identifier(string $name): string {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $name) ?: 'crm_vehicles_v';
    }

    /**
     * Chaves de opções (para admin salvar).
     */
    public static function option_host(): string { return self::OPTION_HOST; }
    public static function option_port(): string { return self::OPTION_PORT; }
    public static function option_name(): string { return self::OPTION_NAME; }
    public static function option_user(): string { return self::OPTION_USER; }
    public static function option_password(): string { return self::OPTION_PASSWORD; }
    public static function option_view(): string { return self::OPTION_VIEW; }
    public static function option_images_base_url(): string { return self::OPTION_BASE_URL; }
    public static function option_images_use_db(): string { return self::OPTION_IMAGES_USE_DB; }
    public static function option_images_include_domain_path(): string { return self::OPTION_IMAGES_INCLUDE_DOMAIN_IN_PATH; }
    public static function option_company_id(): string { return self::OPTION_COMPANY_ID; }
}
