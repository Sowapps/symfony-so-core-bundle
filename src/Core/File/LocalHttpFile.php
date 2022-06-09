<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Core\File;

use Sowapps\SoCoreBundle\Entity\File;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

class LocalHttpFile extends SymfonyFile {
	
	protected string $url;
	
	protected ?File $file = null;
	
	/**
	 * LocalHttpFile constructor
	 *
	 * @param string $path
	 * @param string $url
	 * @param File|null $file
	 * @param bool $checkPath
	 */
	public function __construct(string $path, string $url, ?File $file = null, bool $checkPath = true) {
		parent::__construct($path, $checkPath);
		$this->url = $url;
		$this->file = $file;
	}
	
	/**
	 * @return string
	 */
	public function getUrl(): string {
		return $this->url;
	}
	
	/**
	 * @return string
	 */
	public function getPath(): string {
		return $this->getPathname();
	}
	
	/**
	 * @return File|null
	 */
	public function getFile(): ?File {
		return $this->file;
	}
	
}
