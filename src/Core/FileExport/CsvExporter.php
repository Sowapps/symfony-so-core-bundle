<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\FileExport;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final readonly class CsvExporter implements StreamableDataExporter {
	public function __construct(
		private PropertyAccessorInterface $propertyAccessor
	) {
	}
	
	/**
	 * @param iterable<array<int, scalar|null>> $rows
	 * @param array<int, string> $columns
	 */
	public function streamTo(iterable $rows, array $columns, CsvFormat $format, $out): void {
		if( $format->withUtf8Bom ) {
			// Write UTF-8 BOM (helps some spreadsheet apps detect UTF-8 properly)
			fwrite($out, "\xEF\xBB\xBF");
		}
		
		// The accepted format is a list of properties, also as headers, ex: ['id', 'email', 'createdAt']
		$this->writeRow($out, $columns, $format);
		
		foreach( $rows as $row ) {
			if( is_object($row) ) {
				$row = $this->formatObjectRow($row, $columns);
			}
			$this->writeRow($out, $row, $format);
		}
		
		fclose($out);
	}
	
	/**
	 * @param object $object
	 * @param string[] $properties
	 * @return list<scalar|null>
	 */
	protected function formatObjectRow(object $object, array $properties): array {
		$row = [];
		foreach( $properties as $property ) {
			$value = $this->propertyAccessor->getValue($object, $property);
			if( $value === false ) {
				// The boolean "False" is now explicitly a 0
				// The boolean "True" is already converted implicitly to 1
				$value = 0;
			}
			$row[] = $value;
		}
		
		return $row;
	}
	
	/**
	 * @param resource $out
	 * @param array<int, mixed> $row
	 */
	private function writeRow($out, array $row, CsvFormat $format): void {
		$sanitized = array_map(
			fn($v) => $this->sanitizeCell($v, $format),
			$row
		);
		
		// fputcsv handles delimiter/enclosure/escape correctly (quotes, commas, newlines inside fields, etc.)
		fputcsv($out, $sanitized, $format->delimiter, $format->enclosure, $format->escape);
		
		// fputcsv writes a trailing "\n" depending on implementation/platform.
		// If you need strict CRLF, you can force your own newline.
		//		if( $format->newline !== "\n" ) {
		//			fwrite($out, $format->newline);
		//		}
	}
	
	private function sanitizeCell(mixed $value, CsvFormat $format): string {
		if( $value === null ) {
			return '';
		}
		
		$s = (string)$value;
		
		// Prevent CSV injection (Excel/LibreOffice): if a cell starts with = + - @, prefix it with an apostrophe.
		// OWASP: https://owasp.org/www-community/attacks/CSV_Injection
		if( $format->excelFormulaProtection && $s !== '' && strpbrk($s[0], '=+-@') !== false ) {
			$s = "'" . $s;
		}
		
		return $s;
	}
}
