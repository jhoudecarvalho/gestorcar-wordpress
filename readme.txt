=== CDW Veiculos (GestorCar WordPress) ===
Contributors: cdwtech, jhoudecarvalho
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
Plugin URI: https://github.com/jhoudecarvalho/gestorcar-wordpress

Sincroniza veiculos da view crm_vehicles_v (GestorCar/gstc_app) com o WordPress. Importa apenas veiculos publicados (id_status=1); remove os que deixaram de ser publicados. Inclui listagens em /veiculos/, /marca_veiculo/, /categoria_veiculo/, /cambio_veiculo/, /combustivel_veiculo/.

== Funcionalidades ==

* Sincronizacao com a view crm_vehicles_v (banco externo)
* Apenas veiculos publicados (id_status = 1)
* Filtro opcional por empresa (id_company)
* Imagens como URL externa (configuravel, ex. S3)
* Sincronizacao automatica via WP Cron (15 min, 30 min, 1 h)
* Listagens: /veiculos/, /marca_veiculo/, /categoria_veiculo/, /cambio_veiculo/, /combustivel_veiculo/
* Taxonomias: marca_veiculo, modelo_veiculo, categoria_veiculo, tipo_veiculo, combustivel_veiculo, cambio_veiculo, acessorio_veiculo
* Logs de cada execucao de sync

== Instalacao ==

1. Copie a pasta `cdw-veiculos` para `/wp-content/plugins/`
2. Ative o plugin no painel WordPress
3. Va em CDW Veiculos > Configuracoes
4. Preencha os dados de conexao com o banco (view crm_vehicles_v)
5. Clique em "Testar Conexao"
6. (Opcional) Defina empresa e URL base das imagens
7. Clique em "Sincronizar Agora" para o primeiro sync
8. Em Ajustes > Links permanentes, clique em Salvar alteracoes

== Campos Meta Gerados ==

Todos os campos ficam em post_meta com prefixo `_cdw_`:
- _cdw_id_externo   : ID unico do sistema externo
- _cdw_preco        : Preco
- _cdw_km           : Quilometragem
- _cdw_ano_fab      : Ano de fabricacao
- _cdw_ano_mod      : Ano do modelo
- _cdw_cor          : Cor
- _cdw_placa        : Placa
- _cdw_portas       : Numero de portas
- _cdw_potencia     : Potencia em cv
- _cdw_codigo_fipe  : Codigo FIPE
- _cdw_imagens      : Array de URLs de imagens

== Taxonomias Registradas ==

- marca_veiculo
- modelo_veiculo
- categoria_veiculo
- tipo_veiculo
- combustivel_veiculo
- cambio_veiculo
- acessorio_veiculo

== Cron Real (Recomendado) ==

Adicione em wp-config.php:
  define('DISABLE_WP_CRON', true);

Adicione no crontab do servidor:
  */30 * * * * wget -q -O - https://seusite.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1

== Compatibilidade ==

Compativel com ACF, Elementor, WooCommerce e qualquer tema WordPress padrao.
