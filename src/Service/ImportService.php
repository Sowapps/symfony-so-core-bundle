<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Service;

use Sowapps\SoCore\Core\DBAL\AbstractRepository;
use Sowapps\SoCore\Core\FileExport\CsvFormat;
use Sowapps\SoCore\Core\FileExport\CsvParser;
use Sowapps\SoCore\Core\FileExport\ImportMode;
use Sowapps\SoCore\Entity\AbstractEntity;use Sowapps\SoCore\Entity\AbstractUser;use Sowapps\SoCore\Model\FileImportDto;
use SplFileInfo;
use Throwable;

readonly class ImportService {
	
	public function __construct(
		private FileService     $fileService,
		private EntityService   $entityService,
		private UserService     $userService,
		private SecurityService $securityService,
		private CsvParser       $parser,
	) {
	}
	
	/**
	 * Process request to import entities
	 * @warning With a large file, as we only flush at the end, it could be too huge for memory
	 * @warning Use a cloned EntityManager, so the main EntityManager is not modified, but it is using the same connection and may affect transactions
	 */
	public function importDtoCsv(FileImportDto $importDto, string $class, array $columns, array $idProperties): array {
		return $this->importFileCsv($this->fileService->getStoredFile($this->fileService->getFile($importDto->fileId)), $importDto->mode, $class, $columns, $idProperties);
	}
	
	/**
	 * Process request to import entities
	 * @warning With a large file, as we only flush at the end, it could be too huge for memory
	 * @warning Use a cloned EntityManager, so the main EntityManager is not modified, but it is using the same connection and may affect transactions
	 */
	public function importFileCsv(SplFileInfo $file, ImportMode $mode, string $class, array $columns, array $idProperties): array {
		$format = new CsvFormat(
			delimiter: ',',
			withUtf8Bom: false,
		);
		
		// Use another entity manager for bulk import with truncate
		$entityManager = $this->entityService->cloneEntityManager();
		/** @var AbstractRepository $repository */
		$repository = $entityManager->getRepository($class);
		$parser = $this->parser;
		
		// Fix compat with EntityLifecycleSubscriber
		/** @var AbstractUser $userRef */
		$userRef = $entityManager->getReference($this->userService->getUserClass(), $this->securityService->getCurrentUser()->getId());
		
		$deleted = 0;
		$requestedCreate = 0;
		$requestedUpdate = 0;
		$ignored = 0;
		if( $mode === ImportMode::Replace ) {
			// Replace: remove previous, then import all as new (no dedupe).
			// Only works with no foreign keys in the file
			$deleted = $repository->removeAll(true);// Integrity is enforced, work only with the truncate operation and an imported file not removing important languages in order
		}
		
		$in = $file->openFile('r');
		/** @var array<int, string> $errors array<line, error> */
		$errors = [];
		$line = 0;
		foreach( $parser->parse($in, $format, $columns) as $row ) {
			$line++;
			
			try {
				$existing = null;
				// Append always creates a new entity, no dedupe.
				if( $mode !== ImportMode::Append && $mode !== ImportMode::Replace ) {
					// For Add/Update we need a business key to find existing entries.
					$existingFilters = array_intersect_key($row, array_flip($idProperties));
					$existing = $repository->findOneBy($existingFilters);
				}
				
				if( $existing ) {
					if( $mode === ImportMode::Add ) {
						// Add ignores existing entries
						$ignored++;
						continue;
					}
					
					// Update updates existing entries
					$this->entityService
						->mapDto($row, $existing)
						->setupUpdate($existing);
					$entityManager->persist($existing);
					$requestedUpdate++;
					continue;
				}
				
				// Not found or mode ignoring existing => create new
				$entity = new $class();
				$this->entityService
					->mapDto($row, $entity)
					->setupCreate($entity);
				if($entity instanceof AbstractEntity) {
					// As the user is set, the EntityLifecycleSubscriber won't set it with the wrong EntityManager
					$entity->setCreateUser($userRef);
				}
				$entityManager->persist($entity);
				$requestedCreate++;
			} catch( Throwable $exception ) {
				$errors[$line] = $exception->getMessage();
			}
		}
		
		// Calculate changes
		$uow = $entityManager->getUnitOfWork();
		$uow->computeChangeSets();
		
		$processedCreate = count($uow->getScheduledEntityInsertions());
		$processedUpdate = count($uow->getScheduledEntityUpdates());
		
		// Save
		$entityManager->flush();
		
		return [
			'mode'            => $mode->value,
			'rows'            => $line,
			'requestedCreate' => $requestedCreate,
			'processedCreate' => $processedCreate,
			'requestedUpdate' => $requestedUpdate,
			'processedUpdate' => $processedUpdate,
			'ignored'         => $ignored,
			'deleted'         => $deleted,
			'errors'          => $errors,
		];
	}
	
}
