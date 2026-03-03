<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Security;

use Sowapps\SoCore\Service\ApiTokenService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

readonly class ApiTokenHandler implements AccessTokenHandlerInterface {
	public function __construct(
		private ApiTokenService $apiTokenService,
		private RequestStack    $requestStack,
	) {
	}
	
	public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge {
		// Get token entity from token string
		$token = $this->apiTokenService->getToken($accessToken);
		if( !$token ) {
			throw new BadCredentialsException('Invalid credentials.');
		}
		
		// Get current request to apply restriction
		$request = $this->requestStack->getCurrentRequest();
		if( !$request ) {
			throw new BadCredentialsException('Invalid credentials.');
		}
		
		// Restrict usage of token on the same IP address
		if( !$request->getClientIp() || $request->getClientIp() !== $token->getIp() ) {
			throw new BadCredentialsException('Invalid credentials.');
		}
		
		// Mark token as used
		$this->apiTokenService->useToken($token, $request);
		
		return new UserBadge($token->getUser()->getUserIdentifier());
	}
}
