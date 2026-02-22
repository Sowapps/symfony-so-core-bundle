<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\FileExport;

interface StreamableDataExporter {
	/**
	 * @param iterable $rows Rows in the exported file, we recommend using a generator
	 * @param array $columns Columns of the data rows (source of headers)
	 * @param CsvFormat $format Formatting options
	 * @param resource $out Output resource
	 * @return void
	 */
	public function streamTo(iterable $rows, array $columns, CsvFormat $format, $out): void;
}
