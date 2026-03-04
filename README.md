# CDW Veículos (GestorCar WordPress)

Plugin WordPress que sincroniza veículos a partir da view **`crm_vehicles_v`** do banco **GestorCar** (`gstc_app`). Cria, atualiza e remove automaticamente os posts no WordPress conforme o status na origem; inclui listagens por marca, categoria, câmbio e combustível.

**Repositório:** [github.com/jhoudecarvalho/gestorcar-wordpress](https://github.com/jhoudecarvalho/gestorcar-wordpress)

---

## Requisitos

- WordPress 6.0+
- PHP 8.0+
- Acesso ao banco MySQL/MariaDB onde está a view `crm_vehicles_v` (banco `gstc_app`)

---

## Funcionalidades

- **Sincronização com a view**  
  Conecta ao banco externo, lê a view `crm_vehicles_v` e cria/atualiza posts do tipo `veiculo` no WordPress.

- **Apenas veículos publicados**  
  Importa somente registros com **`id_status = 1`** (publicado). Veículos despublicados na origem são removidos do site na próxima sincronização.

- **Filtro por empresa**  
  Opção em configurações para sincronizar só veículos de uma empresa (`id_company`).

- **Imagens externas**  
  As imagens não são importadas para a Media Library; são armazenadas como URLs (ex.: S3). Configurável: URL base e uso de domínio da empresa no path.

- **Sincronização automática (WP Cron)**  
  Agendamento configurável (15 min, 30 min, 1 h). Recomenda-se uso de cron real (`DISABLE_WP_CRON` + crontab).

- **Listagens públicas**  
  - **`/veiculos/`** — Lista todos os veículos publicados (com filtro por marca no template).  
  - **`/marca_veiculo/`** — Lista todas as marcas (FORD, FIAT, etc.).  
  - **`/categoria_veiculo/`** — Lista todas as categorias (ex.: CARRO, MOTO).  
  - **`/cambio_veiculo/`** — Lista todos os câmbios (ex.: MANUAL, AUTOMÁTICO).  
  - **`/combustivel_veiculo/`** — Lista todos os combustíveis (ex.: FLEX, GASOLINA).  
  Cada termo linka para a listagem de veículos daquele filtro (apenas publicados).

- **Taxonomias**  
  `marca_veiculo`, `modelo_veiculo`, `categoria_veiculo`, `tipo_veiculo`, `combustivel_veiculo`, `cambio_veiculo`, `acessorio_veiculo`.

- **Logs**  
  Registro das execuções de sincronização (total, criados, atualizados, removidos, erros) em CDW Veículos → Logs.

---

## Instalação

1. Clone ou baixe o repositório em `wp-content/plugins/` (a pasta do plugin deve se chamar `cdw-veiculos`).
2. Ative o plugin em **Plugins** no painel WordPress.
3. Acesse **CDW Veículos → Configurações**.
4. Preencha host, porta, database, usuário e senha do banco onde está a view `crm_vehicles_v`.
5. Clique em **Testar conexão**.
6. (Opcional) Defina **Sincronizar apenas empresa** e a **URL base das imagens**.
7. Clique em **Sincronizar agora** para a primeira importação.
8. Em **Ajustes → Links permanentes**, clique em **Salvar alterações** para registrar as regras de URL do plugin.

---

## Configuração de links permanentes

- **Não use** o slug **`/veiculos/`** como base dos produtos (WooCommerce). Use por exemplo **`/produto/`** ou **`/loja/`** para evitar conflito com o archive de veículos.
- Após alterar qualquer opção em **Ajustes → Links permanentes**, salve para que `/veiculos/`, `/marca_veiculo/`, `/categoria_veiculo/`, etc. funcionem corretamente.

---

## Estrutura do plugin

```
cdw-veiculos/
├── cdw-veiculos.php          # Bootstrap e ativação
├── includes/
│   ├── class-database.php    # Conexão PDO e queries na view
│   ├── class-cpt.php         # CPT veiculo e taxonomias
│   ├── class-sync.php        # Lógica de sincronização
│   └── class-scheduler.php   # Agendamento WP Cron
├── admin/
│   ├── class-admin.php
│   └── views/                # Configurações, mapeamento, logs
├── templates/
│   ├── archive-veiculo.php   # Listagem /veiculos/
│   ├── single-veiculo.php    # Página do veículo
│   └── taxonomy-lista.php    # Lista termos (marca, categoria, câmbio, combustível)
├── readme.txt
└── README.md
```

---

## Meta e mapeamento

Os dados da view são mapeados para post (título, conteúdo) e para `post_meta` com prefixo **`_cdw_`**, por exemplo: `_cdw_id_externo`, `_cdw_preco`, `_cdw_km`, `_cdw_ano_fab`, `_cdw_ano_mod`, `_cdw_cor`, `_cdw_placa`, `_cdw_portas`, `_cdw_imagens`. O mapeamento detalhado está em **CDW Veículos → Mapeamento** e pode ser conferido na documentação da view (ex.: `MAPEAMENTO-VIEW-GESTORCAR-CDW-VEICULOS.md` em projetos que o utilizem).

---

## Licença

GPL-2.0-or-later.
