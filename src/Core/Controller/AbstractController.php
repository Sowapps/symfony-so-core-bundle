<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use Sowapps\SoCore\Contracts\ContextInterface;
use Sowapps\SoCore\Core\Form\AppForm;
use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Exception\ForbiddenOperationException;
use Sowapps\SoCore\Exception\UserException;
use Sowapps\SoCore\Service\AbstractUserService;
use Sowapps\SoCore\Service\ControllerService;
use Sowapps\SoCore\Service\StringHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * Class AbstractController
 *
 * @package Sowapps\SoCore\Core\Controller
 * @method AbstractUser getUser()
 */
class AbstractController extends SymfonyAbstractController {
	
	const SESSION_MESSAGE = 'HOME_ERROR';
	
	protected KernelInterface $kernel;
	
	protected RequestStack $requestStack;
	
	protected LoggerInterface $logger;
	
	protected TranslatorInterface $translator;
	
	protected RouterInterface $router;
	
	protected ContextInterface $contextService;
	
	protected AbstractUserService $userService;
	
	protected StringHelper $stringHelper;
	
	protected ?string $domain = null;
	
	public function getSecurityToken(string $key, ?string &$newToken = null): ?string {
		$session = $this->getSession();
		$tokens = $session->get('securityTokens', []);
		$securityToken = $tokens[$key] ?? null;
		$newToken = $this->stringHelper->generateKey();
		$tokens[$key] = $newToken;
		$session->set('securityTokens', $tokens);
		
		return $securityToken;
	}
	
	public function translateErrors(array $errors): array {
		foreach( $errors as &$message ) {
			// Same as AppTwigExtension
			if( $message instanceof UserException ) {
				$report = $message->asArray();
				
			} else {
				$messageDomain = null;
				$params = [];
				if( is_array($message) ) {
					[$message, $params, $messageDomain] = array_pad($message, 3, null);
				}
				$report = ['message' => $message, 'parameters' => $params, 'domain' => $messageDomain ?? $this->domain];
			}
			$message = $this->translator->trans($report['message'], $report['parameters'] ?? [], $report['domain'] ?? null);
		}
		
		return $errors;
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
	 * @return bool
	 */
	public function isAuthenticated(): bool {
		return !!$this->getUser();
	}
	
	/**
	 * @param $title
	 * @param $message
	 * @param null $type
	 * @param array $parameters
	 * @return RedirectResponse
	 */
	public function showMessageOnHome(string $title, string $message, ?string $type = null, array $parameters = []): RedirectResponse {
		$this->getSession()->set(self::SESSION_MESSAGE, [
			'title'   => $this->translator->trans($title, $parameters),
			'message' => $this->translator->trans($message, $parameters),
			'type'    => $type,
		]);
		
		return $this->redirectToRoute('home');
	}
	
	/**
	 * @return Session
	 */
	public function getSession(): SessionInterface {
		return $this->requestStack->getSession();
	}
	
	public function addFormError(FormInterface $form, $messageTemplate, $parameters = [], ?string $domain = null, ?Exception $cause = null) {
		if( $messageTemplate instanceof UserException ) {
			$exception = $messageTemplate;
			$messageTemplate = $exception->getMessage();
			$parameters = $exception->getParameters();
			$domain ??= $exception->getDomain();
		}
		$domain ??= 'validators';
		$message = $this->translator->trans($messageTemplate, $parameters, $domain);
		$form->addError(new FormError($message, $messageTemplate, $parameters, null, $cause));
	}
	
	/**
	 * Save form successes in session
	 *
	 * @param array $messages The form or the list of success messages to save
	 * @param string $name Default is the given form name. Useful to show success in another form because entity is deleted
	 */
	public function saveSuccesses(array $messages, string $name) {
		$this->saveReports($messages, $name, 'success');
	}
	
	/**
	 * @param array $messages
	 * @param string $name
	 * @param string $type
	 */
	private function saveReports(array $messages, string $name, string $type) {
		$session = $this->getSession();
		$sessionMessages = $session->get('form', []);
		if( $messages ) {
			// Save only if there is something to save
			if( !isset($sessionMessages[$name]) ) {
				$sessionMessages[$name] = [];
			}
			$sessionMessages[$name][$type] = $messages;
			$session->set('form', $sessionMessages);
		}
	}
	
	protected function logException(Exception $exception) {
		if( !$this->kernel->isDebug() ) {
			$this->logger->error($exception->getMessage(), ['exception' => $exception]);
		} else {
			throw $exception;
		}
	}
	
	protected function render(string $view, array $parameters = [], ?Response $response = null): Response {
		$parameters['appLanguage'] = $this->contextService->getCurrentLanguage();
		
		return parent::render($view, $parameters, $response);
	}
	
	protected function createForm(string $type, $data = null, array $options = []): AppForm {
		return $this->consumeSavedForm(new AppForm(parent::createForm($type, $data, $options), $this->domain));
	}
	
	/**
	 * Consume form successes from session, load and remove it
	 *
	 * @param AppForm $form
	 * @return AppForm
	 */
	public function consumeSavedForm(AppForm $form): AppForm {
		$session = $this->getSession();
		$formReports = $session->get('form', []);
		if( !empty($formReports[$form->getName()]) ) {
			$sessionForm = $formReports[$form->getName()];
			// Load only if there is something to load
			if( !empty($sessionForm['success']) ) {
				$form->setSuccesses($sessionForm['success']);
			}
			if( !empty($sessionForm['error']) && is_array($sessionForm['error']) ) {
				foreach( $sessionForm['error'] as $textError ) {
					[$message, $template, $params, $pluralization] = $textError;
					$form->addError(new FormError($message, $template, $params, $pluralization));
				}
			}
			unset($formReports[$form->getName()]);
			$session->set('form', $formReports);
		}
		
		return $form;
	}
	
	public function consumeSavedReports(string $name): array {
		$session = $this->getSession();
		$formReports = $session->get('form', []);
		if( !empty($formReports[$name]) ) {
			$reports = $formReports[$name];
			unset($formReports[$name]);
			$session->set('form', $formReports);
			
			return $reports;
		}
		
		return [];
	}
	
	public function createNamedForm(string $name, string $type, $data = null, array $options = []): AppForm {
		$builder = $this->container->get('form.factory')->createNamedBuilder($name, $type, $data, $options);
		
		return $this->consumeSavedForm(new AppForm($builder->getForm(), $this->domain));
	}
	
	protected function createForbiddenOperationException(string $message = 'Forbidden Operation', ?Throwable $previous = null): ForbiddenOperationException {
		return new ForbiddenOperationException($message, [], null, $previous);
	}
	
	protected function redirectToRequest(Request $request, ?AppForm $form = null): RedirectResponse {
		if( $form ) {
			$this->saveForm($form);
		}
		
		return $this->redirect($request->getUri());
	}
	
	/**
	 * Save form messages in session
	 *
	 * @param AppForm $form The form or the list of success messages to save
	 */
	public function saveForm(AppForm $form) {
		$errors = $form->getErrors();
		if( $errors->count() ) {
			$textErrors = [];
			foreach( $errors as $error ) {
				$textErrors[] = [$error->getMessage(), $error->getMessageTemplate(), $error->getMessageParameters(), $error->getMessagePluralization()];
			}
			$this->saveReports($textErrors, $form->getName(), 'error');
		}
		$successes = $form->getSuccesses();
		if( $successes ) {
			$this->saveReports($successes, $form->getName(), 'success');
		}
	}
	
	#[Required]
	public function setKernel(KernelInterface $kernel): AbstractController {
		$this->kernel = $kernel;
		return $this;
	}
	
	#[Required]
	public function setRequestStack(RequestStack $requestStack): AbstractController {
		$this->requestStack = $requestStack;
		return $this;
	}
	
	#[Required]
	public function setLogger(LoggerInterface $logger): AbstractController {
		$this->logger = $logger;
		return $this;
	}
	
	#[Required]
	public function setTranslator(TranslatorInterface $translator): AbstractController {
		$this->translator = $translator;
		return $this;
	}
	
	#[Required]
	public function setRouter(RouterInterface $router): AbstractController {
		$this->router = $router;
		return $this;
	}
	
	#[Required]
	public function setContextService(ContextInterface $contextService): AbstractController {
		$this->contextService = $contextService;
		return $this;
	}
	
	#[Required]
	public function setUserService(AbstractUserService $userService): AbstractController {
		$this->userService = $userService;
		return $this;
	}
	
	#[Required]
	public function setStringHelper(StringHelper $stringHelper): AbstractController {
		$this->stringHelper = $stringHelper;
		return $this;
	}
}
