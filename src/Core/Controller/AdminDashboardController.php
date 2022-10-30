<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Controller;

use Symfony\Component\HttpFoundation\Response;

class AdminDashboardController extends AbstractAdminController {
	
	public function dashboard(): Response {
		return $this->render('@SoCore/admin/page/dashboard.html.twig');
	}
	
}
