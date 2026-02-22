<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\FileExport;

final readonly class CsvFormat {
	public function __construct(
		public string $delimiter = ',',
		public string $enclosure = '"',
		public string $escape = '\\',
//		public string $newline = "\r\n",
		public bool   $withUtf8Bom = false, // Useful for Excel
		public bool   $excelFormulaProtection = true, // Prevent CSV injection in spreadsheet apps
	) {
	}
}
