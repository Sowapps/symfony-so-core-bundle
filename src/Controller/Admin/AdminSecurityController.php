<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Sowapps\SoCoreBundle\Core\Controller\AbstractController;
use Sowapps\SoCoreBundle\Form\User\UserRegisterForm;
use Sowapps\SoCoreBundle\Security\EmailVerifier;
use Sowapps\SoCoreBundle\Service\AbstractUserService;
use Sowapps\SoCoreBundle\Service\ControllerService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class AdminSecurityController extends AbstractController {
	
	protected AbstractUserService $userService;
	
	protected array $configAdmin;
	
	private EmailVerifier $emailVerifier;
	
	public function __construct(ControllerService $controllerService, EmailVerifier $emailVerifier, AbstractUserService $userService, array $configAdmin) {
		parent::__construct($controllerService);
		$this->emailVerifier = $emailVerifier;
		$this->userService = $userService;
		$this->configAdmin = $configAdmin;
	}
	
	protected function render(string $view, array $parameters = [], Response $response = null): Response {
		$backgrounds = $this->configAdmin['auth']['background'] ?? null;
		$parameters['background'] = $backgrounds ? $backgrounds[date('Ymd') % count($backgrounds)] : null;
		
		return parent::render($view, $parameters, $response);
	}
	
	public function login(AuthenticationUtils $authenticationUtils): Response {
		// Get last login error and username
		$lastLoginError = $authenticationUtils->getLastAuthenticationError();
		$lastLoginEmail = $authenticationUtils->getLastUsername();
		
		return $this->render('@SoCore/admin/page/security-login.html.twig', [
			'login' => [
				'email' => $lastLoginEmail,
				'error' => $lastLoginError,
			],
		]);
	}
	
	public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response {
		$userClass = $this->userService->getUserClass();
		$user = new $userClass();
		$form = $this->createForm(UserRegisterForm::class, $user);
		if( $form->isValidRequest($request) ) {
			// encode the plain password
			$user->setPassword(
				$userPasswordHasher->hashPassword(
					$user,
					$form->get('plainPassword')->getData()
				)
			);
			
			$entityManager->persist($user);
			$entityManager->flush();
			
			// generate a signed url and email it to the user
			$this->emailVerifier->sendEmailConfirmation('admin_verify_email', $user,
				(new TemplatedEmail())
					->from(new Address('contact@sowapps.com', 'Sowapps'))
					->to($user->getEmail())
					->subject($this->translator->trans('email.activateAccount.subject', [], 'admin'))
					->htmlTemplate('@SoCore/admin/email/register_confirmation.html.twig')
			);
			
			return $this->redirectToRoute('admin_login');
		}
		
		return $this->render('@SoCore/admin/page/security-register.html.twig', [
			'form' => $form->createView(),
		]);
	}
	
	public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response {
		$id = $request->get('id');
		
		if( null === $id ) {
			return $this->redirectToRoute('admin_register');
		}
		
		$userRepository = $this->userService->getUserRepository();
		$user = $userRepository->find($id);
		
		if( null === $user ) {
			return $this->redirectToRoute('admin_register');
		}
		
		// validate email confirmation link, sets User::isVerified=true and persists
		try {
			$this->emailVerifier->handleEmailConfirmation($request, $user);
		} catch( VerifyEmailExceptionInterface $exception ) {
			$this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
			
			return $this->redirectToRoute('admin_register');
		}
		
		// @TODO Change the redirect on success and handle or remove the flash message in your templates
		$this->addFlash('success', 'Your email address has been verified.');
		
		return $this->redirectToRoute('admin_home');
	}
	
}
