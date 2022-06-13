<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Service;

use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Sowapps\SoCoreBundle\Core\Entity\Persistable;
use Sowapps\SoCoreBundle\Core\File\AsciiSlugger;
use Sowapps\SoCoreBundle\Core\File\FileNameSlugger;
use Sowapps\SoCoreBundle\Core\File\Generator\WkHtmlToPdfGenerator;
use Sowapps\SoCoreBundle\Core\File\LocalHttpFile;
use Sowapps\SoCoreBundle\DBAL\EnumFileSourceType;
use Sowapps\SoCoreBundle\Entity\AbstractEntity;
use Sowapps\SoCoreBundle\Entity\File;
use Sowapps\SoCoreBundle\Repository\FileRepository;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment as TwigService;

class FileService extends AbstractEntityService {
	
	const TYPE_LARGE = 'large';
	const TYPE_SMALL = 'small';
	
	protected Packages $packages;
	
	protected TwigService $twig;
	
	protected ParameterBagInterface $parameters;
	
	protected UrlHelper $urlHelper;
	
	protected UrlGeneratorInterface $router;
	
	protected StringHelper $stringHelper;
	
	protected array $config;
	
	/**
	 * FileService constructor
	 *
	 * @param Packages $packages
	 * @param TwigService $twig
	 * @param UrlHelper $urlHelper
	 * @param UrlGeneratorInterface $router
	 * @param StringHelper $stringHelper
	 * @param array $configFile
	 */
	public function __construct(Packages $packages, TwigService $twig, ParameterBagInterface $parameters, UrlHelper $urlHelper, UrlGeneratorInterface $router, StringHelper $stringHelper, array $configFile) {
		$this->packages = $packages;
		$this->twig = $twig;
		$this->parameters = $parameters;
		$this->urlHelper = $urlHelper;
		$this->router = $router;
		$this->stringHelper = $stringHelper;
		$this->config = $configFile;
	}
	
	public function onSave(AbstractEntity $entity) {
		parent::onSave($entity);
		
		if( $entity instanceof File && !$entity->getOutputName() ) {
			$slugger = new FileNameSlugger();
			$name = $entity->getName();
			// Ensure output name is having the extension of the file
			$extension = $entity->getExtension();
			if( substr($name, -strlen($extension)) !== $extension ) {
				$name .= '.' . $extension;
			}
			$entity->setOutputName($slugger->slug($name));
		}
	}
	
	//	public function createNewPdfGenerator(): WkHtmlToPdfGenerator {
	//		$generator = new WkHtmlToPdfGenerator();
	//		$generator->setBinaryPath($this->parameters->get('wkhtmltopdf.binary'));
	//
	//		$tempPath = $this->parameters->get('wkhtmltopdf.temp_path');
	//		if( $tempPath && !is_dir($tempPath) ) {
	//			mkdir($tempPath);
	//		}
	//		$generator->setTempPath($tempPath ?: sys_get_temp_dir());
	//		$generator->setViewportSize('1024x768');
	//
	//		return $generator;
	//	}
	
	public function upload(UploadedFile $uploadedFile, ?string $purpose, ?string $label = null, ?DateTimeImmutable $expireDate = null, ?Persistable $parent = null): ?File {
		$file = new File();
		$file->setName($label ?: $uploadedFile->getClientOriginalName());
		$file->setExtension($uploadedFile->getClientOriginalExtension());
		$file->setMimeType($uploadedFile->getMimeType());
		$file->setPurpose($purpose);
		$file->setPrivateKey($this->stringHelper->generateKey());
		$file->setSourceType(EnumFileSourceType::HTTP_UPLOAD);
		$file->setSourceName($uploadedFile->getClientOriginalName());
		$file->setSourceUrl(null);
		if( $expireDate ) {
			$file->setExpireDate($expireDate);
		}
		if( $parent ) {
			$file->setParentId($parent->getId());
		}
		
		$this->create($file);
		
		try {
			// Require to be stored in db, need an id
			$uploadedFile->move($this->config['store_path'], $file->getLocalName());
		} catch( FileException $e ) {
			$this->entityManager->remove($file);
			$this->entityManager->flush();
			
			return null;
		}
		
		return $file;
	}
	
	public function import(string $filePath, string $purpose, ?string $label = null, ?DateTimeImmutable $expireDate = null, $extension = null, ?File $file = null): ?File {
		$filesystem = new Filesystem();
		$mimeTypes = new MimeTypes();
		$fileName = basename($filePath);
		$file ??= new File();
		$file->setName($label ?: $fileName);
		$file->setExtension($extension ?: $this->getFileExtension($fileName));
		$file->setMimeType($mimeTypes->guessMimeType($filePath));
		$file->setPurpose($purpose);
		$file->setPrivateKey($this->stringHelper->generateKey());
		$file->setSourceType(EnumFileSourceType::LOCAL);
		$file->setSourceName($fileName);
		$file->setSourceUrl(null);
		if( $expireDate ) {
			$file->setExpireDate($expireDate);
		}
		$this->save($file);
		
		try {
			$filesystem->copy($filePath, $this->config['store_path'] . DIRECTORY_SEPARATOR . $file->getLocalName());
		} catch( IOException $e ) {
			$this->entityManager->remove($file);
			$this->entityManager->flush();
			
			return null;
		}
		
		return $file;
	}
	
	public function getFileExtension(string $fileName): string {
		return pathinfo($fileName, PATHINFO_EXTENSION);
	}
	
	public function getHttpFile(string|File $file): LocalHttpFile {
		$entity = null;
		if( $file instanceof File ) {
			$entity = $file;
		}
		$path = $this->getAssetPath($file);
		$url = $this->getAssetUrl($file);
		
		return new LocalHttpFile($path, $url, $entity);
	}
	
	public function getAlternativeFile(LocalHttpFile $file, string $type): string|File {
		if( !$file->getFile() && $type === self::TYPE_SMALL ) {
			// For files by name : "-large" must be present in file name and alternative "-small" should be provided
			$this->getHttpFile(str_replace('-large', '-small', $file->getPath()));
		}
		
		return $file;
	}
	
	public function getAssetPath($path): string {
		if( $path instanceof File ) {
			return $this->getFileLocalPath($path);
		}
		if( file_exists($path) ) {
			// Absolute existing path
			return $path;
		}
		
		// Calculate absolute from relative path
		return $this->config['public_path'] . '/' . $path;
	}
	
	public function getAssetUrl($path): string {
		if( $path instanceof File ) {
			return $this->getFileUrl($path, false);
		}
		
		return $this->urlHelper->getAbsoluteUrl($this->packages->getUrl($path));
	}
	
	public function getFileUrl(File $file, bool $download = true): string {
		return $this->router->generate('file_download', [
			'id'        => $file->getId(),
			'key'       => $file->getPrivateKey(),
			'extension' => $file->getExtension(),
			'action'    => $download ? 'download' : 'display',
		], UrlGeneratorInterface::ABSOLUTE_URL);
	}
	
	/**
	 * @return QueryBuilder
	 */
	public function queryExpiredFiles(): QueryBuilder {
		return $this->getFileRepository()
			->query()
			->where('file.expireDate IS NOT NULL')
			->andWhere("file.expireDate < :before")
			->setParameter('before', new DateTime());
	}
	
	/**
	 * @return FileRepository
	 */
	public function getFileRepository(): FileRepository {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this->entityManager->getRepository(File::class);
	}
	
	/**
	 * @param Persistable $entity
	 * @param string $purpose
	 * @return File
	 * @throws NonUniqueResultException
	 */
	public function getByEntityPurpose(Persistable $entity, string $purpose): File {
		return $this->queryEntityPurpose($entity, $purpose)
			->setMaxResults(1)
			->getQuery()->getOneOrNullResult();
	}
	
	/**
	 * @param Persistable $parent
	 * @param string $purpose
	 * @return QueryBuilder
	 */
	public function queryEntityPurpose(Persistable $parent, string $purpose): QueryBuilder {
		return $this->queryPurpose($purpose)
			->andWhere('file.parentId = :parentId')
			->setParameter('parentId', $parent->getId())
			->orderBy('file.position', 'ASC');
	}
	
	/**
	 * @param string $purpose
	 * @return QueryBuilder
	 */
	public function queryPurpose(string $purpose): QueryBuilder {
		return $this->getFileRepository()
			->query()
			->where('file.purpose LIKE :purpose')
			->setParameter('purpose', $purpose);
	}
	
	/**
	 * @param $entity
	 * @return bool
	 */
	public function prepareRemove($entity): bool {
		if( $entity instanceof File ) {
			$path = $this->getFileLocalPath($entity);
			if( file_exists($path) ) {
				if( !is_writable($path) ) {
					return false;
				}
				unlink($path);
			}
		}
		
		return parent::prepareRemove($entity);
	}
	
	public function getLocalFile($file): SymfonyFile {
		return new SymfonyFile($this->getFileLocalPath($file instanceof File ? $file : $this->getFile($file)));
	}
	
	public function getFileLocalPath(File $file): string {
		return $this->config['store_path'] . '/' . $file->getLocalName();
	}
	
	/**
	 * @param $fileId
	 * @return File|null
	 */
	public function getFile($fileId): ?File {
		return $this->getFileRepository()->find($fileId);
	}
	
}
