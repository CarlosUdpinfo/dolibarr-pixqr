<?php
/**
 * Página de configuração do módulo PixQr
 */
$res = 0;
if (! $res && file_exists("../../main.inc.php"))       $res = @include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php"))    $res = @include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once dol_buildpath('/pixqr/lib/pixqr.lib.php');

global $langs, $user, $conf, $db;
$langs->loadLangs(array("admin", "bills"));

if (! $user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');

if ($action == 'save') {
    dolibarr_set_const($db, 'PIXQR_KEY',      GETPOST('PIXQR_KEY', 'alphanohtml'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'PIXQR_MERCHANT', GETPOST('PIXQR_MERCHANT', 'alphanohtml'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'PIXQR_CITY',     GETPOST('PIXQR_CITY', 'alphanohtml'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'PIXQR_TXID',     GETPOST('PIXQR_TXID', 'alphanohtml') ?: '***', 'chaine', 0, '', $conf->entity);
    setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
    header("Location: ".$_SERVER["PHP_SELF"]);
    exit;
}

llxHeader('', 'Pix QR - Configuração');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre('Pix QR Code – Configuração', $linkback, 'object_invoice');

$head = pixqr_admin_prepare_head();
print dol_get_fiche_head($head, 'settings', 'Pix QR', -1, 'invoice');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>Parâmetro</td><td>Valor</td></tr>';

print '<tr class="oddeven"><td><label for="PIXQR_KEY">Chave Pix</label><br><span class="opacitymedium">CPF/CNPJ, e-mail, telefone (+5511...) ou chave aleatória</span></td>';
print '<td><input type="text" name="PIXQR_KEY" id="PIXQR_KEY" size="60" value="'.dol_escape_htmltag(getDolGlobalString('PIXQR_KEY')).'"></td></tr>';

print '<tr class="oddeven"><td><label for="PIXQR_MERCHANT">Nome do recebedor</label><br><span class="opacitymedium">Máx. 25 caracteres, sem acentos</span></td>';
print '<td><input type="text" name="PIXQR_MERCHANT" id="PIXQR_MERCHANT" size="40" maxlength="25" value="'.dol_escape_htmltag(getDolGlobalString('PIXQR_MERCHANT')).'"></td></tr>';

print '<tr class="oddeven"><td><label for="PIXQR_CITY">Cidade</label><br><span class="opacitymedium">Máx. 15 caracteres, sem acentos</span></td>';
print '<td><input type="text" name="PIXQR_CITY" id="PIXQR_CITY" size="20" maxlength="15" value="'.dol_escape_htmltag(getDolGlobalString('PIXQR_CITY')).'"></td></tr>';

print '<tr class="oddeven"><td><label for="PIXQR_TXID">TXID</label><br><span class="opacitymedium">Identificador da transação (padrão "***")</span></td>';
print '<td><input type="text" name="PIXQR_TXID" id="PIXQR_TXID" size="25" maxlength="25" value="'.dol_escape_htmltag(getDolGlobalString('PIXQR_TXID', '***')).'"></td></tr>';

print '</table>';

print '<div class="center" style="margin-top:15px"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';
print '</form>';

print '<br><div class="info">Após salvar, gere uma nova fatura usando o modelo <b>sponge_pix</b> em <i>Faturas &gt; Modelo de documento</i>. O QR Code aparecerá ao final do PDF com o valor total da fatura.</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
