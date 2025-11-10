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
	
	public function __construct(private readonly FileService $fileService)
    {
    }
	
	/**
	 * Transforms an entity address to a form address
	 *
	 * @param File|null $value
	 * @return File|null
	 */
	public function transform(mixed $value): mixed {
		if( !$value ) {
			return null;
		}
		return $value;
	}
	
	/**
	 * Transforms a form address to an entity address
	 *
	 * @param File|null $value
	 * @return File|null
	 * @throws TransformationFailedException if object (issue) is not found.
	 */
	public function reverseTransform(mixed $value): mixed {
		if( !$value ) {
			return null;
		}
		
		if( is_numeric($value) ) {
			return $this->fileService->getFile($value);
		}
//		if( $file instanceof UploadedFile ) {
//			return $this->fileService->upload($file, null, null, new DateTime('+1 day'));
//		}
		
		return $value;
	}
}
