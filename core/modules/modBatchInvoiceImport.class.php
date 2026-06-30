<?php
/* Copyright (C) ---Replace with your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \defgroup   batchinvoiceimport     Module BatchInvoiceImport
 * \brief      Batch Invoice Import module descriptor.
 *
 * \file       htdocs/custom/batchinvoiceimport/core/modules/modBatchInvoiceImport.class.php
 * \ingroup    batchinvoiceimport
 * \brief      Description and activation file for module Batch Invoice Import
 */

include_once DOL_DOCUMENT_ROOT."/core/modules/DolibarrModules.class.php";

/**
 * Description and activation class for module Batch Invoice Import.
 */
class modBatchInvoiceImport extends DolibarrModules {
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions and menus.
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db) {
		global $conf;
		$this->db = $db;

		// Private/local development id. Reserve a proper official id before distributing this module.
		$this->numero				   = 502400;
		$this->rights_class			   = "batchinvoiceimport";
		$this->family				   = "financial";
		$this->module_position		   = "90";
		$this->name					   = preg_replace('/^mod/i','',get_class($this));
		$this->description			   = "ModuleBatchInvoiceImportDesc";
		$this->descriptionlong		   = "ModuleBatchInvoiceImportDesc";
		$this->editor_name			   = "";
		$this->editor_url			   = "";
		$this->editor_squarred_logo	   = "";
		$this->version				   = "0.1.0";
		$this->const_name			   = "MAIN_MODULE_".strtoupper($this->name);
		$this->picto				   = "bill";
		$this->module_parts			   = array(
			'triggers'			=> 0,
			'login'				=> 0,
			'substitutions'		=> 0,
			'menus'				=> 0,
			'tpl'				=> 0,
			'barcode'			=> 0,
			'models'			=> 0,
			'printing'			=> 0,
			'theme'				=> 0,
			'css'				=> array(),
			'js'				=> array(),
			'hooks'				=> array(),
			'moduleforexternal'	=> 0,
			'websitetemplates'	=> 0,
			'captcha'			=> 0,
		);
		$this->dirs					   = array('/batchinvoiceimport/temp');
		$this->config_page_url		   = array('setup.php@batchinvoiceimport');
		$this->hidden				   = getDolGlobalInt('MODULE_BATCHINVOICEIMPORT_DISABLED');
		$this->depends				   = array();
		$this->requiredby			   = array();
		$this->conflictwith			   = array();
		$this->langfiles			   = array('batchinvoiceimport@batchinvoiceimport');
		$this->phpmin				   = array(7,3);
		// Dolibarr 24.0 beta or higher for local development. Change to array(24, 0, 0) when targeting stable Dolibarr 24 final.
		$this->need_dolibarr_version   = array(24,0,-4);
		$this->need_javascript_ajax	   = 0;
		$this->warnings_activation	   = array();
		$this->warnings_activation_ext = array();
		$this->const				   = array();
		if(!isModEnabled('batchinvoiceimport')) {
			$conf->batchinvoiceimport		   = new stdClass();
			$conf->batchinvoiceimport->enabled = 0;
		}
		$this->tabs			 = array();
		$this->dictionaries	 = array();
		$this->boxes		 = array();
		$this->cronjobs		 = array();
		$this->rights		 = array();
		$r					 = 0;
		$this->rights[$r][0] = $this->numero."01";
		$this->rights[$r][1] = "Read Batch Invoice Import module";
		$this->rights[$r][2] = "r";
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = "read";
		$r++;
		$this->menu			 = array();
		$r					 = 0;
		$this->menu[$r++]	 = array(
			'fk_menu' => '',
			'type'	   => 'top',
			'titre'	   => 'BatchInvoiceImport',
			'prefix'   => img_picto('',$this->picto,'class="pictofixedwidth valignmiddle"'),
			'mainmenu' => 'batchinvoiceimport',
			'leftmenu' => 'batchinvoiceimport',
			'url'	   => '/batchinvoiceimport/index.php',
			'langs'	   => 'batchinvoiceimport@batchinvoiceimport',
			'position' => 1000 + $r,
			'enabled'  => "isModEnabled('batchinvoiceimport')",
			'perms'    => '($user->admin || $user->hasRight("batchinvoiceimport","read"))',
			'target'   => '',
			'user'	   => 2,
		);
	}

	/**
	 * Function called when module is enabled.
	 *
	 * @param	string	$options	Options when enabling module
	 * @return	int<-1,1>			1 if OK, <=0 if KO
	 */
	public function init($options='') {
		$sql = array();
		return $this->_init($sql,$options);
	}

	/**
	 * Function called when module is disabled.
	 *
	 * @param	string	$options	Options when disabling module
	 * @return	int<-1,1>			1 if OK, <=0 if KO
	 */
	public function remove($options='') {
		$sql = array();
		return $this->_remove($sql,$options);
	}
}
