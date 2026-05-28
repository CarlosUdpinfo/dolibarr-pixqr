# Pix QR Code para Dolibarr

Plugin que adiciona um modelo de PDF de fatura **idêntico ao Sponge** (herda
do template oficial via `class pdf_sponge_pix extends pdf_sponge`) e imprime
um **QR Code Pix (BR Code)** ao final da fatura, com o **valor total** já
preenchido.

## Recursos

- Painel de configuração em **Início → Configurar → Módulos → Pix QR**
  - Chave Pix (CPF/CNPJ, e-mail, telefone, EVP)
  - Nome do recebedor (máx. 25)
  - Cidade (máx. 15)
  - TXID (padrão `***`)
- Modelo de PDF `sponge_pix` (visual 100% Sponge + bloco Pix no rodapé)
- BR Code estático no padrão EMV/BACEN com CRC16 calculado
- Mostra também o **Pix Copia e Cola** em texto

## Instalação

1. Copie a pasta `pixqr` para dentro do diretório `custom/` da
   sua instalação Dolibarr (crie `custom/` se não existir e habilite
   `$dolibarr_main_url_root_alt` / `$dolibarr_main_document_root_alt` no
   `conf.php`).
2. Em **Início → Configuração → Módulos**, ative **Pix QR Code**.
3. Clique no ícone de engrenagem do módulo e preencha sua chave Pix.
4. Em **Faturas → Configuração**, ative o modelo **sponge_pix** como modelo
   padrão (ou selecione-o ao gerar o PDF).
5. Gere/regenere o PDF da fatura — o QR Code aparece no final.

## Estrutura

```
pixqr/
├── core/modules/modPixQr.class.php
├── core/modules/facture/doc/pdf_sponge_pix.modules.php
├── admin/setup.php
├── admin/about.php
├── lib/pixqr.lib.php
└── langs/{pt_BR,en_US}/pixqr.lang
```

## Compatibilidade

Testado conceitualmente em Dolibarr 17+ (usa TCPDF nativo `write2DBarcode`,
disponível em todas as versões recentes).

## Licença

GPL v3+ (mesma do Dolibarr).
