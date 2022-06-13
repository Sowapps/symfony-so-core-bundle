<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Service;

use Sowapps\SoCoreBundle\DBAL\EnumEmailPurposeType;
use Sowapps\SoCoreBundle\Entity\AbstractUser;
use Sowapps\SoCoreBundle\Entity\EmailMessage;

/**
 * All method in this class should be dummy and only send one or several emails
 * All you need should be passed as argument, no calculation, no verification
 */
class MailingService {
	
	protected EmailService $emailService;
	
	/**
	 * MailingService constructor
	 *
	 * @param EmailService $emailService
	 */
	public function __construct(EmailService $emailService) {
		// Should never use another service, absolutely no business service !
		$this->emailService = $emailService;
	}
	
	public function sendRecoveryEmail(AbstractUser $user): EmailMessage {
		$emailMessage = $this->emailService->createFromTemplate(
			'recoveryEmail.subject',
			EnumEmailPurposeType::USER_RECOVER,
			$user,
			'email/email.user-recover.html.twig',
			['user' => $user]
		);
		if( $emailMessage ) {
			$this->emailService->send($emailMessage);
		} // else subscription prevents us to send this email to the user
		
		return $emailMessage;
	}
	
	public function sendRegistrationEmail(AbstractUser $user): EmailMessage {
		$emailMessage = $this->emailService->createFromTemplate(
			'registrationEmail.subject',
			EnumEmailPurposeType::USER_REGISTRATION,
			$user,
			'email/email.user-registration.html.twig',
			['user' => $user]
		);
		if( $emailMessage ) {
			$this->emailService->send($emailMessage);
		} // else subscription prevents us to send this email to the user
		
		return $emailMessage;
	}
	
}
