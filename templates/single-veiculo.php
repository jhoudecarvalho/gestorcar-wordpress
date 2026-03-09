<?php
/**
 * Template: Página individual do veículo
 * Plugin: CDW Veículos
 */
defined('ABSPATH') || exit;
get_header();

while (have_posts()) :
    the_post();
    $post_id    = get_the_ID();

    // Meta fields
    $preco      = (float)  get_post_meta($post_id, '_cdw_preco',      true);
    $km         = (int)    get_post_meta($post_id, '_cdw_km',         true);
    $ano_fab    = (int)    get_post_meta($post_id, '_cdw_ano_fab',     true);
    $ano_mod    = (int)    get_post_meta($post_id, '_cdw_ano_mod',     true);
    $cor        =          get_post_meta($post_id, '_cdw_cor',         true);
    $placa      =          get_post_meta($post_id, '_cdw_placa',       true);
    $alias      =          get_post_meta($post_id, '_cdw_alias',       true);
    $id_externo =          get_post_meta($post_id, '_cdw_id_externo',  true);
    $destaque   = (bool)   get_post_meta($post_id, '_cdw_destaque',    true);
    $video      =          get_post_meta($post_id, '_cdw_video',       true);
    $imagens    = (array)  get_post_meta($post_id, '_cdw_imagens',     true);
    $imagens    = array_filter($imagens);

    // Taxonomias
    $get_term = function(string $tax) use ($post_id): string {
        $terms = wp_get_object_terms($post_id, $tax, ['fields' => 'names']);
        return (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : '';
    };
    $marca       = $get_term('marca_veiculo');
    $cambio      = $get_term('cambio_veiculo');
    $combustivel = $get_term('combustivel_veiculo');
    $acessorios  = wp_get_object_terms($post_id, 'acessorio_veiculo', ['fields' => 'names']);
    $acessorios  = is_wp_error($acessorios) ? [] : $acessorios;

    // Formatações
    $preco_f     = 'R$ ' . number_format($preco, 2, ',', '.');
    $km_f        = number_format($km, 0, ',', '.') . ' km';
    $final_placa = $placa ? substr(preg_replace('/[^0-9]/', '', $placa), -1) : '-';
    // Se não tem alias meta, usa o post_name
    if (empty($alias)) {
        $alias = get_post_field('post_name', $post_id);
    }
    $url_fin     = home_url('/financiar-automovel/' . $alias . '-' . $id_externo);
    $url_estoque = get_post_type_archive_link('veiculo');
?>

<div class="cdw-single-wrap">

    <!-- TÍTULO -->
    <h1 class="cdw-single-titulo"><?php the_title(); ?></h1>

    <!-- BREADCRUMB -->
    <nav class="cdw-breadcrumb">
        <a href="<?php echo home_url('/'); ?>">PÁGINA INICIAL</a> /
        <a href="<?php echo esc_url($url_estoque); ?>">NOSSO ESTOQUE</a> /
        <span><?php echo esc_html(strtoupper($marca)); ?></span>
    </nav>

    <!-- GRID PRINCIPAL -->
    <div class="cdw-single-grid">

        <!-- COLUNA 1: GALERIA -->
        <div class="cdw-col-galeria">
            <?php if (!empty($imagens)) : ?>
                <div class="cdw-galeria-wrap">
                    <div class="cdw-galeria-main">
                        <button class="cdw-gal-prev">&#8249;</button>
                        <img id="cdw-img-principal"
                             src="<?php echo esc_url(array_values($imagens)[0]); ?>"
                             alt="<?php the_title_attribute(); ?>"
                             loading="eager" />
                        <button class="cdw-gal-next">&#8250;</button>
                    </div>
                    <div class="cdw-gal-dots">
                        <?php foreach (array_values($imagens) as $i => $url) : ?>
                            <span class="cdw-dot <?php echo $i === 0 ? 'ativo' : ''; ?>"
                                  data-index="<?php echo $i; ?>"
                                  data-src="<?php echo esc_url($url); ?>">
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <p class="cdw-foto-count">
                        📷 <?php echo count($imagens); ?> Fotos
                    </p>
                </div>
            <?php else : ?>
                <div class="cdw-sem-foto">🚗 Sem fotos disponíveis</div>
            <?php endif; ?>

            <!-- INFORMAÇÕES GERAIS -->
            <?php if (get_the_content()) : ?>
            <div class="cdw-info-gerais">
                <h2>INFORMAÇÕES GERAIS</h2>
                <?php the_content(); ?>
            </div>
            <?php else: ?>
            <div class="cdw-info-gerais">
                <h2>INFORMAÇÕES GERAIS</h2>
                <p>.</p>
            </div>
            <?php endif; ?>

            <!-- OPCIONAIS -->
            <?php if (!empty($acessorios)) : ?>
            <div class="cdw-opcionais">
                <h2>OPCIONAIS</h2>
                <ul class="cdw-opcionais-grid">
                    <?php foreach ($acessorios as $item) : ?>
                        <li>✅ <?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- COLUNA 2: DADOS -->
        <div class="cdw-col-dados">

            <!-- Preço -->
            <div class="cdw-preco-box">
                <?php echo esc_html($preco_f); ?>
            </div>

            <!-- Dados lista -->
            <ul class="cdw-dados-lista">
                <?php if ($ano_fab) : ?>
                <li>
                    <span class="cdw-dado-icon">📅</span>
                    <span class="cdw-dado-label">Ano:</span>
                    <strong><?php echo "{$ano_fab}/{$ano_mod}"; ?></strong>
                </li>
                <?php endif; ?>
                <?php if ($cambio) : ?>
                <li>
                    <span class="cdw-dado-icon">⚙️</span>
                    <span class="cdw-dado-label">Câmbio:</span>
                    <strong><?php echo esc_html(strtoupper($cambio)); ?></strong>
                </li>
                <?php endif; ?>
                <?php if ($combustivel) : ?>
                <li>
                    <span class="cdw-dado-icon">⛽</span>
                    <span class="cdw-dado-label">Combustível:</span>
                    <strong><?php echo esc_html(strtoupper($combustivel)); ?></strong>
                </li>
                <?php endif; ?>
                <?php if ($cor) : ?>
                <li>
                    <span class="cdw-dado-icon">🎨</span>
                    <span class="cdw-dado-label">Cor:</span>
                    <strong><?php echo esc_html(strtoupper($cor)); ?></strong>
                </li>
                <?php endif; ?>
                <?php if ($km) : ?>
                <li>
                    <span class="cdw-dado-icon">🛣️</span>
                    <span class="cdw-dado-label">KM:</span>
                    <strong><?php echo esc_html($km_f); ?></strong>
                </li>
                <?php endif; ?>
                <?php if ($placa) : ?>
                <li>
                    <span class="cdw-dado-icon">🔢</span>
                    <span class="cdw-dado-label">Final de Placa:</span>
                    <strong><?php echo esc_html($final_placa); ?></strong>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Vídeo -->
            <?php if ($video) : ?>
            <div class="cdw-video-wrap">
                <iframe src="<?php echo esc_url($video); ?>"
                        frameborder="0" allowfullscreen loading="lazy"></iframe>
            </div>
            <?php endif; ?>

            <!-- Botões -->
            <a href="<?php echo esc_url($url_fin); ?>"
               class="cdw-btn-acao cdw-btn-financiar">
                💰 FINANCIAR
            </a>
            <a href="<?php echo esc_url($url_estoque); ?>"
               class="cdw-btn-acao cdw-btn-ofertas">
                🚗 OUTRAS OFERTAS
            </a>
        </div>

        <!-- COLUNA 3: FORMULÁRIO -->
        <div class="cdw-col-form">
            <?php echo \CDW_Contact_Form::render($post_id); ?>
        </div>

    </div><!-- .cdw-single-grid -->
</div><!-- .cdw-single-wrap -->

<style>
/* ── Wrap geral ── */
.cdw-single-wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 20px;
    font-family: sans-serif;
}

/* ── Título ── */
.cdw-single-titulo {
    text-align: center;
    font-size: clamp(18px, 2.5vw, 26px);
    font-weight: 800;
    margin-bottom: 8px;
}

/* ── Breadcrumb ── */
.cdw-breadcrumb {
    font-size: 12px;
    color: #888;
    margin-bottom: 20px;
}
.cdw-breadcrumb a {
    color: #888;
    text-decoration: none;
}
.cdw-breadcrumb a:hover { text-decoration: underline; }

/* ── Grid 3 colunas ── */
.cdw-single-grid {
    display: grid;
    grid-template-columns: 2fr 1.2fr 1.5fr;
    gap: 24px;
    align-items: start;
}

/* ── Coluna 3 sticky ── */
.cdw-col-form {
    position: sticky;
    top: 20px;
}

/* ── Galeria ── */
.cdw-galeria-wrap { margin-bottom: 24px; }
.cdw-galeria-main {
    position: relative;
    background: #f0f0f0;
    border-radius: 8px;
    overflow: hidden;
}
.cdw-galeria-main img {
    width: 100%;
    aspect-ratio: 4/3;
    object-fit: cover;
    display: block;
    transition: opacity .3s;
}
.cdw-gal-prev,
.cdw-gal-next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0,0,0,.45);
    color: #fff;
    border: none;
    font-size: 28px;
    padding: 6px 14px;
    cursor: pointer;
    border-radius: 4px;
    z-index: 2;
}
.cdw-gal-prev { left: 8px; }
.cdw-gal-next { right: 8px; }
.cdw-gal-dots {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-top: 10px;
    flex-wrap: wrap;
}
.cdw-dot {
    width: 10px;
    height: 10px;
    background: #ccc;
    border-radius: 50%;
    cursor: pointer;
    transition: background .2s;
}
.cdw-dot.ativo { background: #1a1a1a; }
.cdw-foto-count {
    text-align: center;
    font-size: 13px;
    color: #666;
    margin-top: 6px;
}
.cdw-sem-foto {
    background: #f5f5f5;
    aspect-ratio: 4/3;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    border-radius: 8px;
    color: #aaa;
    margin-bottom: 24px;
}

/* ── Preço ── */
.cdw-preco-box {
    background: #f0faf0;
    border: 1px solid #b2dfb2;
    border-radius: 8px;
    text-align: center;
    font-size: clamp(20px, 2.5vw, 28px);
    font-weight: 800;
    color: #1a7f37;
    padding: 18px;
    margin-bottom: 16px;
}

/* ── Lista de dados ── */
.cdw-dados-lista {
    list-style: none;
    padding: 0;
    margin: 0 0 20px;
    border: 1px solid #eee;
    border-radius: 8px;
    overflow: hidden;
}
.cdw-dados-lista li {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    font-size: 14px;
    border-bottom: 1px solid #eee;
}
.cdw-dados-lista li:last-child { border-bottom: none; }
.cdw-dado-label { color: #555; flex: 1; }
.cdw-dados-lista strong { font-weight: 700; }

/* ── Botões ── */
.cdw-btn-acao {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 14px;
    margin-bottom: 10px;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    box-sizing: border-box;
}
.cdw-btn-financiar  { background: #1a1a1a; color: #fff; }
.cdw-btn-ofertas    { background: #2a2a2a; color: #fff; }
.cdw-btn-financiar:hover { background: #333; color: #fff; }
.cdw-btn-ofertas:hover   { background: #444; color: #fff; }

/* ── Vídeo ── */
.cdw-video-wrap {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    margin-bottom: 16px;
    border-radius: 8px;
}
.cdw-video-wrap iframe {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
}

/* ── Info gerais ── */
.cdw-info-gerais { margin-bottom: 28px; }
.cdw-info-gerais h2 { font-size: 18px; font-weight: 800; margin-bottom: 12px; }

/* ── Opcionais ── */
.cdw-opcionais h2 { font-size: 18px; font-weight: 800; margin-bottom: 12px; }
.cdw-opcionais-grid {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.cdw-opcionais-grid li {
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ── Responsivo ── */
@media (max-width: 1024px) {
    .cdw-single-grid {
        grid-template-columns: 1fr 1fr;
    }
    .cdw-col-form {
        grid-column: 1 / -1;
        position: static;
    }
}
@media (max-width: 640px) {
    .cdw-single-grid { grid-template-columns: 1fr; }
    .cdw-col-form    { position: static; }
    .cdw-opcionais-grid { grid-template-columns: 1fr; }
}
</style>

<script>
(function() {
    var imgs    = <?php echo wp_json_encode(array_values($imagens)); ?>;
    var atual   = 0;
    var img     = document.getElementById('cdw-img-principal');
    var dots    = document.querySelectorAll('.cdw-dot');

    function goTo(index) {
        if (!imgs.length) return;
        atual = (index + imgs.length) % imgs.length;
        img.style.opacity = '0';
        setTimeout(function() {
            img.src = imgs[atual];
            img.style.opacity = '1';
        }, 150);
        dots.forEach(function(d, i) {
            d.classList.toggle('ativo', i === atual);
        });
    }

    dots.forEach(function(dot) {
        dot.addEventListener('click', function() {
            goTo(parseInt(this.dataset.index));
        });
    });

    var prev = document.querySelector('.cdw-gal-prev');
    var next = document.querySelector('.cdw-gal-next');
    if (prev) prev.addEventListener('click', function() { goTo(atual - 1); });
    if (next) next.addEventListener('click', function() { goTo(atual + 1); });
})();
</script>

<?php endwhile; ?>
<?php get_footer(); ?>