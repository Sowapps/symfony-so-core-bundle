<?php

namespace Sowapps\SoCore\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Sowapps\SoCore\Core\DBAL\AbstractRepository;
use Sowapps\SoCore\Entity\Language;

/**
 * @method Language|null find($id, $lockMode = null, $lockVersion = null)
 * @method Language|null findOneBy(array $criteria, array $orderBy = null)
 * @method Language[]    findAll()
 * @method Language[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends AbstractRepository<Language>
 */
class LanguageRepository extends AbstractRepository {
	
	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, Language::class, 'language');
	}
	
	/**
	 * @param string $locale
	 * @return Language|null
	 * @throws NonUniqueResultException
	 */
	public function findByLocale(string $locale): ?Language {
		return $this->query()
			->andWhere('language.locale = :locale')
			->setParameter('locale', $locale)
			->getQuery()
			->enableResultCache(3600)
			->getOneOrNullResult();
	}
	
	/**
	 * @param string $primaryCode
	 * @return Language|null
	 * @throws NonUniqueResultException
	 */
	public function findByPrimary(string $primaryCode): ?Language {
		return $this->query()
			->andWhere('language.primaryCode = :primaryCode')
			->setParameter('primaryCode', $primaryCode)
			->getQuery()
			->enableResultCache(3600)
			->getOneOrNullResult();
	}
	
}
