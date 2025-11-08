<?php

namespace Sowapps\SoCore\Security;

use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Service\AbstractUserService;
use Sowapps\SoCore\Service\MailingService;
use Symfony\Component\HttpFoundation\Request;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier {
	
	private readonly VerifyEmailHelperInterface $verifyEmailHelper;
	
	public function __construct(VerifyEmailHelperInterface $helper, private readonly MailingService $mailingService, private readonly AbstractUserService $userService) {
		$this->verifyEmailHelper = $helper;
	}
	
	public function sendEmailConfirmation(AbstractUser $user): void {
		$signatureComponents = $this->verifyEmailHelper->generateSignature('so_core_admin_verify_email', $user->getId(), $user->getEmail(), ['id' => $user->getId()]);
		
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
