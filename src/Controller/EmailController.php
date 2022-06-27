<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller;

use Sowapps\SoCore\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailController extends AbstractController {
	
	public function view(Request $request, EmailService $emailService): Response {
		$emailMessage = $emailService->getEmailMessage($request->get('messageId'), $request->get('messageKey'));
		$contents = $emailMessage->getBodyHtml() ?: $emailMessage->getBodyText();
		// Hide "show online" link
		$contents .= '<style>.online-hidden { display: none !important; }</style>';
		
		return new Response($contents);
	}
	
}
