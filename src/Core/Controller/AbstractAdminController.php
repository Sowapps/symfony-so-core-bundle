<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Class AbstractAdminController
 *
 * @package Sowapps\SoCore\Core\Controller
 */
abstract class AbstractAdminController extends AbstractController {

	protected array $breadcrumb = [];
	
	public function __construct() {
		$this->domain = 'admin';
	}
	
	#[Required]
	public function initialize(): AbstractController {
		$this->addRouteToBreadcrumb('so_core_admin_home');
		
		return $this;
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
	public function addRouteToBreadcrumb(string $route, string $label = null, $link = true): void {
		if( !$link ) {
			$link = null;
			
		} elseif( !is_string($link) ) {
			// Could be true => generate with no args
			// Could be an array => generate using args
			$link = $this->router->generate($route, $link === true ? [] : $link);
		}
		$this->addBreadcrumb($label ?: $this->translator->trans(sprintf('page.%s.label', $route), [], $this->domain), $link);
	}
	
	public function addBreadcrumb($label, $link = null): static {
		$this->breadcrumb[] = (object) ['label' => $label, 'link' => $link];
		
		return $this;
	}
	
	public function addRequestToBreadcrumb(Request $request, $label = null, $link = false): static {
		$this->addRouteToBreadcrumb($request->attributes->get('_route'), $label, $link);
		
		return $this;
	}
	
	protected function render(string $view, array $parameters = [], Response $response = null): Response {
		$parameters['breadcrumb'] = $this->breadcrumb;
		
		return parent::render($view, $parameters, $response);
	}
	
}
