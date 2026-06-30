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
 * \file    htdocs/custom/batchinvoiceimport/index.php
 * \ingroup batchinvoiceimport
 * \brief   Landing page for Batch Invoice Import.
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
require_once DOL_DOCUMENT_ROOT.'/custom/batchinvoiceimport/lib/batchinvoiceimport.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array('batchinvoiceimport@batchinvoiceimport'));

if (!isModEnabled('batchinvoiceimport')) {
	accessforbidden('Module not enabled');
}

if (empty($user->admin) && !$user->hasRight('batchinvoiceimport', 'read')) {
	accessforbidden();
}

/*
 * Actions
 */

// No actions yet.

/*
 * View
 */

$title = $langs->trans('BatchInvoiceImport');

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-batchinvoiceimport page-index');

print load_fiche_titre($title, '', 'bill');
print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
print '<div class="opacitymedium">'.$langs->trans('BatchInvoiceImportWorkflowIntro').'</div>';
print '<br>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans('Step').'</td><td>'.$langs->trans('Description').'</td><td>'.$langs->trans('Status').'</td></tr>';
print '<tr class="oddeven"><td>1</td><td>'.$langs->trans('BatchInvoiceImportWorkflowUpload').'</td><td>'.$langs->trans('Available').'</td></tr>';
print '<tr class="oddeven"><td>2</td><td>'.$langs->trans('BatchInvoiceImportWorkflowPreview').'</td><td>'.$langs->trans('BatchInvoiceImportPlaceholder').'</td></tr>';
print '<tr class="oddeven"><td>3</td><td>'.$langs->trans('BatchInvoiceImportWorkflowGenerate').'</td><td>'.$langs->trans('BatchInvoiceImportNoInvoiceCreationYet').'</td></tr>';
print '<tr class="oddeven"><td>4</td><td>'.$langs->trans('BatchInvoiceImportWorkflowResult').'</td><td>'.$langs->trans('BatchInvoiceImportPlaceholder').'</td></tr>';
print '</table>';
print '<div class="tabsAction">';
print '<a class="butAction" href="'.dol_escape_htmltag(dol_buildpath('/batchinvoiceimport/upload.php', 1)).'">'.$langs->trans('BatchInvoiceImportUploadPage').'</a>';
print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans('BatchInvoiceImportUploadRequired')).'">'.$langs->trans('BatchInvoiceImportPreviewPage').'</a>';
print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans('BatchInvoiceImportNoInvoiceCreationYet')).'">'.$langs->trans('BatchInvoiceImportResultPage').'</a>';
print '</div>';
print '</div>';

llxFooter();
$db->close();
