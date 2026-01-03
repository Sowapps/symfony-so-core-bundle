<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller\Admin;

use Sowapps\SoCore\Core\Controller\AbstractAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminDashboardController extends AbstractAdminController {
	
	#[Route('/admin', name: 'so_core_admin_dashboard', methods: ['GET'])]
	public function dashboard(Request $request): Response {
		$this->addRequestToBreadcrumb($request);
		
		return $this->render('@SoCore/admin/page/dashboard.html.twig');
	}
	
}
