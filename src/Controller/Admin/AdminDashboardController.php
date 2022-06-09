<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Controller\Admin;

use Sowapps\SoCoreBundle\Core\Controller\AbstractAdminController;
use Symfony\Component\HttpFoundation\Response;

class AdminDashboardController extends AbstractAdminController {
	
	public function dashboard(): Response {
		
		return $this->render('@SoCore/admin/page/dashboard.html.twig');
	}
	
}
