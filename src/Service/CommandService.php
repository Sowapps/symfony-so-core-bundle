<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Service;

use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Entity\ClientSession;
use Sowapps\SoCore\Repository\AbstractUserRepository;
use Symfony\Component\HttpFoundation\Session\Session;

class CommandService {
	// TODO Fix duplicate with ClientSessionService
	const KEY_AUTHENTICATED_USER_ID = 'AUTH_USER_ID';
	const KEY_CLIENT_SESSION_ID = 'CLIENT_SESSION_ID';
	
	protected AbstractUserRepository $userRepository;
	
	public function __construct(
		protected EntityService        $entityService,
		protected ClientSessionService $clientSessionService,
		protected UserService          $userService,
	) {
		$this->userRepository = $this->userService->getUserRepository();
	}
	
	public function setSessionUser(Session $session, AbstractUser $user): void {
		$session->set(CommandService::KEY_AUTHENTICATED_USER_ID, $user->getId());
		// Authenticating while already having a client session, auto connect session to user
		$clientSession = $this->clientSessionService->getClientSession($session, $user);
		if( !$clientSession->getUser() ) {
			$clientSession->setUser($user);
		} else if( !$clientSession->getUser()->equals($user) ) {
			// Authenticating with a new user while already authenticated, reset client session
			$this->clientSessionService->disconnectClientSession($session);
			$this->clientSessionService->assignNewClientSession($session, $user);
			// End request with a new client session
		}
	}
	
	/**
	 * @param Session $session
	 * @return bool True if client session was reset
	 */
	public function disconnectSessionUser(Session $session): bool {
		$session->remove(CommandService::KEY_AUTHENTICATED_USER_ID);
		
		return $this->disconnectClientSession($session);// If logout, reset client session to prevent sharing client session between users
	}
	
	public function getSessionUser(Session $session): ?AbstractUser {
		$userId = $session->get(static::KEY_AUTHENTICATED_USER_ID);
		$user = null;
		if( $userId ) {
			$user = $this->userRepository->find($userId);
		}
		
		return $user;
	}
	
	private function disconnectClientSession(Session $session): bool {
		return (bool)$session->remove(CommandService::KEY_CLIENT_SESSION_ID);
	}
	
	public function getClientSession(Session $session, ?AbstractUser $user): ClientSession {
		return $this->clientSessionService->getClientSession($session, $user);
	}
	
}
