<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller\Api;

use Sowapps\SoCore\Core\Controller\AbstractApiController;
use Sowapps\SoCore\Entity\File;
use Sowapps\SoCore\Service\ControllerService;
use Sowapps\SoCore\Service\FileService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FileApiController extends AbstractApiController {
	
	private FileService $fileService;
	
	/**
	 * FileController constructor
	 *
	 * @param ControllerService $controllerService
	 * @param FileService $fileService
	 */
	public function __construct(ControllerService $controllerService, FileService $fileService) {
		parent::__construct($controllerService);
		$this->fileService = $fileService;
	}
	
	public function list(Request $request): JsonResponse {
		$filters = $this->getRequestFilters($request);
		
		$search = $this->searchEntityTerm($this->fileService->getFileRepository());
		$query = $search->getQuery();
		
		// Filter user allowed
		$query
			->andWhere('file.createUser = :user')
			->setParameter('user', $this->getUser());
		
		// Filter by purpose
		if( !empty($filters['purpose']) ) {
			$query
				->andWhere('file.purpose = :purpose')
				->setParameter('purpose', $filters['purpose']);
		}
		
		$data = [];
		foreach( $this->formatSearchResults([$search], [], 50) as $file ) {
			/** @var File $file */
			$data[] = $file->jsonSerialize() + [
					'size'        => $this->formatFileSize($file),
					'downloadUrl' => $this->fileService->getFileUrl($file),
					'viewUrl'     => $this->fileService->getFileUrl($file, false),
				];
		}
		
		return $this->json($data);
	}
	
	protected function formatFileSize(File $file): array {
		$localeFormatter = $this->getContextService()->getLocaleFormatter();
		$bytes = $this->fileService->getFileSize($file);
		$size = $this->fileService->parseSize($bytes);
		
		return [
			'value' => $bytes,
			'size'  => $size,
			'label' => $localeFormatter->formatFileSize($size),
		];
	}
	
}
