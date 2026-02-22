<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Model;

use Sowapps\SoCore\Core\FileExport\ImportMode;
use Symfony\Component\Validator\Constraints as Assert;

class FileImportDto {
	
	public function __construct(
		public ImportMode $mode,
		
		#[Assert\NotBlank]
		public int $fileId,
	) {
	}
	
}
