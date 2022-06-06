<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use ErrorException;
use Psr\Log\LoggerInterface;
use Sowapps\SoCoreBundle\Entity\AbstractEntity;

abstract class AbstractEntityService {
	
	protected EntityManagerInterface $entityManager;
	
	protected LoggerInterface $logger;
	
	/**
	 * @param LoggerInterface $logger
	 * @return AbstractEntityService
	 * @required
	 */
	public function setLogger(LoggerInterface $logger): AbstractEntityService {
		$this->logger = $logger;
		
		return $this;
	}
	
	/**
	 * @param EntityManagerInterface $entityManager
	 * @return AbstractEntityService
	 * @required
	 */
	public function setEntityManager(EntityManagerInterface $entityManager): AbstractEntityService {
		$this->entityManager = $entityManager;
		
		return $this;
	}
	
	/**
	 * @param \Sowapps\SoCoreBundle\Entity\AbstractEntity[] $entities
	 * @return array
	 */
	public function flattenEntities(array $entities): array {
		return array_map(function (AbstractEntity $entity) {
			return $entity->getId();
		}, $entities);
	}
	
	/**
	 * Use save only if you don't know if the entity is just updated or created
	 *
	 * @param AbstractEntity $entity
	 */
	public function save(AbstractEntity $entity) {
		$this->prepare($entity);
		$this->flush();
	}
	
	/**
	 * Prepare to persist entity without flush
	 * Use prepare only if you don't know if the entity is just updated or created
	 *
	 * @param \Sowapps\SoCoreBundle\Entity\AbstractEntity $entity
	 */
	public function prepare(AbstractEntity $entity) {
		if( $entity->isNew() ) {
			$this->prepareCreate($entity);
		} else {
			$this->prepareUpdate($entity);
		}
	}
	
	public function prepareCreate(AbstractEntity $entity) {
		$this->persist($entity);
	}
	
	public function persist(AbstractEntity $entity) {
		$this->entityManager->persist($entity);
		$this->onSave($entity);
	}
	
	public function onSave(AbstractEntity $entity) {
		// Default do nothing
	}
	
	public function prepareUpdate(AbstractEntity $entity) {
		$this->onSave($entity);
	}
	
	public function flush() {
		$this->entityManager->flush();
	}
	
	public function clearCache($classes = null) {
		if( !is_array($classes) ) {
			$classes = [$classes];
		}
		foreach( $classes as $class ) {
			$this->entityManager->clear($class);
		}
	}
	
	public function isPersisted(AbstractEntity $entity): bool {
		return $this->entityManager->contains($entity);
	}
	
	public function create(AbstractEntity $entity) {
		$this->prepareCreate($entity);
		$this->flush();
	}
	
	/**
	 * @param \Sowapps\SoCoreBundle\Entity\AbstractEntity $entity
	 */
	public function update(AbstractEntity $entity) {
		$this->prepareUpdate($entity);
		$this->flush();
	}
	
	public function remove(AbstractEntity $entity) {
		// Remove
		if( $this->prepareRemove($entity) ) {
			$this->entityManager->flush();
		}
	}
	
	/**
	 * @param \Sowapps\SoCoreBundle\Entity\AbstractEntity $entity
	 * @return bool
	 */
	public function prepareRemove(AbstractEntity $entity): bool {
		// Remove
		if( $this->onRemove($entity) !== false ) {
			$this->entityManager->remove($entity);
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param $entity
	 * @return bool
	 * @deprecated Called but will be removed, use prepareRemove
	 */
	protected function onRemove($entity): bool {
		// Default do nothing
		return true;
	}
	
	public function refresh(AbstractEntity &$entity) {
		if( $entity->isNew() ) {
			// Manually refresh new entities !
			if( method_exists($entity, 'refresh') ) {
				$entity->refresh($this);
			}
			
			// Else no way to refresh it properly
			return;
		}
		// Entity is persisted in db, we would to reload it
		$reload = true;
		if( $this->entityManager->contains($entity) ) {
			// Persisted by doctrine
			try {
				$this->entityManager->refresh($entity);
				$reload = false;
			} catch( ErrorException $exception ) {
			}
		}
		if( $reload ) {
			// Not managed by Doctrine but (should be) existing, so we reload
			$this->reload($entity);
		}
	}
	
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
	
	public function reloadCollection(Collection &$collection, $newIsNull = true) {
		$items = $collection->toArray();
		$collection = new ArrayCollection();
		foreach( $items as &$item ) {
			$this->reload($item, $newIsNull);
			if( $item ) {
				$collection->add($item);
			}
		}
	}
	
}
