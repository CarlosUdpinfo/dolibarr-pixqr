<?php
/**
 * Biblioteca de geração do BR Code (Pix Copia e Cola)
 * Padrão EMV / BACEN.
 */

if (!function_exists('pixqr_admin_prepare_head')) {
    function pixqr_admin_prepare_head()
    {
        global $langs, $conf;
        $h = 0;
        $head = array();
        $head[$h][0] = dol_buildpath("/pixqr/admin/setup.php", 1);
        $head[$h][1] = $langs->trans("Settings");
        $head[$h][2] = 'settings';
        $h++;
        $head[$h][0] = dol_buildpath("/pixqr/admin/about.php", 1);
        $head[$h][1] = $langs->trans("About");
        $head[$h][2] = 'about';
        return $head;
    }
}

/**
 * Formata um campo EMV: ID(2) + LEN(2) + VALUE
 */
function pixqr_emv_field($id, $value)
{
    $value = (string) $value;
    $len = str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT);
    $id  = str_pad((string) $id, 2, '0', STR_PAD_LEFT);
    return $id.$len.$value;
}

/**
 * Calcula o CRC16/CCITT-FALSE (polinômio 0x1021, init 0xFFFF) exigido pelo BR Code.
 */
function pixqr_crc16($payload)
{
    $crc = 0xFFFF;
    $len = strlen($payload);
    for ($i = 0; $i < $len; $i++) {
        $crc ^= (ord($payload[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) {
                $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
            } else {
                $crc = ($crc << 1) & 0xFFFF;
            }
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

/**
 * Remove acentos e caracteres não-ASCII (BR Code aceita apenas ASCII básico).
 */
function pixqr_sanitize($str, $max = null)
{
    $str = (string) $str;
    if (function_exists('iconv')) {
        $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        if ($tmp !== false) $str = $tmp;
    }
    $str = preg_replace('/[^A-Za-z0-9 \-\.\/\*\+\:\;\?\@\&\=\,\$\%\!\(\)\']/', '', $str);
    $str = trim(preg_replace('/\s+/', ' ', $str));
    if ($max !== null) $str = substr($str, 0, $max);
    return $str;
}

/**
 * Gera o payload do BR Code (Pix Copia e Cola) estático com valor.
 *
 * @param string $key       Chave Pix (CPF, e-mail, telefone, EVP)
 * @param float  $amount    Valor da cobrança
 * @param string $merchant  Nome do recebedor (max 25)
 * @param string $city      Cidade (max 15)
 * @param string $txid      Identificador (max 25, alfanumérico) - use "***" para padrão
 * @return string           BR Code pronto para QR Code
 */
function pixqr_build_brcode($key, $amount, $merchant, $city, $txid = '***')
{
    $key      = pixqr_sanitize($key, 77);
    $merchant = pixqr_sanitize($merchant ?: 'RECEBEDOR', 25);
    $city     = pixqr_sanitize($city ?: 'BRASIL', 15);
    $txid     = pixqr_sanitize($txid ?: '***', 25);
    if ($txid === '') $txid = '***';

    // Merchant Account Information (ID 26)
    $mai  = pixqr_emv_field('00', 'br.gov.bcb.pix');
    $mai .= pixqr_emv_field('01', $key);

    $payload  = pixqr_emv_field('00', '01');                  // Payload Format Indicator
    $payload .= pixqr_emv_field('26', $mai);                  // Merchant Account Info - Pix
    $payload .= pixqr_emv_field('52', '0000');                // Merchant Category Code
    $payload .= pixqr_emv_field('53', '986');                 // Currency (BRL)
    if ($amount > 0) {
        $payload .= pixqr_emv_field('54', number_format((float) $amount, 2, '.', ''));
    }
    $payload .= pixqr_emv_field('58', 'BR');                  // Country
    $payload .= pixqr_emv_field('59', $merchant);             // Merchant Name
    $payload .= pixqr_emv_field('60', $city);                 // Merchant City
    $payload .= pixqr_emv_field('62', pixqr_emv_field('05', $txid)); // Additional Data / txid
    $payload .= '6304';                                       // CRC16 placeholder
    $payload .= pixqr_crc16($payload);

    return $payload;
}
