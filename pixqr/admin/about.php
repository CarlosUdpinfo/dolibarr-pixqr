<?php
$res = 0;
if (! $res && file_exists("../../main.inc.php"))    $res = @include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once dol_buildpath('/pixqr/lib/pixqr.lib.php');

if (! $user->admin) accessforbidden();

llxHeader('', 'Pix QR - Sobre');
print load_fiche_titre('Pix QR Code', '', 'object_invoice');
$head = pixqr_admin_prepare_head();
print dol_get_fiche_head($head, 'about', 'Pix QR', -1, 'invoice');

print '<p><b>Pix QR Code para Faturas</b> – versão 1.0.5</p>';
print '<p>Adiciona um modelo de PDF de fatura idêntico ao <b>Sponge</b>, com um QR Code Pix (BR Code) no final do documento, contendo o valor total da fatura.</p>';
print '<ul>';
print '<li>Modelo PDF: <code>sponge_pix</code></li>';
print '<li>BR Code estático (EMV / BACEN) com CRC16 calculado</li>';
print '<li>Compatível com qualquer app bancário brasileiro</li>';
print '</ul>';

print dol_get_fiche_end();
llxFooter();
$db->close();
