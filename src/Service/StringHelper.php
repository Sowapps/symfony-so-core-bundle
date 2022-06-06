<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Service;

use Symfony\Component\String\Slugger\SluggerInterface;

class StringHelper {
	
	protected SluggerInterface $slugger;
	
	/**
	 * StringHelper constructor
	 *
	 * @param SluggerInterface $slugger
	 */
	public function __construct(SluggerInterface $slugger) {
		$this->slugger = $slugger;
	}
	
	/**
	 * Generate a random string of 32 characters
	 *
	 * @return string
	 */
	public function convertToSlug($text): string {
		return strtolower($this->slugger->slug($text));
	}
	
	/**
	 * Generate a random string of 32 characters
	 *
	 * @return string
	 */
	public function generateKey(): string {
		return $this->generateRandomString(32);
	}
	
	/**
	 * Generate a random string of $length characters (max 40)
	 *
	 * @param $length
	 * @return string
	 */
	public function generateRandomString($length): string {
		return substr(sha1(random_bytes(100)), 0, $length);
	}
	
	/**
	 * Parse Markdown
	 *
	 * @param string $text
	 * @return string
	 */
	//	public function parseMarkdown(?string $text) {
	//		if( $text === null ) {
	//			return null;
	//		}
	//		$parser = new Markdown();
	//		return $parser->parse($text);
	//	}
	
}
