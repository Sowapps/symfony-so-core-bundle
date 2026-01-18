<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Controller;

use Closure;
use Doctrine\ORM\QueryBuilder;
use Exception;
use InvalidArgumentException;
use LogicException;
use Sowapps\SoCore\Core\Assertion\UserAssert;
use Sowapps\SoCore\Core\DBAL\AbstractRepository;
use Sowapps\SoCore\Core\Entity\EntitySearch;
use Sowapps\SoCore\Core\Entity\PaginatedResult;
use Sowapps\SoCore\Core\ProcessOption\CriteriaOptions;
use Sowapps\SoCore\Core\ProcessOption\FormatOptions;
use Sowapps\SoCore\Core\ProcessOption\PaginationOptions;
use Sowapps\SoCore\Entity\AbstractEntity;
use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Entity\ClientSession;
use Sowapps\SoCore\Exception\UserException;
use Sowapps\SoCore\Exception\ValidationException;
use Sowapps\SoCore\Service\ClientSessionService;
use Sowapps\SoCore\Service\EntityService;
use Sowapps\SoCore\Service\SecurityService;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Service\Attribute\Required;

class AbstractApiController extends AbstractController {
	
	protected readonly ClientSessionService $clientSessionService;
	protected readonly EntityService $entityService;
	protected readonly SecurityService $securityService;
	
	/**
	 * If you start with a client session, you end request with this one, but it could be deleted, and it will be reset at next request.
	 */
	protected ?ClientSession $clientSession = null;
	
	protected function getUser(): ?AbstractUser {
		$user = parent::getUser();
		
		if( $user && !$user instanceof AbstractUser ) {
			throw new LogicException('User is not a valid user entity.');
		}
		
		return $user;
	}
	
	protected function requireUser(): UserAssert {
		return new UserAssert($this->securityService, $this->getUser());
	}
	
	protected function requireAuthenticated(): static {
		$this->requireUser()->isAuthenticatedOr()->throw();
		
		return $this;
	}
	
	protected function requireAdmin(): static {
		$this->requireUser()->isAdminOr()->throw();
		
		return $this;
	}
	
	protected function requireOwner(AbstractEntity $entity): static {
		$this->requireUser()->isAdminOr()->isOwnerOr($entity)->throw();
		
		return $this;
	}
	
	/**
	 * @param Request $request
	 * @param mixed|null $defaultFormat True if default format is admin, else public
	 * @return array[CriteriaOptions, PaginationOptions, FormatOptions]
	 */
	protected function getRequestOptions(Request $request, mixed $defaultFormat = null): array {
		$criteria = $this->getRequestCriteria($request);
		$pagination = $this->getRequestPagination($request);
		$format = $this->getRequestFormat($request, $defaultFormat);
		$format->setFormatter($this->getEntityFormatter());
		
		return [$criteria, $pagination, $format];
	}
	
	protected function getEntityFormatOptions(string $formatText): FormatOptions {
		$format = new FormatOptions([$formatText]);
		$format->setFormatter($this->getEntityFormatter());
		
		return $format;
	}
	
	protected function getEntityFormatter(): ?Closure {
		return null;
	}
	
	protected function respond(mixed $data, int $status = 200, array $headers = []): Response {
		// If output format is JSON, format json
		// We handle only this format
		return $this->json($data, $status, $headers);
	}
	
	protected function respondPaginated(PaginatedResult $result, FormatOptions $format, array $headers = []): Response {
		$statusCode = 200;
		if( $result->getPageCount() > 1 ) {
			// There is several page, so its partial content
			$statusCode = 206;
		}
		$headers['Pagination-Ressources-Total'] = $result->getCount();// Total number of ressources
		$headers['Pagination-Page-Current'] = $result->getPage();// Current page number
		$headers['Pagination-Page-Total'] = $result->getPageCount();// Total number of pages
		$headers['Pagination-Ressources-Per-Page'] = $result->getResultPerPage();// Max number of ressources per page
		
		$list = [...$result->getResults()];
		if( $list ) {
			$list = $this->formatEntityList($list, $format);
		}
		
		return $this->respond($list, $statusCode, $headers);
	}
	
	protected function formatEntityList(array $entities, FormatOptions $format): array {
		return array_map(fn(AbstractEntity $entity) => $this->formatEntity($entity, $format), $entities);
	}
	
	protected function formatEntity(AbstractEntity $entity, FormatOptions $format): array {
		return $format->format($entity);
	}
	
	protected function getRequestFormat(Request $request, string|array|null|bool $default = null): FormatOptions {
		// Format default parameter
		$default ??= false;
		if( is_bool($default) ) {
			$default = $default ? AbstractEntity::FORMAT_ADMIN : AbstractEntity::FORMAT_PUBLIC;
		}
		// Parse request
		try {
			// String or unset
			$formats = [$request->query->get('format', $default)];
		} catch( BadRequestException $exception ) {
			// Array
			$formats = $request->query->all('format');
		}
		if( !in_array($default, $formats, true) ) {
			// Default is always present
			$formats[] = $default;
		}
		
		return new FormatOptions($formats);
	}
	
	protected function getRequestCriteria(Request $request): CriteriaOptions {
		return new CriteriaOptions($request->query->all());
	}
	
	protected function getRequestPagination(Request $request): ?PaginationOptions {
		if( !$request->headers->has('Accept-Pagination') ) {
			return null;
		}
		$limit = $this->getDefaultPaginationLimit();
		$page = $request->query->get('page', '1');
		$size = $request->query->get('pageLimit', $limit);
		if( !ctype_digit($page) || $page < 1 ) {
			throw new InvalidArgumentException('Invalid page number');
		}
		// Allow $size = 0 as null to select All
		if( !ctype_digit($size) || $size < 1 || ($limit && $size > $limit) ) {
			throw new InvalidArgumentException(sprintf('Invalid size number, it must be between 1 and %d', $limit));
		}
		
		return new PaginationOptions((int)$page, (int)$size);
	}
	
	protected function getDefaultPaginationLimit(): int {
		return 50;
	}
	
	public function getClientSession(): ClientSession {
		if( !$this->clientSession ) {
			// TODO [Low] Add client session by auth token (Handle api authentication instead of cookie ?)
			$this->clientSession = $this->clientSessionService->getClientSession($this->getSession(), $this->getUser());
		}
		
		return $this->clientSession;
	}
	
	protected function respondValidationException(ValidationException $exception): Response {
		return $this->respond($exception, 400);
	}
	
	protected function throwNeverImplemented(string $feature) {
		throw new NotFoundHttpException("Feature $feature is intentionally not implemented and should never be");
	}
	
	/**
	 * TODO May be deprecated ?
	 */
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
	
	/**
	 * TODO May be deprecated ?
	 */
	public function getRequestFilters(Request $request): array {
		$filters = $request->query->get('filter', []);
		if( !is_array($filters) ) {
			$filters = [];
		}
		
		return $filters;
	}
	
	/**
	 * TODO May be deprecated ?
	 */
	public function getFilterTerms(array $filters): array {
		$term = $filters['term'] ?? null;
		
		$allTerms = $term ? $this->parseTerms($term) : null;
		if( !$allTerms ) {
			return [];
		}
		
		return $allTerms;
	}
	
	/**
	 * TODO May be deprecated ?
	 */
	public function parseTerms(string $term): ?array {
		$result = preg_match_all('#\w{3,}#is', $term, $allTerms);
		
		return $result ? array_unique(array_merge([$term], $allTerms[0])) : [$term];
	}
	
	/**
	 * TODO May be deprecated ?
	 */
	public function searchEntityTerm(AbstractRepository $repository, array $terms = [], ?string $alias = null, bool $publicOnly = true): EntitySearch {
		$search = new EntitySearch($repository, $alias);
		$search->setPublicOnly($publicOnly);
		if( $terms ) {
			$search->applyTerms($terms);
		} else {
			$search->prepare();
		}
		
		return $search;
	}
	
	/**
	 * TODO May be deprecated ?
	 */
	public function formatSearchResults(array $searches, array $terms = [], int $max = 20): array {
		$items = [];
		foreach( $searches as $search ) {
			$query = $search->getQuery();
			$fields = $search->getFields() ?? [];
			/** @var QueryBuilder $query */
			// Large set because we are not filtering well
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
	
	/**
	 * TODO May be deprecated ?
	 */
	public function calculateEntityScore(AbstractEntity $entity, array $terms, array $fields): int {
		$score = 0;
		foreach( $fields as $searchField ) {
			$fieldValue = call_user_func([$entity, 'get' . $searchField]);
			$score += $this->calculateValueScore($fieldValue, $terms);
		}
		
		return $score;
	}
	
	/**
	 * TODO May be deprecated ?
	 */
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
	
	#[Required]
	public function initializeAbstractApiController(
		ClientSessionService $clientSessionService,
		EntityService        $entityService,
		SecurityService      $securityService,
	): static {
		$this->clientSessionService = $clientSessionService;
		$this->entityService = $entityService;
		$this->securityService = $securityService;
		
		return $this;
	}
	
}
