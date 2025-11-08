<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller\Api;

use Exception;
use Sowapps\SoCore\Core\Controller\AbstractApiController;
use Sowapps\SoCore\Entity\File;
use Sowapps\SoCore\Exception\ForbiddenOperationException;
use Sowapps\SoCore\Service\ControllerService;
use Sowapps\SoCore\Service\FileService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FileApiController extends AbstractApiController {
	
	/**
	 * FileController constructor
	 *
	 * @param ControllerService $controllerService
	 * @param FileService $fileService
	 */
	public function __construct(ControllerService $controllerService, private readonly FileService $fileService) {
		parent::__construct($controllerService);
	}
	
	public function delete(File $file): JsonResponse {
		try {
			throw new ForbiddenOperationException($this->translator->trans('file.remove.forbidden', [], 'admin'));
			if( !$this->fileService->allowFileEdit($file, $this->getUser()) ) {
				throw new ForbiddenOperationException($this->translator->trans('file.remove.forbidden', [], 'admin'));
			}
			
			//			$this->fileService->remove($file);
			return $this->json($this->translator->trans('file.remove.success', [], 'admin'));
		} catch( Exception $e ) {
			return $this->json($this->formatException($e, $this->translator->trans('file.remove.error', [], 'admin')), $e->getCode() ?: 500);
		}
	}
	
	public function list(Request $request): JsonResponse {
		$filters = $this->getRequestFilters($request);
		$user = $this->getUser();
		
		$search = $this->searchEntityTerm($this->fileService->getFileRepository());
		$query = $search->getQuery();
		
		// Filter user allowed
		$query
			->andWhere('file.createUser = :user')
			->setParameter('user', $user);
		
		// Filter by purpose
		if( !empty($filters['purpose']) ) {
			$query
				->andWhere('file.purpose = :purpose')
				->setParameter('purpose', $filters['purpose']);
		}
		
		$query->orderBy('file.id', 'DESC');
		
		$data = [];
		foreach( $this->formatSearchResults([$search], [], 50) as $file ) {
			/** @var File $file */
			$data[] = $this->fileService->formatFileArray($file, $user, $this->contextService);
		}
		
		return $this->json($data);
	}
	
}
