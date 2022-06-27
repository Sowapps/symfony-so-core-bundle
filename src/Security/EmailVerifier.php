<?php

namespace Sowapps\SoCore\Security;

use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Service\AbstractUserService;
use Sowapps\SoCore\Service\MailingService;
use Symfony\Component\HttpFoundation\Request;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier {
	
	private VerifyEmailHelperInterface $verifyEmailHelper;
	
	private MailingService $mailingService;
	
	private AbstractUserService $userService;
	
	public function __construct(VerifyEmailHelperInterface $helper, MailingService $mailingService, AbstractUserService $userService) {
		$this->verifyEmailHelper = $helper;
		$this->mailingService = $mailingService;
		$this->userService = $userService;
	}
	
	public function sendEmailConfirmation(AbstractUser $user): void {
		$signatureComponents = $this->verifyEmailHelper->generateSignature('admin_verify_email', $user->getId(), $user->getEmail(), ['id' => $user->getId()]);
		
		$context = [];
		$context['activateUrl'] = $signatureComponents->getSignedUrl();
		$context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
		$context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();
		
		$this->mailingService->sendActivationEmail($user, $context);
	}
	
	public function handleEmailConfirmation(Request $request, AbstractUser $user): void {
		$this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());
		
		$this->userService->activate($user);
		$this->userService->update($user);
	}
	
}
