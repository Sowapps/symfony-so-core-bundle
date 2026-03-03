<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller\Api;

use Sowapps\SoCore\Core\Controller\AbstractApiController;
use Sowapps\SoCore\Core\ProcessOption\FormatOptions;
use Sowapps\SoCore\Entity\AbstractEntity;
use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Service\ApiTokenService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class SecurityApiController extends AbstractApiController {
	public function __construct(private ApiTokenService $apiTokenService) {
	}
	
	/**
	 * API Authentication from user credentials to token
	 */
	#[Route("/api/security/authenticate", name: 'api_security_authenticate', methods: ['POST'], format: 'json')]
	public function authenticate(#[CurrentUser] ?AbstractUser $user, Request $request, #[MapQueryParameter] bool $withApi = false): Response {
		if( !$user ) {
			return $this->respond([
				'message' => $this->translator->trans('so.auth.invalidCredentials'),
			], Response::HTTP_UNAUTHORIZED);
		}
		
		$data = [
			'user' => $user->asArray(new FormatOptions([AbstractEntity::FORMAT_PRIVATE]))
		];
		if( $withApi ) {
			$data['apiToken'] = $this->apiTokenService->createToken($user, $request->getClientIp());
		}
		
		return $this->respond($data);
	}
	
	#[Route("/api/security/disconnect", name: 'api_security_disconnect', methods: ['POST'], format: 'json')]
	public function disconnect(Security $security): Response {
		if( $security->getUser() ) {
			$security->logout(false);
		}
		
		return new Response(null, 204);
	}
	
}
