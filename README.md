# Dolibarr BatchInvoiceImport

Work-in-progress external module for Dolibarr that will allow users to generate customer invoices in batches from spreadsheet data.

The module is being developed as a standard Dolibarr external module and follows Dolibarr-native conventions wherever possible.

## Current status

This module is currently in early development.

Implemented so far:

* Clean Dolibarr external module scaffold.
* Module activation in Dolibarr.
* Basic module landing page.
* Upload page.
* File extension validation.
* CSV header/template validation.
* XLSX/XLS header validation support through Dolibarr-bundled PhpSpreadsheet, when the local PHP installation has the required extensions enabled.
* Preview page showing template validation results.
* Result placeholder page.
* Spanish and English language files.
* No invoice creation yet.
* No database writes yet.
* No custom SQL tables yet.

## Purpose

The final goal of the module is to import an Excel/CSV file where each row represents one customer invoice and each row contains one or more invoice item groups.

The module will eventually:

1. Upload a spreadsheet.
2. Validate the spreadsheet structure.
3. Parse invoice rows.
4. Validate customer, VAT, item lines, and totals.
5. Create draft customer invoices using Dolibarr native invoice methods.
6. Show an import result report.

## Development principles

This module must follow these rules:

* Use Dolibarr native methods, classes, permissions, constants, helpers, and database conventions wherever available.
* Do not bypass Dolibarr invoice logic.
* Do not insert directly into native Dolibarr invoice tables such as `llx_facture` or `llx_facturedet`.
* Future invoice creation must use Dolibarr native invoice classes/methods, especially the `Facture` class.
* Custom code must be vanilla PHP, vanilla JavaScript, HTML, and CSS.
* Do not use external frameworks.
* Do not add external libraries.
* Do not use Composer dependencies unless they are already included by Dolibarr.
* Do not use npm, Node, frontend build tools, or JavaScript frameworks.

## Expected spreadsheet format

The current client template is in Spanish.

Each spreadsheet data row represents one customer invoice.

### Invoice header columns

| Column | Header           |
| ------ | ---------------- |
| A      | CIF/NIF          |
| B      | NOMBRE           |
| C      | FECHA DE FACTURA |
| D      | IVA GLOBAL       |
| E      | SUBTOTAL FACTURA |
| F      | TOTAL FACTURA    |

### Repeated item group

Starting at column G, invoice lines are represented by repeated groups of 5 columns:

| Header          | Meaning                                 |
| --------------- | --------------------------------------- |
| CANTIDAD        | Quantity                                |
| DENOMINACION    | Free-text line description              |
| TIPO            | Line type: `1` = service, `0` = product |
| PRECIO UNITARIO | Unit price before VAT                   |
| PRECIO TOTAL    | Quantity × unit price, before VAT       |

The item group may repeat as many times as needed.

Example:

```text
CANTIDAD | DENOMINACION | TIPO | PRECIO UNITARIO | PRECIO TOTAL
CANTIDAD | DENOMINACION | TIPO | PRECIO UNITARIO | PRECIO TOTAL
CANTIDAD | DENOMINACION | TIPO | PRECIO UNITARIO | PRECIO TOTAL
```

A column named `ETC` may appear in the sample template only as an indication that more item groups can be added. It is not treated as an import data column.

## Confirmed business rules

* One row equals one customer invoice.
* `CIF/NIF` is the reliable customer lookup field.
* `NOMBRE` is informational only and must not be used as the primary matching field.
* `IVA GLOBAL` is an integer VAT percentage, for example `21`, `10`, `4`, or `0`.
* `PRECIO UNITARIO` is before VAT.
* `PRECIO TOTAL` is before VAT and must equal `CANTIDAD × PRECIO UNITARIO`.
* `SUBTOTAL FACTURA` is the sum of all `PRECIO TOTAL` values, before VAT.
* `TOTAL FACTURA` is `SUBTOTAL FACTURA` plus VAT.
* Item lines are free-text invoice lines.
* `TIPO = 1` means service.
* `TIPO = 0` means product.
* There is currently no product reference column, so item lines are not linked to existing Dolibarr products/services.

## Local development environment

Current development environment:

```text
OS: Windows 11
Server: XAMPP
Dolibarr: 24.0.0-beta
Module path: htdocs/custom/batchinvoiceimport
```

The local Dolibarr beta version requires the module descriptor to use:

```php
$this->need_dolibarr_version = array(24, 0, -4);
```

When targeting a stable Dolibarr 24 final release, this should be changed to:

```php
$this->need_dolibarr_version = array(24, 0, 0);
```

## XLSX support note

XLSX files require PHP ZIP support because XLSX files are ZIP-based containers.

In XAMPP, if XLSX parsing is unavailable, enable the PHP zip extension in `php.ini`:

```ini
extension=zip
```

Then restart Apache and verify:

```bat
C:\xampp\php\php.exe -m | findstr zip
```

Expected output:

```text
zip
```

## Current manual tests

The following tests currently pass:

* Module can be enabled in Dolibarr.
* Module setup/about pages load.
* Landing page loads.
* Upload page loads.
* TXT files are rejected.
* CSV files with the expected headers are accepted.
* CSV header validation detects repeated 5-column item groups.
* `TIPO` is correctly expected in each item group.
* `ETC` is treated as a warning/stop marker, not as an error.
* Result page clearly states that invoice creation is not implemented yet.

## Roadmap

Planned next milestones:

1. Validate a real client spreadsheet with sample data.
2. Parse invoice rows without creating invoices.
3. Validate customer existence by `CIF/NIF`.
4. Validate VAT values.
5. Validate item line totals.
6. Validate invoice subtotals and totals.
7. Add preview report for row-level errors and warnings.
8. Create draft customer invoices using Dolibarr `Facture` methods.
9. Add import result report with links to generated draft invoices.
10. Add import history and duplicate prevention if needed.

## Important safety note

This module must never manually insert invoice data into Dolibarr invoice tables.

Invoice creation must be delegated to Dolibarr’s own business logic so that totals, VAT, numbering, permissions, triggers, and related behaviors remain consistent with Dolibarr.

## License

To be defined.
