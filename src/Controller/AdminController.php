<?php

namespace Sowapps\SoCore\Controller;

use LogicException;
use Sowapps\SoCore\Config;
use Sowapps\SoCore\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Using SAW, cannot migrate to SoCore bundle without separation
 */
class AdminController extends AbstractController {
	
	#[Route("/admin", name: "so_core_admin_index")]
	public function index(): Response {
		return $this->render("@SoCore/interactive.html.twig", ['mainController' => 'admin--main']);
	}
	
	#[Route("/admin/{path}", name: "so_core_admin_any", requirements: ['path' => '.+'])]
	public function adminAnyRoutes(): Response {
		return $this->index();
	}
	
	#[Route("/admin-auth/{path}", name: "so_core_admin_auth_any", requirements: ['path' => '.+'])]
	public function adminAuthAnyRoutes(Request $request, SecurityService $securityService): Response {
		// Any access to this controller should require the admin access key
		if( !$securityService->isAdmin($this->getUser()) ) {
			// Check the access key if not logged in with an admin account
			$accessKey = $this->getParameter(Config::SECURITY_ADMIN_ACCESS_KEY);
			$userAccessKey = $request->query->get('k');
			if( $userAccessKey !== $accessKey ) {
				throw $this->createNotFoundException();
			}
		}
		
		return $this->render("@SoCore/interactive.html.twig", ['mainController' => 'sowapps--so-core--admin-auth-main']);
	}
	
	#[Route("/admin-auth/login", name: "so_core_admin_auth_login")]
	public function adminAuthLogin(Request $request, SecurityService $securityService): Response {
		// Could never redirect, so_core_admin_auth_any should take the request
		throw new LogicException('Admin login route should be handled by so_core_admin_auth_any');
	}
	
}

