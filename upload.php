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
 * \file    htdocs/custom/batchinvoiceimport/upload.php
 * \ingroup batchinvoiceimport
 * \brief   Upload page for Batch Invoice Import.
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/batchinvoiceimport/lib/batchinvoiceimport.lib.php';

$langs->loadLangs(array('batchinvoiceimport@batchinvoiceimport', 'errors'));

if (!isModEnabled('batchinvoiceimport')) {
	accessforbidden('Module not enabled');
}

if (empty($user->admin) && !$user->hasRight('batchinvoiceimport', 'read')) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$allowedExtensions = array('xlsx', 'xls', 'csv');

/*
 * Actions
 */

if ($action === 'upload') {
	if (empty($_FILES['userfile']) || empty($_FILES['userfile']['name'])) {
		setEventMessages($langs->trans('BatchInvoiceImportNoFileUploaded'), null, 'errors');
	} else {
		$originalName = (string) $_FILES['userfile']['name'];
		$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
		if (!in_array($extension, $allowedExtensions, true)) {
			setEventMessages($langs->trans('BatchInvoiceImportUnsupportedExtension'), null, 'errors');
		} else {
			$tempDir = batchinvoiceimportGetTempDir();
			if (dol_mkdir($tempDir, DOL_DATA_ROOT) < 0) {
				setEventMessages($langs->trans('ErrorFailedToSaveFile'), null, 'errors');
			} else {
				$timestamp = dol_print_date(dol_now(), '%Y%m%d%H%M%S');
				$fileName = $user->id.'-'.$timestamp.'-'.dol_sanitizeFileName($originalName);
				$fullPath = $tempDir.'/'.$fileName;
				$resultUpload = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $fullPath, 1, 0, (int) $_FILES['userfile']['error']);
				if (is_numeric($resultUpload) && $resultUpload > 0) {
					dol_syslog('BatchInvoiceImport uploaded file '.$fullPath);
					header('Location: '.dol_buildpath('/batchinvoiceimport/preview.php', 1).'?file='.urlencode($fileName));
					exit;
				}
				setEventMessages($langs->trans('ErrorFailedToSaveFile'), null, 'errors');
			}
		}
	}
}

/*
 * View
 */

$title = $langs->trans('BatchInvoiceImportUploadPage');

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-batchinvoiceimport page-upload');

print load_fiche_titre($title, '', 'upload');
print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
print '<div class="opacitymedium">'.$langs->trans('BatchInvoiceImportUploadIntro').'</div>';
print '<div class="opacitymedium">'.$langs->trans('BatchInvoiceImportExpectedItemGroupStructure').'</div>';
print '<div class="opacitymedium">'.$langs->trans('BatchInvoiceImportItemTypeHelp').'</div>';
print '<br>';
print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="upload">';
print '<table class="border centpercent">';
print '<tr><td class="titlefieldcreate">'.$langs->trans('File').'</td><td><input type="file" name="userfile" accept=".xlsx,.xls,.csv"></td></tr>';
print '<tr><td>'.$langs->trans('Format').'</td><td>'.dol_escape_htmltag(implode(', ', $allowedExtensions)).'</td></tr>';
print '</table>';
print '<div class="center">';
print '<input type="submit" class="button" name="sendit" value="'.$langs->trans('BatchInvoiceImportValidateTemplate').'">';
print '</div>';
print '</form>';
print '</div>';

llxFooter();
$db->close();
