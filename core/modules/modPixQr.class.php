<?php
/* Copyright (C) 2026
 * Módulo Pix QR Code para Faturas - Template Sponge
 */

/**
 *  \defgroup   pixqr     Module PixQr
 *  \brief      Módulo Dolibarr que adiciona QR Code Pix ao final da fatura (template Sponge)
 *  \file       htdocs/pixqr/core/modules/modPixQr.class.php
 *  \ingroup    pixqr
 *  \brief      Descrição e ativação do módulo PixQr
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modPixQr extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;

        $this->numero = 500001; // ID livre (>= 500000 para módulos externos)
        $this->rights_class = 'pixqr';
        $this->family = "billing";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Adiciona QR Code Pix ao final da impressão da fatura (template Sponge Pix)";
        $this->descriptionlong = "Plugin que disponibiliza um novo modelo de PDF de fatura idêntico ao Sponge, incluindo um QR Code Pix (BR Code) com o valor total da fatura. Inclui painel de configuração para chave Pix, nome do recebedor e cidade.";
        $this->editor_name = 'Pix QR Plugin';
        $this->editor_url = '';
        $this->version = '1.0.5';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'invoice';

        // 'models' => 1 faz o Dolibarr varrer este módulo procurando
        // geradores de documentos (PDF) em core/modules/*/doc/
        $this->module_parts = array(
            'models' => 1,
        );
        $this->dirs = array();

        $this->config_page_url = array("setup.php@pixqr");

        $this->hidden = false;
        $this->depends = array("modFacture");
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array("pixqr@pixqr");

        $this->const = array(
            0 => array('PIXQR_KEY',          'chaine', '',  'Chave Pix do recebedor', 0),
            1 => array('PIXQR_MERCHANT',     'chaine', '',  'Nome do recebedor (max 25)', 0),
            2 => array('PIXQR_CITY',         'chaine', '',  'Cidade do recebedor (max 15)', 0),
            3 => array('PIXQR_TXID',         'chaine', '***', 'Identificador da transação (txid)', 0),
            4 => array('PIXQR_ENABLE_SPONGE','chaine', '1','Habilitar modelo sponge_pix', 0),
        );

        $this->tabs = array();
        $this->dictionaries = array();

        $this->rights = array();
        $r = 0;
        $this->rights[$r][0] = 5000011;
        $this->rights[$r][1] = 'Configurar Pix QR';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'configure';

        $this->menu = array();
    }

    public function init($options = '')
    {
        global $conf;
        $sql = array();

        // Ativa o template sponge_pix na lista de modelos de fatura
        $this->_load_tables('/install/mysql/tables/');
        // Adiciona o nosso modelo como gerador de PDF de fatura
        $sql[] = "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom='sponge_pix' AND type='invoice' AND entity=".$conf->entity;
        $sql[] = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('sponge_pix','invoice',".$conf->entity.")";

        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        global $conf;
        $sql = array(
            "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom='sponge_pix' AND type='invoice' AND entity=".$conf->entity,
        );
        return $this->_remove($sql, $options);
    }
}
