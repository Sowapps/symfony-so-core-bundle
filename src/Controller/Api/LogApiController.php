<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller\Api;

use Closure;
use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Sowapps\SoCore\Core\Controller\AbstractApiController;
use Sowapps\SoCore\Entity\AbstractEntity;
use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Model\UserPasswordDto;
use Sowapps\SoCore\Model\UserSecurityDto;
use Sowapps\SoCore\Model\UserUpdateDto;
use Sowapps\SoCore\Service\SecurityService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Routes for Log API
 */
class LogApiController extends AbstractApiController {
	
	#[Route("/api/log/default", methods: ['GET'], format: 'json')]
	public function getLogDetails(): Response {
		// TODO Verify access controls
		$handler = $this->getBestFileHandler();
		
		return $this->json([
			'logFile'         => $handler->getUrl(),
			'logLevelName'    => $handler->getLevel()->getName(),
			'logLevelValue'   => $handler->getLevel()->value,
			'errorLevelName'  => Level::Error->getName(),
			'errorLevelValue' => Level::Error->value,
			'levels'          => $this->getLogLevels(),
		]);
	}
	
	public function getBestFileHandler(): ?StreamHandler {
		if( !$this->logger instanceof Logger ) {
			throw new InvalidArgumentException(sprintf('Logger must be an instance of %s', Logger::class));
		}
		foreach( $this->logger->getHandlers() as $handler ) {
			if( $handler instanceof StreamHandler ) {
				return $handler;
			}
		}
		
		return null;
	}
	
	public function getLogLevels(): array {
		return array_combine(Level::NAMES, Level::VALUES);
	}
	
	#[Route("/api/user/{id}", methods: ['GET'], format: 'json')]
	public function getOneUser(AbstractUser $user, #[CurrentUser] ?AbstractUser $currentUser): Response {
		// TODO Format must be provided
		$format = AbstractEntity::FORMAT_PUBLIC;
		if( $this->securityService->isAdmin($currentUser) ) {
			$format = AbstractEntity::FORMAT_ADMIN;
		} else if( $user->isOwnedBy($currentUser) ) {
			$format = AbstractEntity::FORMAT_PRIVATE;
		}
		$format = $this->getEntityFormatOptions($format);
		
		return $this->respondEntity($user, $format);
	}
	
	#[Route("/api/user/{id}", methods: ['PATCH'], format: 'json')]
	public function patchOneUser(AbstractUser $user, #[MapRequestPayload] UserUpdateDto $userDto, Request $request): Response {
		return $this
			->requireOwner($user)
			->processRequestEntityBasicPatch($user, $userDto, $request);
	}
	
	#[Route("/api/user/{id}/security", methods: ['PATCH'], format: 'json')]
	public function patchOneUserSecurity(AbstractUser $user, #[MapRequestPayload] UserSecurityDto $userDto, Request $request): Response {
		return $this
			->requireOwner($user)
			->processRequestEntityBasicPatch($user, $userDto, $request);
	}
	
	#[Route("/api/user/{id}/password", methods: ['PATCH'], format: 'json')]
	public function patchOneUserPassword(AbstractUser $user, #[MapRequestPayload] UserPasswordDto $userDto, Request $request, SecurityService $securityService): Response {
		$userDto->password = $securityService->encodePassword($userDto->password, $user);
		return $this
			->requireOwner($user)
			->processRequestEntityBasicPatch($user, $userDto, $request);
	}
	
	#[Route("/api/user", methods: ['GET'], format: 'json')]
	public function listUsers(Request $request): Response {
		return $this
			->requireAdmin()
			->processRequestListWithBasicPagination($request);
	}
	
	protected function getEntityFormatter(): Closure {
		return fn(AbstractUser $user) => ['grantedRoles' => $this->securityService->getUserRoles($user)];
	}
	
}
