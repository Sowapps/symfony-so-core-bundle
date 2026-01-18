<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Service;

use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Entity\ClientSession;
use Sowapps\SoCore\Repository\AbstractUserRepository;
use Sowapps\SoCore\Repository\ClientSessionRepository;
use Symfony\Component\HttpFoundation\Session\Session;

class ClientSessionService {
	
	const KEY_AUTHENTICATED_USER_ID = 'AUTH_USER_ID';
	const KEY_CLIENT_SESSION_ID = 'CLIENT_SESSION_ID';
	protected readonly AbstractUserRepository $userRepository;
	
	public function __construct(
		protected EntityService           $entityService,
		protected ClientSessionRepository $clientSessionRepository,
		protected UserService             $userService,
	) {
		$this->userRepository = $this->userService->getUserRepository();
	}
	
	public function disconnectClientSession(Session $session): bool {
		return (bool)$session->remove(ClientSessionService::KEY_CLIENT_SESSION_ID);
	}
	
	private function getSavedClientSession(Session $session): ?ClientSession {
		$clientSessionId = $session->get(static::KEY_CLIENT_SESSION_ID);
		
		return $clientSessionId ? $this->clientSessionRepository->find($clientSessionId) : null;
	}
	
	public function assignNewClientSession(Session $session, ?AbstractUser $user): ClientSession {
		$clientSession = new ClientSession();
		if( $user ) {
			$clientSession->setUser($user);
		}
		$this->entityService->create($clientSession)->flush();
		$session->set(static::KEY_CLIENT_SESSION_ID, $clientSession->getId());
		
		return $clientSession;
	}
	
	public function getClientSession(Session $session, ?AbstractUser $user): ClientSession {
		$clientSession = $this->getSavedClientSession($session);
		if( !$clientSession ) {
			$clientSession = $this->assignNewClientSession($session, $user);
		}
		
		return $clientSession;
	}
	
}
