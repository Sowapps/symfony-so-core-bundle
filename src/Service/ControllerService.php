<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Service;

use Psr\Log\LoggerInterface;
use Sowapps\SoCoreBundle\Contracts\ContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ControllerService
 * Service to include controller service easily
 *
 * @package Sowapps\SoCoreBundle\Service
 */
class ControllerService {
	
	protected KernelInterface $kernel;
	
	protected RequestStack $requestStack;
	
	protected LoggerInterface $logger;
	
	protected TranslatorInterface $translator;
	
	protected RouterInterface $router;
	
	protected ContextInterface $contextService;
	
	protected AbstractUserService $userService;
	
	protected StringHelper $stringHelper;
	
	/**
	 * AbstractController constructor
	 *
	 * @param Kernel $kernel
	 * @param RequestStack $requestStack
	 * @param LoggerInterface $logger
	 * @param TranslatorInterface $translator
	 * @param ContextInterface $contextService
	 * @param AbstractUserService $userService
	 * @param StringHelper $stringHelper
	 */
	public function __construct(KernelInterface  $kernel, RequestStack $requestStack, LoggerInterface $logger, TranslatorInterface $translator, RouterInterface $router,
								ContextInterface $contextService, AbstractUserService $userService, StringHelper $stringHelper) {
		$this->kernel = $kernel;
		$this->requestStack = $requestStack;
		$this->logger = $logger;
		$this->translator = $translator;
		$this->router = $router;
		$this->contextService = $contextService;
		$this->userService = $userService;
		$this->stringHelper = $stringHelper;
	}
	
	/**
	 * @return Kernel
	 */
	public function getKernel(): Kernel {
		return $this->kernel;
	}
	
	/**
	 * @return RequestStack
	 */
	public function getRequestStack(): RequestStack {
		return $this->requestStack;
	}
	
	/**
	 * @return LoggerInterface
	 */
	public function getLogger(): LoggerInterface {
		return $this->logger;
	}
	
	/**
	 * @return TranslatorInterface
	 */
	public function getTranslator(): TranslatorInterface {
		return $this->translator;
	}
	
	/**
	 * @return RouterInterface
	 */
	public function getRouter(): RouterInterface {
		return $this->router;
	}
	
	/**
	 * @return ContextInterface
	 */
	public function getContextService(): ContextInterface {
		return $this->contextService;
	}
	
	/**
	 * @return AbstractUserService
	 */
	public function getUserService(): AbstractUserService {
		return $this->userService;
	}
	
	/**
	 * @return StringHelper
	 */
	public function getStringHelper(): StringHelper {
		return $this->stringHelper;
	}
	
}
