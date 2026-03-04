<?php
/**
 * Tela de configurações: credenciais, frequência, Testar Conexão, Sincronizar Agora, DISABLE_WP_CRON.
 *
 * @package CDW\Veiculos
 */

if (!defined('ABSPATH')) {
    exit;
}

use CDW\Veiculos\Database;
use CDW\Veiculos\Scheduler;

$db = Database::get_instance();
$opts = $db->get_options();
$frequency = Scheduler::get_instance()->get_frequency();
$company_id = $db->get_company_id();
$companies = $db->get_companies_with_vehicle_count(10);
?>

<div class="wrap">
    <h1><?php esc_html_e('Configurações — CDW Veículos', 'cdw-veiculos'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('cdw_veiculos_settings'); ?>

        <h2 class="title"><?php esc_html_e('Banco de dados externo', 'cdw-veiculos'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="cdw_db_host"><?php esc_html_e('Host', 'cdw-veiculos'); ?></label></th>
                <td><input name="cdw_db_host" id="cdw_db_host" type="text" value="<?php echo esc_attr($opts['host']); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="cdw_db_port"><?php esc_html_e('Porta', 'cdw-veiculos'); ?></label></th>
                <td><input name="cdw_db_port" id="cdw_db_port" type="number" value="<?php echo esc_attr((string) $opts['port']); ?>" class="small-text" /></td>
            </tr>
            <tr>
                <th><label for="cdw_db_name"><?php esc_html_e('Database', 'cdw-veiculos'); ?></label></th>
                <td><input name="cdw_db_name" id="cdw_db_name" type="text" value="<?php echo esc_attr($opts['database']); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="cdw_db_user"><?php esc_html_e('Usuário', 'cdw-veiculos'); ?></label></th>
                <td><input name="cdw_db_user" id="cdw_db_user" type="text" value="<?php echo esc_attr($opts['user']); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="cdw_db_password"><?php esc_html_e('Senha', 'cdw-veiculos'); ?></label></th>
                <td><input name="cdw_db_password" id="cdw_db_password" type="password" value="" class="regular-text" autocomplete="off" />
                    <p class="description"><?php esc_html_e('Deixe em branco para manter a senha atual.', 'cdw-veiculos'); ?></p></td>
            </tr>
            <tr>
                <th><label for="cdw_db_view"><?php esc_html_e('Nome da view', 'cdw-veiculos'); ?></label></th>
                <td><input name="cdw_db_view" id="cdw_db_view" type="text" value="<?php echo esc_attr($opts['view']); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e('Ex.: crm_vehicles_v', 'cdw-veiculos'); ?></p></td>
            </tr>
            <tr>
                <th><label for="cdw_images_use_db"><?php esc_html_e('Imagens (URLs)', 'cdw-veiculos'); ?></label></th>
                <td>
                    <p class="description" style="margin-bottom:10px;">
                        <strong><?php esc_html_e('As imagens não são importadas para o WordPress.', 'cdw-veiculos'); ?></strong>
                        <?php esc_html_e('Ficam como link externo: o navegador carrega direto do servidor que você configurar. Funciona com o site em qualquer domínio.', 'cdw-veiculos'); ?>
                    </p>
                    <label for="cdw_images_base_url" style="display:block; margin-bottom:4px;"><?php esc_html_e('URL base das imagens', 'cdw-veiculos'); ?></label>
                    <input name="cdw_images_base_url" id="cdw_images_base_url" type="url" value="<?php echo esc_attr($opts['images_base_url']); ?>" class="large-text" placeholder="https://gestorcars3.s3-sa-east-1.amazonaws.com/" style="margin-bottom:6px;" />
                    <p class="description">
                        <?php esc_html_e('Ex.:', 'cdw-veiculos'); ?>
                        <code>https://gestorcars3.s3-sa-east-1.amazonaws.com/</code>
                        <?php esc_html_e('— a URL final fica: base + path da coluna (ex.: ...amazonaws.com/images/2025/12/arquivo.jpeg). Deixe desmarcado "Incluir domínio da empresa no path" para S3.', 'cdw-veiculos'); ?>
                    </p>
                    <label style="display:block; margin-top:8px;">
                        <input type="checkbox" name="cdw_images_include_domain_path" id="cdw_images_include_domain_path" value="1" <?php checked($db->get_images_include_domain_in_path()); ?> />
                        <?php esc_html_e('Incluir domínio da empresa no path', 'cdw-veiculos'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Se marcar, a URL fica: base + domínio_empresa + path (ex.: app.../imagens/dalboscoveiculos.com.br/images/2025/01/xxx.jpeg). Só marque se o app usar essa estrutura.', 'cdw-veiculos'); ?></p>
                    <label style="display:block; margin-top:10px;">
                        <input type="checkbox" name="cdw_images_use_db" id="cdw_images_use_db" value="1" <?php checked($db->get_images_use_db()); ?> />
                        <?php esc_html_e('Quando a URL base estiver vazia, usar domínio da empresa (crm_site_cfg) como host', 'cdw-veiculos'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="cdw_company_id"><?php esc_html_e('Sincronizar apenas empresa', 'cdw-veiculos'); ?></label></th>
                <td>
                    <select name="cdw_company_id" id="cdw_company_id">
                        <option value=""><?php esc_html_e('Todas as empresas', 'cdw-veiculos'); ?></option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?php echo esc_attr((string) $c['id']); ?>" <?php selected($company_id, $c['id']); ?>>
                                <?php echo esc_html(sprintf('%s (ID %d) — %d veículos', $c['byname'], $c['id'], $c['total'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e('Lista apenas empresas com 10 ou mais veículos. Ao escolher uma empresa, apenas os veículos dela serão sincronizados (cron e “Sincronizar agora”). Se você filtrar por uma empresa, na próxima sync os veículos de outras empresas já importados podem ser removidos do site.', 'cdw-veiculos'); ?></p>
                    <?php if ($company_id !== null && $company_id > 0): ?>
                        <?php
                        $total_empresa = $db->count_vehicles(1, $company_id);
                        $nome_empresa = '';
                        foreach ($companies as $c) {
                            if ((int) $c['id'] === $company_id) {
                                $nome_empresa = $c['byname'];
                                break;
                            }
                        }
                        ?>
                        <p class="description" style="margin-top:8px;"><strong><?php esc_html_e('Indicador:', 'cdw-veiculos'); ?></strong>
                            <?php echo esc_html(sprintf(__('Baixar apenas veículos da empresa “%s” — %d veículos disponíveis.', 'cdw-veiculos'), $nome_empresa ?: 'ID ' . $company_id, $total_empresa)); ?>
                        </p>
                    <?php endif; ?>
                    <p class="description" style="margin-top:10px;"><strong><?php esc_html_e('Status na view:', 'cdw-veiculos'); ?></strong> <?php esc_html_e('São importados apenas veículos com id_status = 1 (publicados) na view. Veículos em rascunho ou outros status não entram na sincronização.', 'cdw-veiculos'); ?></p>
                </td>
            </tr>
        </table>

        <h2 class="title"><?php esc_html_e('Sincronização automática (WP Cron)', 'cdw-veiculos'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="cdw_cron_frequency"><?php esc_html_e('Frequência', 'cdw-veiculos'); ?></label></th>
                <td>
                    <select name="cdw_cron_frequency" id="cdw_cron_frequency">
                        <option value="cdw_15min" <?php selected($frequency, 'cdw_15min'); ?>><?php esc_html_e('A cada 15 minutos', 'cdw-veiculos'); ?></option>
                        <option value="cdw_30min" <?php selected($frequency, 'cdw_30min'); ?>><?php esc_html_e('A cada 30 minutos', 'cdw-veiculos'); ?></option>
                        <option value="cdw_1h" <?php selected($frequency, 'cdw_1h'); ?>><?php esc_html_e('A cada hora', 'cdw-veiculos'); ?></option>
                        <option value="cdw_6h" <?php selected($frequency, 'cdw_6h'); ?>><?php esc_html_e('A cada 6 horas', 'cdw-veiculos'); ?></option>
                        <option value="cdw_daily" <?php selected($frequency, 'cdw_daily'); ?>><?php esc_html_e('Diariamente', 'cdw-veiculos'); ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" name="cdw_save_settings" class="button button-primary"><?php esc_html_e('Salvar configurações', 'cdw-veiculos'); ?></button>
            <button type="submit" name="cdw_test_connection" class="button"><?php esc_html_e('Testar conexão', 'cdw-veiculos'); ?></button>
            <button type="submit" name="cdw_sync_now" class="button button-secondary"><?php esc_html_e('Sincronizar agora', 'cdw-veiculos'); ?></button>
        </p>
    </form>

    <hr />

    <h2><?php esc_html_e('Cron real (recomendado)', 'cdw-veiculos'); ?></h2>
    <p><?php esc_html_e('Para maior confiabilidade, desabilite o cron virtual do WordPress e use um crontab no servidor.', 'cdw-veiculos'); ?></p>
    <ol style="list-style: decimal; margin-left: 20px;">
        <li><?php esc_html_e('No wp-config.php, adicione:', 'cdw-veiculos'); ?>
            <pre style="background:#f5f5f5; padding:10px;">define('DISABLE_WP_CRON', true);</pre>
        </li>
        <li><?php esc_html_e('No crontab do servidor (editar com crontab -e), adicione uma linha conforme a frequência desejada. Exemplo (a cada 15 minutos):', 'cdw-veiculos'); ?>
            <pre style="background:#f5f5f5; padding:10px;">*/15 * * * * curl -s "<?php echo esc_url(home_url('wp-cron.php?doing_wp_cron')); ?>" > /dev/null 2>&1</pre>
            <?php esc_html_e('Ou usando wget:', 'cdw-veiculos'); ?>
            <pre style="background:#f5f5f5; padding:10px;">*/15 * * * * wget -q -O - "<?php echo esc_url(home_url('wp-cron.php?doing_wp_cron')); ?>" > /dev/null 2>&1</pre>
        </li>
    </ol>
</div>
