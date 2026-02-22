<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\FileExport;

use Generator;
use SplFileObject;

class CsvParser {
	/**
	 * Iterates over CSV rows as associative arrays keyed by $columns.
	 *
	 * @param SplFileObject $in
	 * @param CsvFormat $format
	 * @param list<string> $columns
	 * @return Generator<int, array<string, string|null>>
	 */
	public function parse(SplFileObject $in, CsvFormat $format, array $columns): Generator
	{
		// Discard the header row (you can validate it if you want)
		$in->fgetcsv($format->delimiter, $format->enclosure, $format->escape);
		
		while (($row = $in->fgetcsv($format->delimiter, $format->enclosure, $format->escape)) !== false) {
			if ($row === [null] || $row === []) {
				continue;
			}
			
			$entityData = [];
			foreach ($columns as $i => $col) {
				$entityData[$col] = array_key_exists($i, $row) ? ($row[$i] === '' ? null : $row[$i]) : null;
			}
			
			yield $entityData;
		}
	}
}
