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
 * \file    htdocs/custom/batchinvoiceimport/lib/batchinvoiceimport.lib.php
 * \ingroup batchinvoiceimport
 * \brief   Common library for Batch Invoice Import.
 */

/**
 * Prepare admin pages header.
 *
 * @return array<array{string,string,string}>
 */
function batchinvoiceimportAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load('batchinvoiceimport@batchinvoiceimport');

	$h = 0;
	$head = array();

	$head[$h][0] = dolBuildUrl(dol_buildpath('/batchinvoiceimport/admin/setup.php', 1));
	$head[$h][1] = $langs->trans('Settings');
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dolBuildUrl(dol_buildpath('/batchinvoiceimport/admin/about.php', 1));
	$head[$h][1] = $langs->trans('About');
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'batchinvoiceimport@batchinvoiceimport');
	complete_head_from_modules($conf, $langs, null, $head, $h, 'batchinvoiceimport@batchinvoiceimport', 'remove');

	return $head;
}

/**
 * Return module temp directory, matching Dolibarr module directory convention.
 *
 * @return string
 */
function batchinvoiceimportGetTempDir()
{
	global $conf;

	if (!getDolGlobalString('MAIN_MODULE_MULTICOMPANY') || $conf->entity == 1) {
		return DOL_DATA_ROOT.'/batchinvoiceimport/temp';
	}

	return DOL_DATA_ROOT.'/'.$conf->entity.'/batchinvoiceimport/temp';
}

/**
 * Normalize a spreadsheet cell value for template comparison.
 *
 * @param mixed $value Cell value
 * @return string
 */
function batchinvoiceimportNormalizeHeaderValue($value)
{
	$value = trim((string) $value);
	$value = preg_replace('/\s+/', ' ', $value);

	return dol_string_unaccent(dol_strtoupper($value));
}

/**
 * Read the first two rows from a CSV/XLS/XLSX file.
 *
 * @param string $filePath Full path to uploaded file
 * @param string $extension Lowercase file extension
 * @return array{rows:array<int,array<int,string>>,data_rows:int,error:string}
 */
function batchinvoiceimportReadSpreadsheetHeaderRows($filePath, $extension)
{
	$result = array(
		'rows' => array(1 => array(), 2 => array()),
		'data_rows' => 0,
		'error' => '',
	);

	if ($extension === 'csv') {
		return batchinvoiceimportReadCsvHeaderRows($filePath);
	}

	if (!in_array($extension, array('xlsx', 'xls'), true)) {
		$result['error'] = 'UnsupportedExtension';
		return $result;
	}

	if (!class_exists('ZipArchive') && $extension === 'xlsx') {
		$result['error'] = 'ParsingNotAvailable';
		return $result;
	}

	require_once DOL_DOCUMENT_ROOT.'/includes/phpoffice/phpspreadsheet/src/autoloader.php';
	require_once DOL_DOCUMENT_ROOT.'/includes/Psr/autoloader.php';

	try {
		$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
		$reader->setReadDataOnly(true);
		if (method_exists($reader, 'setReadFilter')) {
			$reader->setReadFilter(new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
				/**
				 * @param string $columnAddress Column address
				 * @param int $row Row number
				 * @param string $worksheetName Worksheet name
				 * @return bool
				 */
				public function readCell($columnAddress, $row, $worksheetName = '')
				{
					return $row <= 2;
				}
			});
		}
		$spreadsheet = $reader->load($filePath);
		$sheet = $spreadsheet->getSheet(0);
		$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
		$highestColumnIndex = max($highestColumnIndex, 23);

		for ($row = 1; $row <= 2; $row++) {
			for ($col = 1; $col <= $highestColumnIndex; $col++) {
				$result['rows'][$row][$col] = trim((string) $sheet->getCellByColumnAndRow($col, $row)->getValue());
			}
		}

		$highestDataRow = (int) $sheet->getHighestDataRow();
		$result['data_rows'] = max(0, $highestDataRow - 2);
		$spreadsheet->disconnectWorksheets();
	} catch (Exception $e) {
		dol_syslog('BatchInvoiceImport spreadsheet parsing failed: '.$e->getMessage(), LOG_WARNING);
		$result['error'] = 'ParsingNotAvailable';
	}

	return $result;
}

/**
 * Read the first two rows from a CSV file.
 *
 * @param string $filePath Full path
 * @return array{rows:array<int,array<int,string>>,data_rows:int,error:string}
 */
function batchinvoiceimportReadCsvHeaderRows($filePath)
{
	$result = array(
		'rows' => array(1 => array(), 2 => array()),
		'data_rows' => 0,
		'error' => '',
	);

	$handle = fopen($filePath, 'r');
	if (!$handle) {
		$result['error'] = 'ParsingNotAvailable';
		return $result;
	}

	$lineNumber = 0;
	while (($line = fgets($handle)) !== false) {
		$lineNumber++;
		if ($lineNumber <= 2) {
			$values = batchinvoiceimportParseCsvLine($line);
			foreach ($values as $index => $value) {
				$result['rows'][$lineNumber][$index + 1] = trim((string) $value);
			}
		} elseif (trim($line) !== '') {
			$result['data_rows']++;
		}
	}
	fclose($handle);

	return $result;
}

/**
 * Parse a CSV line using the most likely separator.
 *
 * @param string $line CSV line
 * @return array<int,string>
 */
function batchinvoiceimportParseCsvLine($line)
{
	$bestValues = array();
	$bestCount = 0;
	foreach (array(';', ',', "\t") as $separator) {
		$values = str_getcsv($line, $separator);
		if (count($values) > $bestCount) {
			$bestValues = $values;
			$bestCount = count($values);
		}
	}

	return $bestValues;
}

/**
 * Validate the uploaded spreadsheet template.
 *
 * @param array<int,array<int,string>> $rows First two spreadsheet rows
 * @return array{checks:array<int,array{cell:string,expected:string,detected:string,status:string}>,warnings:array<int,string>,errors:int,item_groups:int}
 */
function batchinvoiceimportValidateTemplateHeaders($rows)
{
	$checks = array();
	$warnings = array();
	$errors = 0;

	$expectedFixed = array(
		'A1' => array(1, 1, 'DATOS DE FACTURA'),
		'G1' => array(1, 7, 'ITEMS'),
		'A2' => array(2, 1, 'CIF/NIF'),
		'B2' => array(2, 2, 'NOMBRE'),
		'C2' => array(2, 3, 'FECHA DE FACTURA'),
		'D2' => array(2, 4, 'IVA GLOBAL'),
		'E2' => array(2, 5, 'SUBTOTAL FACTURA'),
		'F2' => array(2, 6, 'TOTAL FACTURA'),
	);

	foreach ($expectedFixed as $cell => $expectedInfo) {
		$detected = isset($rows[$expectedInfo[0]][$expectedInfo[1]]) ? $rows[$expectedInfo[0]][$expectedInfo[1]] : '';
		$status = batchinvoiceimportNormalizeHeaderValue($detected) === batchinvoiceimportNormalizeHeaderValue($expectedInfo[2]) ? 'ok' : 'error';
		if ($status === 'error') {
			$errors++;
		}
		$checks[] = array(
			'cell' => $cell,
			'expected' => $expectedInfo[2],
			'detected' => $detected,
			'status' => $status,
		);
	}

	$itemPattern = array('CANTIDAD', 'DENOMINACION', 'TIPO', 'PRECIO UNITARIO', 'PRECIO TOTAL');
	$itemPatternSize = count($itemPattern);
	$itemGroups = 0;
	$col = 7;

	while (true) {
		$nextValues = array();
		for ($offset = 0; $offset < $itemPatternSize; $offset++) {
			$nextValues[] = isset($rows[2][$col + $offset]) ? $rows[2][$col + $offset] : '';
		}

		if (batchinvoiceimportNormalizeHeaderValue($nextValues[0]) === 'ETC') {
			$warnings[] = 'ETC';
			$checks[] = array(
				'cell' => batchinvoiceimportColumnNameFromIndex($col).'2',
				'expected' => '',
				'detected' => $nextValues[0],
				'status' => 'warning',
			);
			break;
		}

		$allEmpty = true;
		foreach ($nextValues as $value) {
			if (trim((string) $value) !== '') {
				$allEmpty = false;
				break;
			}
		}
		if ($allEmpty) {
			break;
		}

		$groupIsValid = true;
		for ($offset = 0; $offset < $itemPatternSize; $offset++) {
			$status = batchinvoiceimportNormalizeHeaderValue($nextValues[$offset]) === batchinvoiceimportNormalizeHeaderValue($itemPattern[$offset]) ? 'ok' : 'error';
			if ($status === 'error') {
				$groupIsValid = false;
				$errors++;
			}
			$checks[] = array(
				'cell' => batchinvoiceimportColumnNameFromIndex($col + $offset).'2',
				'expected' => $itemPattern[$offset],
				'detected' => $nextValues[$offset],
				'status' => $status,
			);
		}

		if (!$groupIsValid) {
			break;
		}

		$itemGroups++;
		$col += $itemPatternSize;
	}

	if ($itemGroups < 1) {
		$errors++;
		$checks[] = array(
			'cell' => 'G2:K2',
			'expected' => implode(' / ', $itemPattern),
			'detected' => '',
			'status' => 'error',
		);
	}

	return array(
		'checks' => $checks,
		'warnings' => $warnings,
		'errors' => $errors,
		'item_groups' => $itemGroups,
	);
}

/**
 * Convert one-based column index to Excel-style column name.
 *
 * @param int $index Column index
 * @return string
 */
function batchinvoiceimportColumnNameFromIndex($index)
{
	$name = '';
	while ($index > 0) {
		$index--;
		$name = chr(65 + ($index % 26)).$name;
		$index = (int) floor($index / 26);
	}

	return $name;
}
