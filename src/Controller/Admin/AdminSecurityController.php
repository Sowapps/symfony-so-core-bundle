<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sowapps\SoCore\Core\Controller\AbstractController;
use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Form\User\UserRecoveryPasswordForm;
use Sowapps\SoCore\Form\User\UserRecoveryRequestForm;
use Sowapps\SoCore\Form\User\UserRegisterForm;
use Sowapps\SoCore\Security\EmailVerifier;
use Sowapps\SoCore\Service\AbstractUserService;
use Sowapps\SoCore\Service\ControllerService;
use Sowapps\SoCore\Service\LanguageService;
use Sowapps\SoCore\Service\MailingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class AdminSecurityController extends AbstractController {
	
	public function __construct(ControllerService $controllerService, private readonly EmailVerifier $emailVerifier, protected AbstractUserService $userService, protected array $configAdmin) {
		parent::__construct($controllerService);
	}
	
	protected function render(string $view, array $parameters = [], Response $response = null): Response {
		$backgrounds = $this->configAdmin['auth']['background'] ?? null;
		$parameters['background'] = $backgrounds ? $backgrounds[date('Ymd') % count($backgrounds)] : null;
		
		return parent::render($view, $parameters, $response);
	}
	
	public function logout(): never {
		// By-passed by security firewall
		throw new LogicException('Not implemented for now.');
	}
	
	public function login(AuthenticationUtils $authenticationUtils): Response {
		// Get last login error and username
		$lastLoginError = $authenticationUtils->getLastAuthenticationError();
		$lastLoginEmail = $authenticationUtils->getLastUsername();
		
		return $this->render('@SoCore/admin/page/security/signin-login.html.twig', [
			'login' => [
				'email' => $lastLoginEmail,
				'error' => $lastLoginError,
			],
		]);
	}
	
	public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, LanguageService $languageService): Response {
		$userClass = $this->userService->getUserClass();
		/** @var AbstractUser $user */
		$user = new $userClass();
		$form = $this->createForm(UserRegisterForm::class, ['user' => $user]);
		if( $form->isValidRequest($request) ) {
			// encode the plain password
			$user->setPassword($userPasswordHasher->hashPassword($user, $form->get('user')->get('plainPassword')->getData()));
			$user->setTimezone('Europe/Paris');
			$user->setRoles($user->getRoles());// Initialize with defaults
			$user->setLanguage($languageService->getDefaultLocaleLanguage());
			
			$this->userService->create($user);
			
			// generate a signed url and email it to the user
			$this->emailVerifier->sendEmailConfirmation($user);
			$this->addFlash('auth_success', $this->translator->trans('page.so_core_admin_register.success', [], 'admin'));
			
			return $this->redirectToRoute('admin_login');
		}
		
		return $this->render('@SoCore/admin/page/security/signin-register.html.twig', [
			'form' => $form->createView(),
		]);
	}
	
	public function verifyUserEmail(Request $request): Response {
		$id = $request->get('id');
		
		if( null === $id ) {
			return $this->redirectToRoute('admin_register');
		}
		
		$userRepository = $this->userService->getUserRepository();
		$user = $userRepository->find($id);
		
		if( !$user ) {
			return $this->redirectToRoute('admin_register');
		}
		
		// validate email confirmation link, sets User::isVerified=true and persists
		try {
			$this->emailVerifier->handleEmailConfirmation($request, $user);
		} catch( VerifyEmailExceptionInterface $exception ) {
			$this->addFlash('verify_email_error', $this->translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
			
			return $this->redirectToRoute('admin_register');
		}
		
		$this->addFlash('auth_success', $this->translator->trans('page.verifyUserEmail.success'));
		
		return $this->redirectToRoute('admin_login');
	}
	
	public function requestRecover(Request $request, MailingService $mailingService): Response {
		$recoveryRequestForm = $this->createForm(UserRecoveryRequestForm::class);
		
		if( $recoveryRequestForm->isValidRequest($request) ) {
			$input = $recoveryRequestForm->getData();
			$user = $this->userService->getUserByEmail($input['email']);
			if( !$user ) {
				$this->addFormError($recoveryRequestForm, 'user.email.notFound');
				//			} elseif( !$user->canRecoverByDate() ) {
				//				$this->addFormError($recoveryRequestForm, 'user.recovery.tooRecent');
			} else {
				$this->userService->requestRecover($user);
				$mailingService->sendRecoveryEmail($user);
				
				return $this->render('@SoCore/admin/page/security/recover-requested.html.twig', [
					'user' => $user,
					'form' => $recoveryRequestForm->createView(),
				]);
			}
		}
		
		return $this->render('@SoCore/admin/page/security/recover-request.html.twig', [
			'form' => $recoveryRequestForm->createView(),
		]);
	}
	
	public function recoverPassword(Request $request, int $id, string $recoveryKey): Response {
		$user = $this->userService->getUser($id);
		$error = null;
		if( $this->getUser() ) {
			// Already logged in
			$error = $this->translator->trans('page.recover_password.error.loggedIn');
			
		} elseif( !$this->userService->isRecoverable($user, $recoveryKey) ) {
			// Check user exists, is having right recovery key and recovery is not expired
			$error = $this->translator->trans('page.recover_password.error.invalid');
		}
		
		if( $error ) {
			return $this->render('@SoCore/admin/page/security/recover-error.html.twig', [
				'error' => $error,
			]);
		}
		
		$recoverForm = $this->createForm(UserRecoveryPasswordForm::class);
		if( $recoverForm->isValidRequest($request) ) {
			$user->setPassword($this->userService->encodePassword($recoverForm->get('user')->get('plainPassword')->getData(), $user));
			$user->setRecoverRequestDate(null);
			$user->setRecoveryKey(null);
			$this->userService->update($user);
			
			return $this->render('@SoCore/admin/page/security/recover-success.html.twig', [
				'user' => $user,
			]);
		}
		
		return $this->render('@SoCore/admin/page/security/recover-password.html.twig', [
			'user' => $user,
			'form' => $recoverForm->createView(),
		]);
	}
	
}
