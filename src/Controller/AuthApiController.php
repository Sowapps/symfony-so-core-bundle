<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller;

use Sowapps\SoCore\Core\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthApiController extends AbstractController {
	
	/**
	 * Authenticate the user from API Token to session via an API Endpoint dedicated to the web request (non API).
	 * So the user can be authenticated using an API Token, which is used to authenticate the user in the web application.
	 * Using the main firewall, Symfony is authenticating the user, and as this firewall has stateless=false, the session shares the authentication
	 */
	#[Route("/auth-api/connect", name: "so_core_auth_connect", methods: ['POST'], format: "json")]
	public function index(Request $request): Response {
		// TODO Is session migration required ?
		// Force une session + cookie si tu veux être sûr
		$request->getSession()->migrate(true);
		$request->getSession()->set('auth_connect', 1);
		
		return new Response(null, 204);
	}
	
}
