<?php
/* Copyright (C) ---Replace with your own copyright and developer email---
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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
 * \file    htdocs/custom/batchinvoiceimport/preview.php
 * \ingroup batchinvoiceimport
 * \brief   Template validation preview for Batch Invoice Import.
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
	$res = @include str_replace('..', '', $_SERVER['CONTEXT_DOCUMENT_ROOT']).'/main.inc.php';
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)).'/main.inc.php')) {
	$res = @include substr($tmp, 0, ($i + 1)).'/main.inc.php';
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))).'/main.inc.php')) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))).'/main.inc.php';
}
if (!$res && file_exists('../main.inc.php')) {
	$res = @include '../main.inc.php';
}
if (!$res && file_exists('../../main.inc.php')) {
	$res = @include '../../main.inc.php';
}
if (!$res && file_exists('../../../main.inc.php')) {
	$res = @include '../../../main.inc.php';
}
if (!$res) {
	die('Include of main fails');
}

/**
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

require_once DOL_DOCUMENT_ROOT.'/custom/batchinvoiceimport/lib/batchinvoiceimport.lib.php';

$langs->loadLangs(array('batchinvoiceimport@batchinvoiceimport', 'errors'));

if (!isModEnabled('batchinvoiceimport')) {
	accessforbidden('Module not enabled');
}

if (empty($user->admin) && !$user->hasRight('batchinvoiceimport', 'read')) {
	accessforbidden();
}

$fileParam = GETPOST('file', 'nohtml');
$fileName = dol_sanitizeFileName(basename(str_replace('\\', '/', $fileParam)));
$tempDir = batchinvoiceimportGetTempDir();
$filePath = $fileName ? $tempDir.'/'.$fileName : '';
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$readResult = array('rows' => array(), 'data_rows' => 0, 'error' => '');
$validation = array('checks' => array(), 'warnings' => array(), 'errors' => 0, 'item_groups' => 0);

/*
 * Actions
 */

if (!$fileName || !is_readable($filePath)) {
	setEventMessages($langs->trans('BatchInvoiceImportNoFileUploaded'), null, 'errors');
} elseif (!in_array($extension, array('xlsx', 'xls', 'csv'), true)) {
	setEventMessages($langs->trans('BatchInvoiceImportUnsupportedExtension'), null, 'errors');
} else {
	$readResult = batchinvoiceimportReadSpreadsheetHeaderRows($filePath, $extension);
	if (!empty($readResult['error'])) {
		setEventMessages($langs->trans('BatchInvoiceImport'.$readResult['error']), null, 'errors');
	} else {
		$validation = batchinvoiceimportValidateTemplateHeaders($readResult['rows']);
		if (!empty($validation['warnings'])) {
			setEventMessages($langs->trans('BatchInvoiceImportEtcWarning'), null, 'warnings');
		}
	}
}

/*
 * View
 */

$title = $langs->trans('BatchInvoiceImportPreviewPage');

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-batchinvoiceimport page-preview');

print load_fiche_titre($title, '', 'bill');
print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

if ($fileName && empty($readResult['error']) && is_readable($filePath)) {
	print '<table class="border centpercent">';
	print '<tr><td class="titlefield">'.$langs->trans('File').'</td><td>'.dol_escape_htmltag($fileName).'</td></tr>';
	print '<tr><td>'.$langs->trans('BatchInvoiceImportExpectedItemGroup').'</td><td>'.dol_escape_htmltag($langs->trans('BatchInvoiceImportExpectedItemGroupStructure')).'</td></tr>';
	print '<tr><td>'.$langs->trans('BatchInvoiceImportItemGroupsDetected').'</td><td>'.((int) $validation['item_groups']).'</td></tr>';
	print '<tr><td>'.$langs->trans('BatchInvoiceImportItemType').'</td><td>'.dol_escape_htmltag($langs->trans('BatchInvoiceImportItemTypeHelp')).'</td></tr>';
	print '<tr><td>'.$langs->trans('BatchInvoiceImportDataRowsDetected').'</td><td>'.((int) $readResult['data_rows']).'</td></tr>';
	print '<tr><td>'.$langs->trans('Status').'</td><td>';
	if ((int) $validation['errors'] > 0) {
		print '<span class="badge badge-danger">'.$langs->trans('BatchInvoiceImportStatusError').'</span>';
	} elseif (!empty($validation['warnings'])) {
		print '<span class="badge badge-warning">'.$langs->trans('BatchInvoiceImportStatusWarning').'</span>';
	} else {
		print '<span class="badge badge-status4">'.$langs->trans('BatchInvoiceImportStatusOk').'</span>';
	}
	print '</td></tr>';
	print '</table>';
	print '<br>';

	print load_fiche_titre($langs->trans('BatchInvoiceImportHeaderValidation'), '', '');
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Field').'</td>';
	print '<td>'.$langs->trans('BatchInvoiceImportExpectedValue').'</td>';
	print '<td>'.$langs->trans('BatchInvoiceImportDetectedValue').'</td>';
	print '<td>'.$langs->trans('Status').'</td>';
	print '</tr>';
	foreach ($validation['checks'] as $check) {
		print '<tr class="oddeven">';
		print '<td>'.dol_escape_htmltag($check['cell']).'</td>';
		print '<td>'.dol_escape_htmltag($check['expected']).'</td>';
		print '<td>'.dol_escape_htmltag($check['detected']).'</td>';
		print '<td>';
		if ($check['status'] === 'ok') {
			print '<span class="badge badge-status4">'.$langs->trans('BatchInvoiceImportStatusOk').'</span>';
		} elseif ($check['status'] === 'warning') {
			print '<span class="badge badge-warning">'.$langs->trans('BatchInvoiceImportStatusWarning').'</span>';
		} else {
			print '<span class="badge badge-danger">'.$langs->trans('BatchInvoiceImportStatusError').'</span>';
		}
		print '</td>';
		print '</tr>';
	}
	print '</table>';

	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.dol_escape_htmltag(dol_buildpath('/batchinvoiceimport/upload.php', 1)).'">'.$langs->trans('BatchInvoiceImportUploadAnotherFile').'</a>';
	print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans('BatchInvoiceImportNoInvoiceCreationYet')).'">'.$langs->trans('BatchInvoiceImportResultPage').'</a>';
	print '</div>';
} else {
	print '<div class="opacitymedium">'.$langs->trans('BatchInvoiceImportUploadRequired').'</div>';
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.dol_escape_htmltag(dol_buildpath('/batchinvoiceimport/upload.php', 1)).'">'.$langs->trans('BatchInvoiceImportUploadPage').'</a>';
	print '</div>';
}

print '</div>';

llxFooter();
$db->close();
