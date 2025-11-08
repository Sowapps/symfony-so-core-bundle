<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Controller;

use Doctrine\ORM\QueryBuilder;
use Exception;
use Sowapps\SoCore\Core\Entity\EntitySearch;
use Sowapps\SoCore\Core\Repository\AbstractEntityRepository;
use Sowapps\SoCore\Entity\AbstractEntity;
use Sowapps\SoCore\Exception\UserException;
use Symfony\Component\HttpFoundation\Request;

class AbstractApiController extends AbstractController {
	
	public function formatException(Exception $exception, string $message): array {
		$data = [
			'text' => $exception instanceof UserException ? $exception->getMessage() : $message,
			'data' => $exception instanceof UserException ? $exception->asArray() : null,
			'code' => $exception->getCode(),
		];
		if( $this->kernel->isDebug() ) {
			$data['class'] = $exception::class;
			$data['message'] = $exception->getMessage();
			$data['file'] = $exception->getFile();
			$data['line'] = $exception->getLine();
			$data['trace'] = $exception->getTrace();
		}
		
		return $data;
	}
	
	public function getRequestFilters(Request $request): array {
		$filters = $request->get('filter', []);
		if( !is_array($filters) ) {
			$filters = [];
		}
		
		return $filters;
	}
	
	public function getFilterTerms(array $filters): array {
		$term = $filters['term'] ?? null;
		
		$allTerms = $term ? $this->parseTerms($term) : null;
		if( !$allTerms ) {
			return [];
		}
		
		return $allTerms;
	}
	
	public function parseTerms(string $term): ?array {
		$result = preg_match_all('#\w{3,}#is', $term, $allTerms);
		
		return $result ? array_unique(array_merge([$term], $allTerms[0])) : [$term];
	}
	
	public function searchEntityTerm(AbstractEntityRepository $repository, array $terms = [], ?string $alias = null, bool $publicOnly = true): EntitySearch {
		$search = new EntitySearch($repository, $alias);
		$search->setPublicOnly($publicOnly);
		if( $terms ) {
			$search->applyTerms($terms);
		} else {
			$search->prepare();
		}
		
		return $search;
	}
	
	public function formatSearchResults(array $searches, array $terms = [], int $max = 20): array {
		$items = [];
		foreach( $searches as $search ) {
			$query = $search->getQuery();
			$fields = $search->getFields() ?? [];
			/** @var QueryBuilder $query */
			// Large set because we are not filtering in a good way
			//$query->setMaxResults(200);// No limit, it's not ordered
			foreach( $query->getQuery()->toIterable() as $entity ) {
				if( $terms ) {
					$score = $this->calculateEntityScore($entity, $terms, $fields);
					$scoreIndex = -1;
					// Look for an available score key (001-000, 001-001, 001-002, ...)
					do {
						$scoreIndex++;
						$scoreKey = str_pad($score, 3, '0', STR_PAD_LEFT) . '-' . str_pad($scoreIndex, 3, '0', STR_PAD_LEFT);
					} while( isset($items[$scoreKey]) );
					
					$items[$scoreKey] = $entity;
				} else {
					// No term we don't care about sorting
					$items[] = $entity;
				}
			}
		}
		if( $terms ) {
			krsort($items, SORT_STRING);
		}
		
		return array_values(array_slice($items, 0, min($max, 50)));
	}
	
	public function calculateEntityScore(AbstractEntity $entity, array $terms, array $fields): int {
		$score = 0;
		foreach( $fields as $searchField ) {
			$fieldValue = call_user_func([$entity, 'get' . $searchField]);
			$score += $this->calculateValueScore($fieldValue, $terms);
		}
		
		return $score;
	}
	
	public function calculateValueScore(?string $value, array $terms): int {
		$score = 0;
		foreach( $terms as $termIndex => $term ) {
			$termScore = 0;
			if( !$value || stripos($value, (string) $term) === false ) {
				// Value not matching, losing score for term
				$termScore -= strlen((string) $term);
			} else {
				// Value match the term, earn score for term
				$termScore += 100 + strlen((string) $term);
			}
			$termLength = strlen((string) $term);
			// Add levenshtein score (number of characters in difference), max 10
			$termScore += max(10 - levenshtein($term, $value), 0);
			// Add order score (first is valued)
			if( !$termIndex && str_starts_with((string) $value, (string) $term) ) {
				// If starting by first term (Value "Paris 1" against "Damparis")
				$termScore += min($termLength, 5);
			}
			$score += $termScore;
		}
		
		return $score;
	}
	
}
