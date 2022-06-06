<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Core\Controller;


use Sowapps\SoCoreBundle\Service\ControllerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AbstractAdminController
 *
 * @package Sowapps\SoCoreBundle\Core\Controller
 */
abstract class AbstractAdminController extends AbstractController {
	
	protected array $breadcrumb = [];
	
	public function __construct(ControllerService $controllerService) {
		parent::__construct($controllerService);
		
		$this->addRouteToBreadcrumb('admin_home');
		$this->domain = 'admin';
	}
	
	/**
	 * Add given route to breadcrumb
	 * Label is optional, else we translate the route name
	 * Link could be
	 *  - disabled using false
	 *  - auto-generated using true or an array of value (passed as values)
	 *  - Specified using string
	 *
	 * @param string $route
	 * @param string|null $label
	 * @param string|bool|array $link
	 */
	public function addRouteToBreadcrumb(string $route, string $label = null, $link = true) {
		if( !$link ) {
			$link = null;
			
		} elseif( !is_string($link) ) {
			// Could be true => generate with no args
			// Could be an array => generate using args
			$link = $this->router->generate($route, $link === true ? [] : $link);
		}
		$this->addBreadcrumb($label ?: $this->translator->trans(sprintf('page.%s.label', $route), [], $this->domain), $link);
	}
	
	public function addBreadcrumb($label, $link = null) {
		$this->breadcrumb[] = (object) ['label' => $label, 'link' => $link];
	}
	
	public function addRequestToBreadcrumb(Request $request, $label = null, $link = false) {
		$this->addRouteToBreadcrumb($request->get('_route'), $label, $link);
	}
	
	protected function render(string $view, array $parameters = [], Response $response = null): Response {
		$parameters['breadcrumb'] = $this->breadcrumb;
		
		return parent::render($view, $parameters, $response);
	}
	
}
