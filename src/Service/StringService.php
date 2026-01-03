<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Service;

use Symfony\Component\String\Slugger\SluggerInterface;

class StringService {
 
	public function __construct(protected SluggerInterface $slugger)
    {
    }
	
	/**
	 * Generate a random string of 32 characters
	 */
	public function convertToSlug($text): string {
		return strtolower($this->slugger->slug($text));
	}
	
	/**
	 * Generate a random string of 32 characters
	 */
	public function generateKey(): string {
		return $this->generateRandomString(32);
	}
	
	/**
	 * Generate a random string of $length characters (max 40)
	 */
	public function generateRandomString($length): string {
		return substr(sha1(random_bytes(100)), 0, $length);
	}
	
	/**
	 * Format string using values
	 */
	public function formatString(string $string, array $values): string {
		return str_replace(array_map(fn($key) => '{' . $key . '}', array_keys($values)), array_values($values), $string);
	}
	
}
