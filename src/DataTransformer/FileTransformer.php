<?php

namespace Sowapps\SoCore\DataTransformer;

use Sowapps\SoCore\Entity\File;
use Sowapps\SoCore\Service\FileService;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @deprecated Use EntityTransformer
 */
class FileTransformer implements DataTransformerInterface {
	
	private FileService $fileService;
	
	public function __construct(FileService $fileService) {
		$this->fileService = $fileService;
	}
	
	/**
	 * Transforms an entity address to a form address
	 *
	 * @param File|null $file
	 * @return File|null
	 */
	public function transform($file) {
		if( !$file ) {
			return null;
		}
		return $file;
	}
	
	/**
	 * Transforms a form address to an entity address
	 *
	 * @param File|null $file
	 * @return File|null
	 * @throws TransformationFailedException if object (issue) is not found.
	 */
	public function reverseTransform($file) {
		if( !$file ) {
			return null;
		}
		
		if( is_numeric($file) ) {
			return $this->fileService->getFile($file);
		}
//		if( $file instanceof UploadedFile ) {
//			return $this->fileService->upload($file, null, null, new DateTime('+1 day'));
//		}
		
		return $file;
	}
}
