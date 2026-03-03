<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Twig\Environment as TwigEnvironment;

/**
 * TODO Remove, did it in exception subscriber
 */
readonly class AuthenticateTokenEntryPoint implements AuthenticationEntryPointInterface {
	
	public function __construct(
		private TwigEnvironment $twig,
	) {}
	
	public function start(Request $request, ?AuthenticationException $authException = null): Response
	{
//		$path = $request->getPathInfo();

//		// On ne veut déclencher ce template que pour /admin
//		if (!str_starts_with($path, '/admin')) {
//			// Fallback neutre (ou 401 JSON si tu veux)
//			return new Response('Unauthorized', 401);
//		}
		
		$html = $this->twig->render('@SoCore/system/security/auth_token.html.twig', [
			'currentUrl' => $request->getUri(),
		]);
		
		// 401 = “non authentifié” (plutôt que 403)
		return new Response($html, 401);
	}
}
