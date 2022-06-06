<?php

namespace Sowapps\SoCoreBundle\Security;

use Sowapps\SoCoreBundle\Entity\AbstractUser;
use Sowapps\SoCoreBundle\Service\AbstractUserService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier {
	
	private VerifyEmailHelperInterface $verifyEmailHelper;
	
	private MailerInterface $mailer;
	
	private AbstractUserService $userService;
	
	public function __construct(VerifyEmailHelperInterface $helper, MailerInterface $mailer, AbstractUserService $userService) {
		$this->verifyEmailHelper = $helper;
		$this->mailer = $mailer;
		$this->userService = $userService;
	}
	
	public function sendEmailConfirmation(string $verifyEmailRouteName, UserInterface $user, TemplatedEmail $email): void {
		$signatureComponents = $this->verifyEmailHelper->generateSignature(
			$verifyEmailRouteName,
			$user->getId(),
			$user->getEmail(),
			['id' => $user->getId()]
		);
		
		$context = $email->getContext();
		$context['signedUrl'] = $signatureComponents->getSignedUrl();
		$context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
		$context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();
		
		$email->context($context);
		
		$this->mailer->send($email);
	}
	
	/**
	 * @throws VerifyEmailExceptionInterface
	 */
	public function handleEmailConfirmation(Request $request, AbstractUser $user): void {
		$this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());
		
		$this->userService->activate($user);
		$this->userService->update($user);
	}
	
}
