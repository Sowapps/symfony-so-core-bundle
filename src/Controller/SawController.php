<?php

namespace Sowapps\SoCore\Controller;

use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Using SAW, cannot migrate to SoCore bundle without separation
 */
class SawController extends AbstractController {
	
	#[Route("/api/saw/template/{templatePath}", name: "saw_template", requirements: ['templatePath' => '.+'], methods: ['GET'])]
	public function downloadTemplate(Request $request, string $templatePath): Response {
		$vars = $request->query->all('vars');
		if( empty($vars['mainController']) ) {
			throw new InvalidArgumentException('Query "vars" labelled mainController is required and must contains the stimulus identifier of the main controller');
		}
		
		return $this->render(sprintf('@SoCore/saw/%s.twig', $templatePath), $vars);
	}
	
}

