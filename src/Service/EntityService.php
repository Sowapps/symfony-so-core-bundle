<?php

namespace Sowapps\SoCore\Service;

use ArrayIterator;
use AutoMapper\AutoMapperInterface;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use RuntimeException;
use Sowapps\SoCore\Core\Entity\PaginatedResult;
use Sowapps\SoCore\Core\ProcessOption\PaginationOptions;
use Sowapps\SoCore\Entity\AbstractEntity;
use Sowapps\SoCore\Exception\ValidationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Traversable;

readonly class EntityService {
	
	public function __construct(
		private EntityManagerInterface $entityManager,
		private ValidatorInterface     $validator,
		private AutoMapperInterface    $autoMapper
	) {
	}
	
	public function mapDto(object $dto, AbstractEntity $entity): static {
		$this->autoMapper->map($dto, $entity);
		
		return $this;
	}
	
	public function countChanges(AbstractEntity $entity): int {
		$uow = $this->entityManager->getUnitOfWork();
		$uow->computeChangeSets();
		
		return count($uow->getEntityChangeSet($entity));
	}
	
	/**
	 * @throws ValidationException
	 */
	public function validate(object $entity, array $groups = null): static {
		$violationList = $this->validator->validate($entity, null, $groups);
		if( $violationList->count() ) {
			throw new ValidationFailedException($entity, $violationList);
		}
		
		return $this;
	}
	
	public function paginateQuery(QueryBuilder $query, PaginationOptions $pagination): PaginatedResult {
		$resultPerPage = $pagination->getPageLimit();
		$page = $resultPerPage ? $pagination->getPage() : 1;// if selecting all, force first page
		$resultMax = $page * $resultPerPage;
		$resultMin = $resultMax - $resultPerPage;
		$count = $this->countQueryRows($query);
		if( ($count - 1) >= $resultMin ) {
			// There are results for this page
			if( $resultPerPage ) {
				$query
					->setMaxResults($resultPerPage)
					->setFirstResult($resultMin);
			}
			$results = $query
				->getQuery()
				->toIterable();
		} else {
			$results = new ArrayIterator();
		}
		
		return $this->getPaginatedResult($results, $page, $resultPerPage, $count);
	}
	
	public function countQueryRows(QueryBuilder $query): int {
		$paginator = new Paginator($query);
		
		return count($paginator);
	}
	
	protected function getPaginatedResult(Traversable $results, int $page, int $resultPerPage, int $count): PaginatedResult {
		return new PaginatedResult($results, $page, $resultPerPage, $count);
	}
	
	public function paginateCollection(Collection $collection, array $pagination): PaginatedResult {
		if( !($collection instanceof Selectable) ) {
			throw new RuntimeException('Non-selectable collection, can not paginate results');
		}
		[$page, $resultPerPage] = $pagination;
		$page++;// 0-indexed to 1-indexed
		$resultMax = $page * $resultPerPage;
		$resultMin = $resultMax - $resultPerPage;
		
		// Require fetch="EXTRA_LAZY" on relations to prevent loading
		$count = $collection->count();
		
		$criteria = Criteria::create();
		$criteria
			->setMaxResults($resultPerPage)
			->setFirstResult($resultMin);
		$results = $collection->matching($criteria);
		
		return $this->getPaginatedResult($results, $page, $resultPerPage, $count);
	}
	
	public function create(AbstractEntity $entity): static {
		$this->setupCreate($entity);
		$this->entityManager->persist($entity);
		
		return $this;
	}
	
	protected function setupCreate(AbstractEntity $entity): void {
		//		$entity->setId(Uuid::v4());// Uuid4 is totally random
		//		$entity->setCreationDate(new DateTime());
		//		$entity->setCreationUser($this->security->getUser());
		//		$entity->setCreationIp($this->requestStack->getCurrentRequest()?->getClientIp() ?? '127.0.0.1');
	}
	
	public function refresh(AbstractEntity &$entity): void {
		if( $entity->isNew() ) {
			// Manually refresh new entities !
			//			$entity->refresh($this);
			
			// Else no way to refresh it properly
			return;
		}
		// Entity is persisted in db, we would to reload it
		$reload = true;
		if( $this->entityManager->contains($entity) ) {
			// Persisted by doctrine
			$this->entityManager->refresh($entity);
			$reload = false;
		}
		if( $reload ) {
			// Not managed by Doctrine but (should be) existing, so we reload
			$this->reload($entity);
		}
	}
	
	/**
	 * @param AbstractEntity|null $entity
	 * @param $newIsNull
	 * @return bool
	 * @see getFreshEntity() may be better
	 */
	public function reload(?AbstractEntity &$entity, $newIsNull = true): bool {
		if( !$entity ) {
			return false;
		}
		if( $entity->isNew() ) {
			if( $newIsNull ) {
				$entity = null;
			}
		} else {
			$refreshedEntity = $this->entityManager->getRepository(get_class($entity))->find($entity->getId());
			if( $refreshedEntity ) {
				$entity = $refreshedEntity;
			} else {
				// Not existing in db for real, may be stored in session
				// Clone to make it new
				$entity = clone $entity;
				// Manually refresh new entities !
				if( method_exists($entity, 'refresh') ) {
					$entity->refresh($this);
				}
			}
		}
		
		return true;
	}
	
	/**
	 * @template T of AbstractEntity
	 * @param T $entity
	 * @return T
	 * @throws ORMException
	 */
	public function getFreshEntity(AbstractEntity $entity): AbstractEntity {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this->entityManager->getReference(get_class($entity), $entity->getId());
	}
	
	public function getRepository(string $class): EntityRepository {
		return $this->entityManager->getRepository($class);
	}
	
	public function createList(array $entities): static {
		foreach( $entities as $entity ) {
			$this->setupCreate($entity);
			$this->entityManager->persist($entity);
		}
		
		return $this;
	}
	
	public function updateList(array $entities): static {
		foreach( $entities as $entity ) {
			$this->update($entity);
		}
		
		return $this;
	}
	
	public function update(AbstractEntity $entity): static {
		$this->setupUpdate($entity);
		$this->entityManager->persist($entity);
		
		return $this;
	}
	
	protected function setupUpdate(AbstractEntity $entity): void {
		if( method_exists($entity, 'setModificationDate') ) {
			$entity->setModificationDate(new DateTime());
		}
		//		if( method_exists($entity, 'onUpdate') ) {
		//			$entity->onUpdate();
		//		}
	}
	
	public function remove(AbstractEntity $entity): static {
		$this->entityManager->remove($entity);
		
		return $this;
	}
	
	public function detachList(array $entities): void {
		foreach( $entities as $entity ) {
			$this->entityManager->detach($entity);
		}
	}
	
	public function flush(): void {
		$this->entityManager->flush();
	}
	
	public function clear(): void {
		$this->entityManager->clear();
	}
	
	public function clearAllEntities(array $entityClasses): void {
		$connection = $this->entityManager->getConnection();
		$dbPlatform = $connection->getDatabasePlatform();
		
		// Truncate ignores transaction and commit immediately
		// Disable foreign key checks
		$connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
		
		foreach( $entityClasses as $class ) {
			$cmd = $this->entityManager->getClassMetadata($class);
			$sql = $dbPlatform->getTruncateTableSql($cmd->getTableName());
			$connection->executeStatement($sql);
		}
		
		// Enable foreign key checks
		$connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
	}
	
	public function getAttachedCount(): int {
		$unitOfWork = $this->entityManager->getUnitOfWork();
		$identityMap = $unitOfWork->getIdentityMap();
		
		$count = 0;
		foreach( $identityMap as $entities ) {
			$count += count($entities);
		}
		
		return $count;
	}
	
}
