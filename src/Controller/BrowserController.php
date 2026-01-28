<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Respond to standard browser requests
 */
class BrowserController extends AbstractController {
	
	#[Route("/.well-known/appspecific/com.chrome.devtools.json", name: "so_core_chrome_devtools")]
	public function index(): Response {
		// Default only to prevent 404 error in logs
		return $this->json([]);
	}
	
}
