<?php
/* Copyright (C) 2026 — Pix QR Plugin
 *
 * Modelo PDF que herda 100% do template oficial "Sponge" do Dolibarr
 * (pdf_sponge.modules.php, GPL) e adiciona um QR Code Pix (BR Code)
 * com o valor total da fatura no final do documento.
 */

dol_include_once('/core/modules/facture/doc/pdf_sponge.modules.php');
dol_include_once('/pixqr/lib/pixqr.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

class pdf_sponge_pix extends pdf_sponge
{
    /** Flag para garantir que o QR seja desenhado apenas uma vez por documento */
    public $pixqr_drawn = false;

    /** Altura reservada no rodapé da última página para o bloco Pix */
    public $pixqr_reserved_height = 52;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->name        = "sponge_pix";
        $this->description = "Modelo Sponge com QR Code Pix ao final (BR Code)";
    }

    /**
     * Sobrescreve write_file para resetar o flag e capturar o objeto.
     */
    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        $this->pixqr_drawn   = false;
        $this->pixqr_object  = $object;
        dol_syslog("pdf_sponge_pix::write_file called for invoice ".(isset($object->ref) ? $object->ref : '?'), LOG_INFO);
        return parent::write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref);
    }

    protected function drawTotalTable(&$pdf, $object, $deja_regle, $posy, $outputlangs, $outputlangsbis)
    {
        $newposy = parent::drawTotalTable($pdf, $object, $deja_regle, $posy, $outputlangs, $outputlangsbis);
        $this->_pixqr_draw($pdf, $object, $newposy + 4, $outputlangs);
        return max($newposy, $this->_pixqr_get_reserved_bottom_y($pdf));
    }

    public function drawPaymentsTable(&$pdf, $object, $posy, $outputlangs)
    {
        $newposy = parent::drawPaymentsTable($pdf, $object, $posy, $outputlangs);
        $this->_pixqr_draw($pdf, $object, $newposy + 4, $outputlangs);
        return max($newposy, $this->_pixqr_get_reserved_bottom_y($pdf));
    }

    protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0, $heightforqrinvoice = 0)
    {
        global $conf;

        $previousPageNumberX = isset($conf->global->PDF_FOOTER_PAGE_NUMBER_X) ? $conf->global->PDF_FOOTER_PAGE_NUMBER_X : null;
        $conf->global->PDF_FOOTER_PAGE_NUMBER_X = -500;

        $ret = parent::_pagefoot($pdf, $object, $outputlangs, $hidefreetext, $heightforqrinvoice);

        if ($previousPageNumberX === null) {
            unset($conf->global->PDF_FOOTER_PAGE_NUMBER_X);
        } else {
            $conf->global->PDF_FOOTER_PAGE_NUMBER_X = $previousPageNumberX;
        }

        return $ret;
    }

    protected function getHeightForQRInvoice(int $pagenbr, Facture $object, Translate $langs)
    {
        return $this->pixqr_reserved_height;
    }

    /**
     * Desenha o bloco do QR Code Pix com o valor total da fatura.
     */
    protected function _pixqr_draw(&$pdf, $object, $posy, $outputlangs)
    {
        if ($this->pixqr_drawn) return;

        $key      = trim((string) getDolGlobalString('PIXQR_KEY'));
        $merchant = trim((string) getDolGlobalString('PIXQR_MERCHANT'));
        $city     = trim((string) getDolGlobalString('PIXQR_CITY'));
        $txid     = trim((string) getDolGlobalString('PIXQR_TXID'));
        if ($txid === '') $txid = '***';
        if ($merchant === '') $merchant = 'RECEBEDOR';
        if ($city === '')     $city     = 'SAO PAULO';

        $amount = (float) (isset($object->total_ttc) ? $object->total_ttc : 0);

        $qrSize = 35; // mm
        $x = $this->marge_gauche;
        $y = $posy;

        // Nunca adiciona página aqui: o Sponge calcula layout antes do rodapé/totais.
        // Apenas ancora o bloco na faixa reservada do final da última página.
        $reservedTop = $this->_pixqr_get_reserved_top_y($pdf);
        if ($y < $reservedTop) {
            $y = $reservedTop;
        }

        // Caixa
        $boxW = 190;
        $pdf->SetDrawColor(120, 120, 120);
        $pdf->SetLineWidth(0.2);
        $pdf->Rect($x, $y, $boxW, $qrSize + 10);

        $tx = $x + $qrSize + 8;
        $ty = $y + 4;
        $pdf->SetTextColor(0, 0, 0);

        if (empty($key) || $amount <= 0) {
            // Aviso visível em vez de silenciar — facilita diagnóstico
            $pdf->SetFont('', 'B', 10);
            $pdf->SetXY($x + 2, $y + 4);
            $pdf->Cell($boxW - 4, 5, 'Pix QR Code', 0, 1, 'L');
            $pdf->SetFont('', '', 8);
            $pdf->SetXY($x + 2, $y + 11);
            $msg = empty($key)
                ? 'Configure a chave Pix em: Configuração > Módulos > Pix QR Code > Configurar.'
                : 'Valor da fatura é zero — QR Code não gerado.';
            $pdf->MultiCell($boxW - 4, 4, $msg, 0, 'L');
            $this->pixqr_drawn = true;
            dol_syslog("pdf_sponge_pix: QR não gerado (key=".(empty($key)?'vazia':'ok').", amount=$amount)", LOG_WARNING);
            return;
        }

        $brcode = pixqr_build_brcode($key, $amount, $merchant, $city, $txid);

        // QR Code (TCPDF nativo)
        $style = array(
            'border'  => false,
            'padding' => 0,
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false,
        );
        $pdf->write2DBarcode($brcode, 'QRCODE,M', $x + 2, $y + 5, $qrSize, $qrSize, $style, 'N');

        // Texto ao lado
        $pdf->SetFont('', 'B', 11);
        $pdf->SetXY($tx, $ty);
        $pdf->Cell($boxW - $qrSize - 12, 5, 'Pague com Pix', 0, 1, 'L');

        $pdf->SetFont('', '', 8);
        $pdf->SetXY($tx, $ty + 6);
        $pdf->Cell($boxW - $qrSize - 12, 4, 'Escaneie o QR Code com o app do seu banco', 0, 1, 'L');

        $pdf->SetFont('', 'B', 10);
        $pdf->SetXY($tx, $ty + 12);
        $pdf->Cell($boxW - $qrSize - 12, 5, 'Valor: R$ '.number_format($amount, 2, ',', '.'), 0, 1, 'L');

        $pdf->SetFont('', '', 7);
        $pdf->SetXY($tx, $ty + 18);
        $pdf->Cell($boxW - $qrSize - 12, 4, 'Chave Pix: '.$key, 0, 1, 'L');

        // Pix Copia e Cola
        $pdf->SetFont('courier', '', 5.5);
        $pdf->SetXY($tx, $ty + 23);
        $pdf->MultiCell($boxW - $qrSize - 12, 2.6, "Pix Copia e Cola:\n".$brcode, 0, 'L');

        $this->pixqr_drawn = true;
        dol_syslog("pdf_sponge_pix: QR Pix desenhado com sucesso (valor=$amount)", LOG_INFO);
    }

    protected function _pixqr_get_reserved_top_y(&$pdf)
    {
        return $pdf->getPageHeight() - $this->heightforfooter - $this->heightforfreetext - $this->pixqr_reserved_height + 1;
    }

    protected function _pixqr_get_reserved_bottom_y(&$pdf)
    {
        return $this->_pixqr_get_reserved_top_y($pdf) + $this->pixqr_reserved_height;
    }
}
