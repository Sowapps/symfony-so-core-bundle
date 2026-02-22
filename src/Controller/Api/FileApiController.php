<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller\Api;

use DateTime;
use Exception;
use Sowapps\SoCore\Core\Controller\AbstractApiEntityController;
use Sowapps\SoCore\Entity\File;
use Sowapps\SoCore\Exception\ForbiddenOperationException;
use Sowapps\SoCore\Model\FileCreateDto;
use Sowapps\SoCore\Service\FileService;
use Sowapps\SoCore\Service\SecurityService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FileApiController extends AbstractApiEntityController {
	
	/**
	 * FileController constructor
	 *
	 * @param FileService $fileService
	 */
	public function __construct(private readonly FileService $fileService) {
		parent::__construct($fileService->getFileRepository());
	}
	
	#[Route("/api/file", methods: ['POST'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function upload(#[MapUploadedFile] UploadedFile $file, #[MapRequestPayload] FileCreateDto $fileDto, Request $request): Response {
		$format = $this->getRequestFormat($request);
		$purpose = $fileDto->purpose;
		$expireDate = $fileDto->expireDate ? new DateTime($fileDto->expireDate) : null;
		// TODO Add related parent, deducted from purpose
		$file = $this->fileService->upload($file, $purpose, $expireDate);
		
		return $this->respondEntity($file, $format, Response::HTTP_CREATED);
	}
	
	#[Route("/api/file/{id}", name: 'api_file_delete', methods: ['DELETE'], format: 'json')]
	public function delete(File $file): JsonResponse {
		try {
			throw new ForbiddenOperationException($this->translator->trans('file.remove.forbidden', domain: 'admin'));
			// TODO Add/Verify authentication
			//			if( !$this->fileService->allowFileEdit($file, $this->getUser()) ) {
			//				throw new ForbiddenOperationException($this->translator->trans('file.remove.forbidden', [], 'admin'));
			//			}
			//
			//			//			$this->fileService->remove($file);
			//			return $this->json($this->translator->trans('file.remove.success', [], 'admin'));
		} catch( Exception $e ) {
			return $this->json($this->formatException($e, $this->translator->trans('file.remove.error', domain: 'admin')), $e->getCode() ?: 500);
		}
	}
	
	#[Route("/api/me/file", name: 'sowapps_socore_api_file_list', methods: ['GET'], format: 'json')]
	public function list(Request $request): JsonResponse {
		// TODO Use SoCore API tools
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
