<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

class CDW_Financing_Form
{
    public static function init(): void
    {
        add_action('init',                [self::class, 'register_rewrite']);
        add_filter('query_vars',          [self::class, 'register_query_vars']);
        add_action('template_redirect',   [self::class, 'render_page']);
        add_action('wp_enqueue_scripts',  [self::class, 'enqueue_assets']);
        add_action('wp_ajax_cdw_enviar_financiamento',        [self::class, 'handle_ajax']);
        add_action('wp_ajax_nopriv_cdw_enviar_financiamento', [self::class, 'handle_ajax']);
    }

    public static function register_rewrite(): void
    {
        add_rewrite_rule(
            '^financiar-automovel/([^/]+)-(\d+)/?$',
            'index.php?cdw_financiar=1&cdw_veiculo_alias=$matches[1]&cdw_veiculo_id=$matches[2]',
            'top'
        );
    }

    public static function register_query_vars(array $vars): array
    {
        $vars[] = 'cdw_financiar';
        $vars[] = 'cdw_veiculo_alias';
        $vars[] = 'cdw_veiculo_id';
        return $vars;
    }

    public static function render_page(): void
    {
        if (!get_query_var('cdw_financiar')) return;

        $id_externo = (int) get_query_var('cdw_veiculo_id');
        if (!$id_externo) wp_die('Veículo não encontrado.', 404);

        // Buscar post pelo id_externo
        $posts = get_posts([
            'post_type'   => 'veiculo',
            'post_status' => 'publish',
            'meta_key'    => '_cdw_id_externo',
            'meta_value'  => $id_externo,
            'numberposts' => 1,
        ]);

        if (empty($posts)) wp_die('Veículo não encontrado.', 404);

        $post    = $posts[0];
        $post_id = $post->ID;

        // Dados do veículo
        $marca   = implode(', ', wp_get_object_terms($post_id, 'marca_veiculo',  ['fields' => 'names']) ?: []);
        // No plugin CDW Veículos estamos usando modelo_veiculo (pois a importação não cria a taxonomia versao_veiculo)
        $modelo  = implode(', ', wp_get_object_terms($post_id, 'modelo_veiculo', ['fields' => 'names']) ?: []);
        $ano_fab = (int)   get_post_meta($post_id, '_cdw_ano_fab', true);
        $ano_mod = (int)   get_post_meta($post_id, '_cdw_ano_mod', true);
        $preco   = (float) get_post_meta($post_id, '_cdw_preco',   true);

        $titulo_pagina = "Financiar {$marca} {$modelo} #{$id_externo}";

        // Renderizar página completa
        get_header();
        echo self::render_form($post_id, [
            'marca'      => $marca,
            'modelo'     => $modelo, 
            'ano_fab'    => $ano_fab,
            'ano_mod'    => $ano_mod,
            'preco'      => $preco,
            'id_externo' => $id_externo,
            'titulo'     => $titulo_pagina,
        ]);
        get_footer();
        exit;
    }

    public static function enqueue_assets(): void
    {
        if (!get_query_var('cdw_financiar')) return;

        wp_enqueue_script('jquery-mask',
            'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js',
            ['jquery'], '1.14.16', true
        );

        wp_add_inline_script('jquery-mask', "
        jQuery(function($) {
            // Máscaras
            $('#cdw-fin-celular').mask('(00) 00000-0000');
            $('#cdw-fin-ref-telefone').mask('(00) 00000-0000');
            $('#cdw-fin-cpf').mask('000.000.000-00');
            $('#cdw-fin-rg').mask('0.000.000-A', {reverse: true});
            $('#cdw-fin-cep').mask('00000-000');
            $('#cdw-fin-nascimento').mask('00/00/0000');

            // Busca CEP
            $('#cdw-fin-cep').on('blur', function() {
                var cep = $(this).val().replace(/\D/g, '');
                if (cep.length !== 8) return;
                $.getJSON('https://viacep.com.br/ws/' + cep + '/json/', function(data) {
                    if (data.erro) return;
                    $('#cdw-fin-logradouro').val(data.logradouro);
                    $('#cdw-fin-bairro').val(data.bairro);
                    $('#cdw-fin-cidade').val(data.localidade);
                    $('#cdw-fin-estado').val(data.uf);
                });
            });

            // Submit
            $('#cdw-form-financiamento').on('submit', function(e) {
                e.preventDefault();
                var btn = $(this).find('button[type=submit]');
                btn.prop('disabled', true).text('Enviando...');

                var data = {
                    action:           'cdw_enviar_financiamento',
                    nonce:            cdw_fin_ajax.nonce,
                    post_id:          $(this).data('post-id'),
                    id_externo:       $(this).data('id-externo'),
                    // Dados pessoais
                    nome:             $('#cdw-fin-nome').val(),
                    email:            $('#cdw-fin-email').val(),
                    celular:          $('#cdw-fin-celular').val(),
                    rg:               $('#cdw-fin-rg').val(),
                    cpf:              $('#cdw-fin-cpf').val(),
                    nascimento:       $('#cdw-fin-nascimento').val(),
                    sexo:             $('input[name=cdw_fin_sexo]:checked').val(),
                    estado_civil:     $('#cdw-fin-estado-civil').val(),
                    naturalidade:     $('#cdw-fin-naturalidade').val(),
                    pai:              $('#cdw-fin-pai').val(),
                    mae:              $('#cdw-fin-mae').val(),
                    // Financiamento
                    entrada:          $('#cdw-fin-entrada').val(),
                    parcelas:         $('#cdw-fin-parcelas').val(),
                    // Endereço
                    logradouro:       $('#cdw-fin-logradouro').val(),
                    numero:           $('#cdw-fin-numero').val(),
                    complemento:      $('#cdw-fin-complemento').val(),
                    cep:              $('#cdw-fin-cep').val(),
                    bairro:           $('#cdw-fin-bairro').val(),
                    tempo_residencia: $('#cdw-fin-tempo-residencia').val(),
                    estado:           $('#cdw-fin-estado').val(),
                    cidade:           $('#cdw-fin-cidade').val(),
                    // Profissional
                    empresa:          $('#cdw-fin-empresa').val(),
                    cargo:            $('#cdw-fin-cargo').val(),
                    renda:            $('#cdw-fin-renda').val(),
                    tempo_emprego:    $('#cdw-fin-tempo-emprego').val(),
                    // Banco
                    banco:            $('#cdw-fin-banco').val(),
                    agencia:          $('#cdw-fin-agencia').val(),
                    conta:            $('#cdw-fin-conta').val(),
                    tempo_conta:      $('#cdw-fin-tempo-conta').val(),
                    // Referência
                    ref_nome:         $('#cdw-fin-ref-nome').val(),
                    ref_telefone:     $('#cdw-fin-ref-telefone').val(),
                    // Adicional
                    observacoes:      $('#cdw-fin-observacoes').val(),
                    allow_car_change: $('#cdw-fin-troca').is(':checked') ? 1 : 0,
                    allow_whatsapp:   $('#cdw-fin-whatsapp').is(':checked') ? 1 : 0,
                    allow_phone:      $('#cdw-fin-ligacao').is(':checked') ? 1 : 0,
                    allow_email:      $('#cdw-fin-email-contato').is(':checked') ? 1 : 0,
                };

                $.post(cdw_fin_ajax.url, data, function(res) {
                    if (res.success) {
                        $('#cdw-form-financiamento').html(
                            '<div class=\"cdw-fin-sucesso\">' +
                            '<h2>✅ Solicitação enviada com sucesso!</h2>' +
                            '<p>Em breve nossa equipe entrará em contato.</p>' +
                            '</div>'
                        );
                    } else {
                        btn.prop('disabled', false).html('✉ Enviar');
                        alert(res.data.message || 'Erro ao enviar. Tente novamente.');
                    }
                });
            });
        });
        ");

        wp_localize_script('jquery-mask', 'cdw_fin_ajax', [
            'url'   => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cdw_financiamento_nonce'),
        ]);
    }

    public static function render_form(int $post_id, array $v): string
    {
        $preco_f = number_format($v['preco'], 2, ',', '.');
        ob_start(); ?>
        <div class="cdw-fin-wrap">
            <h1 class="cdw-fin-titulo-principal"><?php echo esc_html($v['titulo']); ?></h1>
            <p>Preencha o formulário abaixo para aprovar seu financiamento</p>

            <form id="cdw-form-financiamento"
                  data-post-id="<?php echo esc_attr((string)$post_id); ?>"
                  data-id-externo="<?php echo esc_attr((string)$v['id_externo']); ?>">
                <?php wp_nonce_field('cdw_financiamento_nonce', 'cdw_fin_nonce'); ?>

                <!-- DADOS DO VEÍCULO -->
                <h2 class="cdw-fin-section">DADOS DO VEÍCULO</h2>
                <div class="cdw-fin-grid-2">
                    <div class="cdw-fin-campo">
                        <label>* Marca</label>
                        <input type="text" value="<?php echo esc_attr($v['marca']); ?>" readonly />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>* Modelo / Versão</label>
                        <input type="text" value="<?php echo esc_attr($v['modelo']); ?>" readonly />
                    </div>
                </div>
                <div class="cdw-fin-grid-3">
                    <div class="cdw-fin-campo">
                        <label>* Ano Fabricação</label>
                        <input type="text" value="<?php echo esc_attr((string)$v['ano_fab']); ?>" readonly />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>* Ano Modelo</label>
                        <input type="text" value="<?php echo esc_attr((string)$v['ano_mod']); ?>" readonly />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>* Valor</label>
                        <input type="text" value="<?php echo esc_attr($preco_f); ?>" readonly />
                    </div>
                </div>

                <!-- DADOS PARA FINANCIAMENTO -->
                <h2 class="cdw-fin-section">DADOS PARA FINANCIAMENTO</h2>
                <div class="cdw-fin-grid-2">
                    <div class="cdw-fin-campo">
                        <label>* Entrada</label>
                        <input type="text" id="cdw-fin-entrada" placeholder="R$ 0,00" required />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>* Parcelas</label>
                        <select id="cdw-fin-parcelas" required>
                            <option value="12">12x</option>
                            <option value="24" selected>24x</option>
                            <option value="36">36x</option>
                            <option value="48">48x</option>
                            <option value="52">52x</option>
                            <option value="60">60x</option>
                            <option value="72">72x</option>
                        </select>
                    </div>
                </div>

                <!-- DADOS PESSOAIS -->
                <h2 class="cdw-fin-section">DADOS PESSOAIS</h2>
                <div class="cdw-fin-campo" style="margin-bottom:16px;">
                    <label>* Nome Completo</label>
                    <input type="text" id="cdw-fin-nome" required />
                </div>
                <div class="cdw-fin-grid-3">
                    <div class="cdw-fin-campo">
                        <label>* RG</label>
                        <input type="text" id="cdw-fin-rg" required />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>* CPF</label>
                        <input type="text" id="cdw-fin-cpf" required />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>* Data de Nascimento</label>
                        <input type="text" id="cdw-fin-nascimento"
                               placeholder="dd/mm/aaaa" required />
                    </div>
                </div>
                <div class="cdw-fin-grid-3">
                    <div class="cdw-fin-campo">
                        <label>* E-mail</label>
                        <input type="email" id="cdw-fin-email" required />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>* Celular</label>
                        <input type="text" id="cdw-fin-celular" required />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>Sexo</label>
                        <div class="cdw-fin-radio">
                            <label><input type="radio" name="cdw_fin_sexo"
                                   value="Masculino" checked /> Masculino</label>
                            <label><input type="radio" name="cdw_fin_sexo"
                                   value="Feminino" /> Feminino</label>
                        </div>
                    </div>
                </div>
                <div class="cdw-fin-grid-3">
                    <div class="cdw-fin-campo">
                        <label>Estado Civil</label>
                        <select id="cdw-fin-estado-civil">
                            <option value="Solteiro">Solteiro</option>
                            <option value="Casado">Casado</option>
                            <option value="Separado">Separado</option>
                            <option value="Divorciado">Divorciado</option>
                            <option value="Outros">Outros</option>
                        </select>
                    </div>
                    <div class="cdw-fin-campo">
                        <label>Naturalidade</label>
                        <input type="text" id="cdw-fin-naturalidade" />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>Filiação (Pai)</label>
                        <input type="text" id="cdw-fin-pai" />
                    </div>
                </div>
                <div class="cdw-fin-campo" style="margin-bottom:16px;">
                    <label>Filiação (Mãe)</label>
                    <input type="text" id="cdw-fin-mae" />
                </div>

                <!-- ENDEREÇO -->
                <h2 class="cdw-fin-section">ENDEREÇO</h2>
                <div class="cdw-fin-grid-3">
                    <div class="cdw-fin-campo">
                        <label>* Logradouro</label>
                        <input type="text" id="cdw-fin-logradouro" required />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>* Número</label>
                        <input type="text" id="cdw-fin-numero" required />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>Complemento</label>
                        <input type="text" id="cdw-fin-complemento" />
                    </div>
                </div>
                <div class="cdw-fin-grid-3">
                    <div class="cdw-fin-campo">
                        <label>* CEP</label>
                        <input type="text" id="cdw-fin-cep" required />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>* Bairro</label>
                        <input type="text" id="cdw-fin-bairro" required />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>Tempo de Residência</label>
                        <input type="text" id="cdw-fin-tempo-residencia" />
                    </div>
                </div>
                <div class="cdw-fin-grid-2">
                    <div class="cdw-fin-campo">
                        <label>* Estado</label>
                        <select id="cdw-fin-estado" required>
                            <option value="">-- Selecione --</option>
                            <?php
                            $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO',
                                    'MA','MT','MS','MG','PA','PB','PR','PE','PI',
                                    'RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                            foreach ($ufs as $uf) {
                                echo "<option value=\"{$uf}\">{$uf}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="cdw-fin-campo">
                        <label>* Cidade</label>
                        <input type="text" id="cdw-fin-cidade" required />
                    </div>
                </div>

                <!-- DADOS PROFISSIONAIS -->
                <h2 class="cdw-fin-section">DADOS PROFISSIONAIS</h2>
                <div class="cdw-fin-grid-3">
                    <div class="cdw-fin-campo">
                        <label>Empresa onde trabalha</label>
                        <input type="text" id="cdw-fin-empresa" />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>Cargo / Função Exercida</label>
                        <input type="text" id="cdw-fin-cargo" />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>* Renda Mensal</label>
                        <input type="text" id="cdw-fin-renda" required />
                    </div>
                </div>
                <div class="cdw-fin-campo" style="max-width:340px; margin-bottom:16px;">
                    <label>* Tempo Neste Emprego</label>
                    <input type="text" id="cdw-fin-tempo-emprego" required />
                </div>

                <!-- REFERÊNCIAS BANCÁRIAS -->
                <h2 class="cdw-fin-section">REFERÊNCIAS BANCÁRIAS</h2>
                <div class="cdw-fin-grid-4">
                    <div class="cdw-fin-campo">
                        <label>Banco</label>
                        <input type="text" id="cdw-fin-banco" />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>Agência</label>
                        <input type="text" id="cdw-fin-agencia" />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>Conta</label>
                        <input type="text" id="cdw-fin-conta" />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>Tempo de Conta</label>
                        <input type="text" id="cdw-fin-tempo-conta" />
                    </div>
                </div>

                <!-- REFERÊNCIA PESSOAL -->
                <h2 class="cdw-fin-section">REFERÊNCIA PESSOAL</h2>
                <div class="cdw-fin-grid-2">
                    <div class="cdw-fin-campo">
                        <label>Nome</label>
                        <input type="text" id="cdw-fin-ref-nome" />
                    </div>
                    <div class="cdw-fin-campo">
                        <label>Telefone</label>
                        <input type="text" id="cdw-fin-ref-telefone" />
                    </div>
                </div>

                <!-- INFORMAÇÕES ADICIONAIS -->
                <h2 class="cdw-fin-section">INFORMAÇÕES ADICIONAIS</h2>
                <div class="cdw-fin-campo">
                    <label>Observações</label>
                    <textarea id="cdw-fin-observacoes" rows="4"></textarea>
                </div>

                <!-- TOGGLES -->
                <div class="cdw-toggles">
                    <label class="cdw-toggle">
                        <input type="checkbox" id="cdw-fin-troca" />
                        <span class="cdw-toggle-slider"></span>
                        Tem veículo na troca?
                    </label>
                    <label class="cdw-toggle">
                        <input type="checkbox" id="cdw-fin-whatsapp" />
                        <span class="cdw-toggle-slider"></span>
                        Prefere contato via WhatsApp
                    </label>
                    <label class="cdw-toggle">
                        <input type="checkbox" id="cdw-fin-ligacao" />
                        <span class="cdw-toggle-slider"></span>
                        Prefere contato via ligação telefônica
                    </label>
                    <label class="cdw-toggle">
                        <input type="checkbox" id="cdw-fin-email-contato" />
                        <span class="cdw-toggle-slider"></span>
                        Prefere contato via e-mail
                    </label>
                </div>

                <button type="submit" class="cdw-btn-enviar">✉ Enviar</button>

                <p class="cdw-fin-aviso">
                    * Ao enviar suas informações pessoais, você concorda que
                    realizemos consultas bancárias em seu nome para aprovação
                    de um financiamento de veículo.
                </p>
            </form>
        </div>

        <style>
        .cdw-fin-wrap { max-width: 960px; margin: 0 auto; padding: 32px 20px; font-family: sans-serif; }
        .cdw-fin-titulo-principal { font-size: 28px; font-weight: 800; margin-bottom: 10px; }
        .cdw-fin-section { font-size: 20px; font-weight: 800; margin: 28px 0 16px; border-bottom: 2px solid #eee; padding-bottom: 8px; }
        .cdw-fin-campo { display: flex; flex-direction: column; gap: 4px; }
        .cdw-fin-campo label { font-size: 13px; font-weight: 600; }
        .cdw-fin-campo input,
        .cdw-fin-campo select,
        .cdw-fin-campo textarea {
            border: 1px solid #ccc; border-radius: 5px;
            padding: 9px 12px; font-size: 14px;
            width: 100%; box-sizing: border-box;
        }
        .cdw-fin-campo input[readonly] { background: #f9f9f9; color: #555; }
        .cdw-fin-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .cdw-fin-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .cdw-fin-grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .cdw-fin-radio  { display: flex; gap: 16px; align-items: center; padding-top: 8px; }
        .cdw-toggles    { display: flex; flex-direction: column; gap: 10px; margin: 20px 0; }
        .cdw-toggle     { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; }
        .cdw-toggle input { display: none; }
        .cdw-toggle-slider {
            width: 40px; height: 22px; background: #ccc;
            border-radius: 11px; position: relative; flex-shrink: 0; transition: background .3s;
        }
        .cdw-toggle-slider::after {
            content: ''; position: absolute;
            width: 18px; height: 18px; background: #fff;
            border-radius: 50%; top: 2px; left: 2px; transition: left .3s;
        }
        .cdw-toggle input:checked + .cdw-toggle-slider { background: #222; }
        .cdw-toggle input:checked + .cdw-toggle-slider::after { left: 20px; }
        .cdw-btn-enviar {
            padding: 14px 40px; background: #1a1a1a; color: #fff;
            border: none; border-radius: 6px; font-size: 16px;
            font-weight: 600; cursor: pointer; margin-top: 8px;
        }
        .cdw-btn-enviar:hover { background: #333; }
        .cdw-fin-aviso { font-size: 12px; color: #666; margin-top: 12px; }
        .cdw-fin-sucesso { text-align: center; padding: 60px 20px; }
        .cdw-fin-sucesso h2 { font-size: 24px; }
        @media (max-width: 768px) {
            .cdw-fin-grid-2,
            .cdw-fin-grid-3,
            .cdw-fin-grid-4 { grid-template-columns: 1fr; }
        }
        </style>
        <?php
        return ob_get_clean();
    }

    public static function handle_ajax(): void
    {
        check_ajax_referer('cdw_financiamento_nonce', 'nonce');

        $post_id    = absint($_POST['post_id']    ?? 0);
        $id_externo = absint($_POST['id_externo'] ?? 0);
        $nome       = sanitize_text_field($_POST['nome']      ?? '');
        $email      = sanitize_email($_POST['email']          ?? '');
        $celular    = sanitize_text_field($_POST['celular']    ?? '');
        $renda      = sanitize_text_field($_POST['renda']      ?? '');
        $tempo_emp  = sanitize_text_field($_POST['tempo_emprego'] ?? '');

        // Validação obrigatórios
        if (!$nome || !$email || !$celular || !$id_externo) {
            wp_send_json_error(['message' => 'Preencha todos os campos obrigatórios.']);
        }

        $company_token = get_option('cdw_veiculos_company_token', '');
        if (!$company_token) {
            wp_send_json_error(['message' => 'Configuração do plugin incompleta.']);
        }

        // Montar city_state
        $cidade = sanitize_text_field($_POST['cidade'] ?? '');
        $estado = sanitize_text_field($_POST['estado'] ?? '');
        $city_state = $cidade && $estado ? "{$cidade}/{$estado}" : '';

        // Montar address
        $logradouro  = sanitize_text_field($_POST['logradouro']  ?? '');
        $numero      = sanitize_text_field($_POST['numero']      ?? '');
        $complemento = sanitize_text_field($_POST['complemento'] ?? '');
        $cep         = sanitize_text_field($_POST['cep']         ?? '');
        $bairro      = sanitize_text_field($_POST['bairro']      ?? '');
        $address     = trim("{$logradouro}, {$numero}" . ($complemento ? ", {$complemento}" : '') . ", {$cep}, {$bairro}");

        $response = wp_remote_post(
            'https://novo.gestorcar.com.br/gestorcar/api/post_financing_lead',
            [
                'blocking' => true,
                'timeout'  => 15,
                'headers'  => ['Content-Type' => 'application/json'],
                'body'     => wp_json_encode([
                    'company_token'    => $company_token,
                    'id_vehicle'       => $id_externo,
                    'name'             => $nome,
                    'phone'            => $celular,
                    'email'            => $email,
                    'entry_value'      => sanitize_text_field($_POST['entrada']        ?? ''),
                    'installments'     => sanitize_text_field($_POST['parcelas']       ?? ''),
                    'rg'               => sanitize_text_field($_POST['rg']             ?? ''),
                    'cpf'              => sanitize_text_field($_POST['cpf']            ?? ''),
                    'birth_date'       => sanitize_text_field($_POST['nascimento']     ?? ''),
                    'gender'           => sanitize_text_field($_POST['sexo']           ?? ''),
                    'marital_status'   => sanitize_text_field($_POST['estado_civil']   ?? ''),
                    'birth_place'      => sanitize_text_field($_POST['naturalidade']   ?? ''),
                    'father_name'      => sanitize_text_field($_POST['pai']            ?? ''),
                    'mother_name'      => sanitize_text_field($_POST['mae']            ?? ''),
                    'address'          => $address,
                    'city_state'       => $city_state,
                    'residence_time'   => sanitize_text_field($_POST['tempo_residencia'] ?? ''),
                    'company_name'     => sanitize_text_field($_POST['empresa']        ?? ''),
                    'job_title'        => sanitize_text_field($_POST['cargo']          ?? ''),
                    'monthly_income'   => $renda,
                    'company_time'     => $tempo_emp,
                    'bank_name'        => sanitize_text_field($_POST['banco']          ?? ''),
                    'bank_agency'      => sanitize_text_field($_POST['agencia']        ?? ''),
                    'bank_account'     => sanitize_text_field($_POST['conta']          ?? ''),
                    'bank_time'        => sanitize_text_field($_POST['tempo_conta']    ?? ''),
                    'personal_ref_name'  => sanitize_text_field($_POST['ref_nome']     ?? ''),
                    'personal_ref_phone' => sanitize_text_field($_POST['ref_telefone'] ?? ''),
                ]),
            ]
        );

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Erro ao conectar com o servidor.']);
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 300) {
            wp_send_json_success(['message' => 'Financiamento enviado com sucesso!']);
        }

        wp_send_json_error(['message' => 'Erro ao enviar. Tente novamente.']);
    }
}
