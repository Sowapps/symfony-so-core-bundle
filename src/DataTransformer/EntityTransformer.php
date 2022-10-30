<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\DataTransformer;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Sowapps\SoCore\Entity\AbstractEntity;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EntityTransformer implements DataTransformerInterface {
	
	protected EntityManagerInterface $entityManager;
	protected ObjectRepository $repository;
	protected string $notFoundMessage = 'entity.getById.notFound';
	
	public function __construct(EntityManagerInterface $entityManager, ?string $class = null) {
		$this->entityManager = $entityManager;
		if( $class ) {
			$this->setRepositoryOfClass($class);
		}
	}
	
	public function cloneForClass(string $class): EntityTransformer {
		$clone = clone $this;
		$clone->setRepositoryOfClass($class);
		
		return $clone;
	}
	
	/**
	 * @param string $class
	 */
	public function setRepositoryOfClass(string $class): void {
		$this->setRepository($this->entityManager->getRepository($class));
	}
	
	/**
	 * @return ServiceEntityRepository
	 */
	public function getRepository(): ObjectRepository {
		return $this->repository;
	}
	
	/**
	 * @param ObjectRepository $repository
	 */
	public function setRepository(ObjectRepository $repository): void {
		$this->repository = $repository;
	}
	
	/**
	 * Transforms an entity to an id
	 *
	 * @param AbstractEntity|null $entity
	 * @return int|null
	 */
	public function transform($entity): ?int {
		if( !$entity ) {
			return null;
		}
		
		return $entity->getId();
	}
	
	/**
	 * Transforms an id to an entity
	 *
	 * @param AbstractEntity|int|null $value
	 * @return AbstractEntity|null
	 * @throws TransformationFailedException if object (issue) is not found.
	 */
	public function reverseTransform($value): ?AbstractEntity {
		//		dump('EntityTransformer::reverseTransform', $value);
		if( !$value ) {
			//			dump('EntityTransformer::reverseTransform - NULL');
			return null;
		}
		if( $value instanceof AbstractEntity ) {
			//			dump('EntityTransformer::reverseTransform - ALREADY ENTITY');
			return $value;
		}
		// Id
		$entity = $this->repository->find($value);
		if( !$entity ) {
			$failure = new TransformationFailedException(sprintf('No entity "%s" with id "%s" found!', $this->repository->getClassName(), $value));
			$failure->setInvalidMessage($this->notFoundMessage, [
				'entityId' => $value,
			]);
			throw $failure;
		}
		
		//		dump('EntityTransformer::reverseTransform', $entity);
		
		return $entity;
	}
	
}
