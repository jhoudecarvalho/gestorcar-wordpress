<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

class CDW_Contact_Form
{
    public static function init(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('wp_ajax_cdw_enviar_proposta',        [self::class, 'handle_ajax']);
        add_action('wp_ajax_nopriv_cdw_enviar_proposta', [self::class, 'handle_ajax']);
    }

    public static function enqueue_assets(): void
    {
        if (!is_singular('veiculo')) return;

        // Máscara de telefone (CDN)
        wp_enqueue_script(
            'jquery-mask',
            'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js',
            ['jquery'], '1.14.16', true
        );

        // Script do formulário (inline)
        wp_add_inline_script('jquery-mask', "
            jQuery(function($) {
                $('#cdw-telefone').mask('(00) 00000-0000');

                $('#cdw-form-proposta').on('submit', function(e) {
                    e.preventDefault();

                    var btn = $(this).find('button[type=submit]');
                    btn.prop('disabled', true).text('Enviando...');

                    $.post(cdw_ajax.url, {
                        action:          'cdw_enviar_proposta',
                        nonce:           cdw_ajax.nonce,
                        post_id:         $(this).data('post-id'),
                        nome:            $('#cdw-nome').val(),
                        email:           $('#cdw-email').val(),
                        telefone:        $('#cdw-telefone').val(),
                        mensagem:        $('#cdw-mensagem').val(),
                        allow_car_change: $('#cdw-troca').is(':checked') ? 1 : 0,
                        allow_whatsapp:   $('#cdw-whatsapp').is(':checked') ? 1 : 0,
                        allow_phone:      $('#cdw-ligacao').is(':checked') ? 1 : 0,
                        allow_email:      $('#cdw-email-contato').is(':checked') ? 1 : 0,
                    }, function(res) {
                        if (res.success) {
                            $('#cdw-form-proposta').html(
                                '<div class=\"cdw-sucesso\">' +
                                '<p>✅ Proposta enviada com sucesso!</p>' +
                                '<p>Em breve entraremos em contato.</p>' +
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

        wp_localize_script('jquery-mask', 'cdw_ajax', [
            'url'   => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cdw_proposta_nonce'),
        ]);
    }

    public static function render(int $post_id): string
    {
        // Montar mensagem padrão
        $marca   = implode(', ', wp_get_object_terms($post_id, 'marca_veiculo',
                     ['fields' => 'names']) ?: []);
        $versao  = implode(', ', wp_get_object_terms($post_id, 'modelo_veiculo', // Usando modelo_veiculo, pois versao_veiculo não existe na base
                     ['fields' => 'names']) ?: []);
        $ano_fab = (int) get_post_meta($post_id, '_cdw_ano_fab', true);
        $ano_mod = (int) get_post_meta($post_id, '_cdw_ano_mod', true);
        $preco   = (float) get_post_meta($post_id, '_cdw_preco', true);
        $preco_f = 'R$ ' . number_format($preco, 2, ',', '.');

        $mensagem = "Olá, tenho interesse neste automóvel de referência: "
            . "{$marca} {$versao}, {$ano_fab}/{$ano_mod}, {$preco_f}, "
            . "por favor entre em contato comigo assim que possível, obrigado!";

        $whatsapp_number = get_option('cdw_veiculos_whatsapp', '');
        $whatsapp_number_clean = preg_replace('/[^0-9]/', '', $whatsapp_number);
        $whatsapp_url = $whatsapp_number_clean !== '' 
            ? "https://wa.me/{$whatsapp_number_clean}?text=" . urlencode($mensagem) 
            : "https://wa.me/?text=" . urlencode($mensagem);

        ob_start(); ?>
        <div class="cdw-proposta-wrap">
            <h3 class="cdw-proposta-titulo">ENVIE SUA PROPOSTA</h3>

            <a href="<?php echo esc_url($whatsapp_url); ?>"
               target="_blank" class="cdw-whatsapp-btn">
                <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg"
                     width="28" alt="WhatsApp" />
                ATENDIMENTO POR WHATSAPP
            </a>

            <form id="cdw-form-proposta"
                  data-post-id="<?php echo esc_attr((string)$post_id); ?>">
                <?php wp_nonce_field('cdw_proposta_nonce', 'cdw_nonce_field'); ?>

                <div class="cdw-campo">
                    <label>* Nome</label>
                    <div class="cdw-input-wrap">
                        <span class="cdw-icon">👤</span>
                        <input type="text" id="cdw-nome" placeholder="Seu nome completo" required />
                    </div>
                </div>

                <div class="cdw-campo">
                    <label>* E-mail</label>
                    <div class="cdw-input-wrap">
                        <span class="cdw-icon">✉</span>
                        <input type="email" id="cdw-email" placeholder="seu@email.com" required />
                    </div>
                </div>

                <div class="cdw-campo">
                    <label>* Telefone</label>
                    <div class="cdw-input-wrap">
                        <span class="cdw-icon">📱</span>
                        <input type="text" id="cdw-telefone"
                               placeholder="(45) 99999-9999" required />
                    </div>
                </div>

                <div class="cdw-campo">
                    <label>* Mensagem</label>
                    <textarea id="cdw-mensagem" rows="6" required><?php
                        echo esc_textarea($mensagem);
                    ?></textarea>
                </div>

                <div class="cdw-toggles">
                    <label class="cdw-toggle">
                        <input type="checkbox" id="cdw-troca" />
                        <span class="cdw-toggle-slider"></span>
                        Tem veículo na troca?
                    </label>
                    <label class="cdw-toggle">
                        <input type="checkbox" id="cdw-whatsapp" checked />
                        <span class="cdw-toggle-slider"></span>
                        Prefere contato via WhatsApp
                    </label>
                    <label class="cdw-toggle">
                        <input type="checkbox" id="cdw-ligacao" />
                        <span class="cdw-toggle-slider"></span>
                        Prefere contato via ligação telefônica
                    </label>
                    <label class="cdw-toggle">
                        <input type="checkbox" id="cdw-email-contato" />
                        <span class="cdw-toggle-slider"></span>
                        Prefere contato via e-mail
                    </label>
                </div>

                <button type="submit" class="cdw-btn-enviar">✉ Enviar</button>
            </form>
        </div>

        <style>
        .cdw-proposta-wrap { max-width: 520px; padding: 24px; font-family: sans-serif; }
        .cdw-proposta-titulo { font-size: 22px; font-weight: 800; margin-bottom: 16px; }
        .cdw-whatsapp-btn {
            display: flex; align-items: center; gap: 10px;
            background: #f5f5f5; border: 1px solid #ddd;
            padding: 12px 16px; border-radius: 6px;
            font-weight: 700; color: #25D366;
            text-decoration: none; margin-bottom: 20px;
            font-size: 15px;
        }
        .cdw-campo { margin-bottom: 14px; }
        .cdw-campo label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px; }
        .cdw-input-wrap { display: flex; align-items: center; border: 1px solid #ccc; border-radius: 5px; overflow: hidden; }
        .cdw-icon { padding: 0 10px; background: #f0f0f0; font-size: 16px; height: 42px; display: flex; align-items: center; border-right: 1px solid #ccc; }
        .cdw-input-wrap input { border: none; outline: none; padding: 10px 12px; width: 100%; font-size: 14px; }
        .cdw-campo textarea { width: 100%; border: 1px solid #ccc; border-radius: 5px; padding: 10px 12px; font-size: 14px; resize: vertical; box-sizing: border-box; }
        .cdw-toggles { margin: 16px 0; display: flex; flex-direction: column; gap: 10px; }
        .cdw-toggle { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; }
        .cdw-toggle input { display: none; }
        .cdw-toggle-slider {
            width: 40px; height: 22px; background: #ccc;
            border-radius: 11px; position: relative;
            transition: background 0.3s; flex-shrink: 0;
        }
        .cdw-toggle-slider::after {
            content: ''; position: absolute;
            width: 18px; height: 18px; background: white;
            border-radius: 50%; top: 2px; left: 2px;
            transition: left 0.3s;
        }
        .cdw-toggle input:checked + .cdw-toggle-slider { background: #222; }
        .cdw-toggle input:checked + .cdw-toggle-slider::after { left: 20px; }
        .cdw-btn-enviar {
            width: 100%; padding: 14px;
            background: #1a1a1a; color: white;
            border: none; border-radius: 6px;
            font-size: 16px; font-weight: 600;
            cursor: pointer; margin-top: 8px;
        }
        .cdw-btn-enviar:hover { background: #333; }
        .cdw-sucesso { text-align: center; padding: 40px 20px; }
        .cdw-sucesso p:first-child { font-size: 20px; font-weight: 700; }
        </style>
        <?php
        return ob_get_clean();
    }

    public static function handle_ajax(): void
    {
        check_ajax_referer('cdw_proposta_nonce', 'nonce');

        $post_id  = absint($_POST['post_id'] ?? 0);
        $nome     = sanitize_text_field($_POST['nome']     ?? '');
        $email    = sanitize_email($_POST['email']         ?? '');
        $telefone = sanitize_text_field($_POST['telefone'] ?? '');
        $mensagem = sanitize_textarea_field($_POST['mensagem'] ?? '');

        // Validação
        if (!$nome || !$email || !$telefone || !$mensagem) {
            wp_send_json_error(['message' => 'Preencha todos os campos obrigatórios.']);
        }
        if (!is_email($email)) {
            wp_send_json_error(['message' => 'E-mail inválido.']);
        }
        if (!$post_id || get_post_type($post_id) !== 'veiculo') {
            wp_send_json_error(['message' => 'Veículo inválido.']);
        }

        $id_externo    = get_post_meta($post_id, '_cdw_id_externo', true);
        $company_token = get_option('cdw_veiculos_company_token', '');

        if (!$id_externo || !$company_token) {
            wp_send_json_error(['message' => 'Configuração do plugin incompleta.']);
        }

        $response = wp_remote_post(
            'https://novo.gestorcar.com.br/gestorcar/api/post_contact_lead',
            [
                'blocking' => true,
                'timeout'  => 15,
                'headers'  => ['Content-Type' => 'application/json'],
                'body'     => wp_json_encode([
                    'company_token'  => $company_token,
                    'id_vehicle'     => (int) $id_externo,
                    'name'           => $nome,
                    'phone'          => $telefone,
                    'email'          => $email,
                    'description'    => $mensagem,
                    'allow_whatsapp' => (int) ($_POST['allow_whatsapp']   ?? 0),
                    'allow_car_change'=> (int) ($_POST['allow_car_change'] ?? 0),
                    'allow_email'    => (int) ($_POST['allow_email']      ?? 0),
                    'allow_phone'    => (int) ($_POST['allow_phone']      ?? 0),
                ]),
            ]
        );

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Erro ao conectar com o servidor.']);
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 300) {
            $form_email = get_option('cdw_veiculos_form_email', '');
            if (is_email($form_email)) {
                $site_name = get_bloginfo('name');
                $subject = "Nova proposta de veículo - " . get_the_title($post_id);
                $body = "Nova proposta recebida através do site {$site_name}:\n\n";
                $body .= "Nome: {$nome}\n";
                $body .= "E-mail: {$email}\n";
                $body .= "Telefone: {$telefone}\n\n";
                $body .= "Mensagem:\n{$mensagem}\n\n";
                $body .= "Link do veículo: " . get_permalink($post_id) . "\n";
                wp_mail($form_email, $subject, $body);
            }
            wp_send_json_success(['message' => 'Proposta enviada com sucesso!']);
        }

        wp_send_json_error(['message' => 'Erro ao enviar proposta. Tente novamente.']);
    }
}
