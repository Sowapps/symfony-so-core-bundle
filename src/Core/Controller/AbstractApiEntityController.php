<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Controller;

use Doctrine\ORM\QueryBuilder;
use Sowapps\SoCore\Core\DBAL\AbstractRepository;
use Sowapps\SoCore\Core\ProcessOption\FormatOptions;
use Sowapps\SoCore\Core\ProcessOption\PaginationOptions;
use Sowapps\SoCore\Entity\AbstractEntity;
use Sowapps\SoCore\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApiEntityController extends AbstractApiController {
	
	public function __construct(
		protected readonly AbstractRepository $repository
	) {
	}
	
	/**
	 * Process request to patch given entity from DTO and render entity as output
	 */
	protected function processRequestEntityBasicCreate(AbstractEntity $entity, object $dto, Request $request): Response {
		$format = $this->getRequestFormat($request, true);
		
		$this->entityService
//			->validate($dto) // Form-level validation (ex: The length of strings) - DTO is already validated by Symfony
			->mapDto($dto, $entity)
			->validate($entity) // Entity-level validation (ex: Entity is unique)
			->create($entity)
			->flush();
		
		return $this->respondEntity($entity, $format);
	}
	
	/**
	 * Process request to patch given entity from DTO and render entity as output
	 */
	protected function processRequestEntityBasicPatch(AbstractEntity $entity, object $dto, Request $request): Response {
		$this->entityService->mapDto($dto, $entity);
		$format = $this->getRequestFormat($request, true);
		
		return $this->respondEntityUpdate($entity, $format);
	}
	
	/**
	 * Process request to list entities
	 */
	protected function processRequestListWithBasicPagination(Request $request): Response {
		[$criteria, $pagination, $format] = $this->getRequestOptions($request, false);
		
		$query = $this->repository->queryBy($criteria);
		
		return $this->respondQueryList($query, $pagination, $format);
	}
	
	/**
	 * Process request to get one entity
	 * @param AbstractEntity $entity
	 * @param Request $request
	 * @param bool|array|string|null $defaultFormat True to use admin as default, else public or given one
	 * @return Response
	 */
	protected function processRequestEntityGet(AbstractEntity $entity, Request $request, bool|array|string|null $defaultFormat = null): Response {
		$format = $this->getRequestFormat($request, $defaultFormat);
		
		return $this->respondEntity($entity, $format);
	}
	
	/**
	 * @param AbstractEntity $entity
	 * @param FormatOptions $format
	 * @param int|null $changes Number of changes (null to calculate from UnitOfWork)
	 * @return Response
	 */
	protected function respondEntityUpdate(AbstractEntity $entity, FormatOptions $format, ?int $changes = null): Response {
		if( $changes === null ) {
			// Calculate changes
			$changes = $this->entityService->countChanges($entity);
		}
		if( $changes ) {
			try {
				$this->entityService->validate($entity);
			} catch( ValidationException $exception ) {
				return $this->respondValidationException($exception);
			}
			$this->entityService->update($entity)->flush();
		}
		
		$headers = ['Update-Changes' => $changes];
		
		return $this->respond($this->formatEntity($entity, $format), Response::HTTP_OK, $headers);
	}
	
	/**
	 * Format response a list from a query
	 */
	protected function respondQueryList(QueryBuilder $query, ?PaginationOptions $pagination, FormatOptions $format): Response {
		if( $pagination ) {
			$result = $this->entityService->paginateQuery($query, $pagination);
			
			return $this->respondPaginated($result, $format);
		} else {
			return $this->respond($this->formatEntityList($query->getQuery()->getResult(), $format));
		}
	}
	
	/**
	 * Format an entity
	 */
	protected function respondEntity(AbstractEntity $entity, FormatOptions $format): Response {
		return $this->respond($this->formatEntity($entity, $format));
	}
	
}
