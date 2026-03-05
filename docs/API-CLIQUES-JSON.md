# API REST — Resumo de cliques (JSON)

Documentação do endpoint JSON do plugin CDW Veículos para consultar **ID externo** e **cliques** (visualizações) de cada veículo publicado.

---

## Endpoint

| Método | URL |
|--------|-----|
| **GET** | `{site}/wp-json/cdw-veiculos/v1/cliques/resumo` |

**Exemplo:**  
`https://seusite.com.br/wp-json/cdw-veiculos/v1/cliques/resumo`

---

## Autenticação (opcional)

Se uma **Chave da API** estiver configurada em **CDW Veículos → Configurações → API REST**, envie-a no header:

| Header | Valor |
|--------|--------|
| **X-CDW-API-Key** | A chave definida nas configurações do plugin |

- **Chave vazia nas configurações:** o endpoint fica aberto (não é obrigatório enviar o header).
- **Chave preenchida:** todas as requisições devem incluir `X-CDW-API-Key` com o valor correto.

---

## Exemplo de requisição (cURL)

**Sem chave (quando não configurada):**
```bash
curl -X GET "https://seusite.com.br/wp-json/cdw-veiculos/v1/cliques/resumo"
```

**Com chave:**
```bash
curl -X GET "https://seusite.com.br/wp-json/cdw-veiculos/v1/cliques/resumo" \
  -H "X-CDW-API-Key: SUA_CHAVE_AQUI"
```

---

## Formato da resposta

**Content-Type:** `application/json`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| **gerado_em** | string (ISO 8601) | Data/hora da geração da resposta |
| **total** | int | Quantidade de veículos na lista |
| **veiculos** | array | Lista de objetos `{ id, cliques }` |

Cada item em **veiculos**:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| **id** | int | ID externo do veículo (`_cdw_id_externo`), **não** é o `post_id` do WordPress |
| **cliques** | int | Total de visualizações (page views) da página do veículo |

A lista vem **ordenada por cliques em ordem decrescente** (mais cliques primeiro).  
Só entram veículos com **post_status = publish**.

---

## Exemplo de resposta (200 OK)

```json
{
  "gerado_em": "2025-01-15T14:30:00-03:00",
  "total": 150,
  "veiculos": [
    { "id": 70152, "cliques": 87 },
    { "id": 50004, "cliques": 43 },
    { "id": 50005, "cliques": 12 },
    { "id": 50006, "cliques": 0 }
  ]
}
```

---

## Observações

- O **id** é sempre o **ID externo** do veículo (origem GestorCar), armazenado em `_cdw_id_externo`.  
  Ex.: URL `/veiculos/toro-1-3-t270-freedom-aut-70152/` → na resposta esse veículo aparece como `"id": 70152`.
- Veículos sem nenhuma visualização aparecem com **"cliques": 0**.
- O endpoint usa apenas dados do WordPress (post_meta); não consulta o banco externo.
