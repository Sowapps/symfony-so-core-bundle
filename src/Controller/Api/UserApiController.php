<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller\Api;

use Closure;
use Sowapps\SoCore\Core\Controller\AbstractApiEntityController;
use Sowapps\SoCore\Entity\AbstractEntity;
use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Model\UserPasswordDto;
use Sowapps\SoCore\Model\UserSecurityDto;
use Sowapps\SoCore\Model\UserUpdateDto;
use Sowapps\SoCore\Service\SecurityService;
use Sowapps\SoCore\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * It provides default routes for User API, you could overwrite the route path to get it by your own way
 */
class UserApiController extends AbstractApiEntityController {
	
	public function __construct(UserService $userService) {
		parent::__construct($userService->getUserRepository());
	}
	
	#[Route("/api/me", methods: ['GET'], format: 'json')]
	public function getCurrentUser(#[CurrentUser] ?AbstractUser $currentUser): Response {
		if( !$currentUser ) {
			throw new UnauthorizedHttpException('Login');
		}
		
		return $this->getOneUser($currentUser, $currentUser);
	}
	
	#[Route("/api/user/{id}", methods: ['GET'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_USER)]
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
