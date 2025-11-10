<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Service;

use Sowapps\SoCore\DBAL\EnumEmailPurposeType;
use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Entity\EmailMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * All method in this class should be dummy and only send one or several emails
 * All you need should be passed as argument, no calculation, no verification
 */
class MailingService {
	
	/**
	 * MailingService constructor
	 *
	 * @param TranslatorInterface $translator
	 * @param EmailService $emailService
	 */
	public function __construct(protected TranslatorInterface $translator, protected EmailService $emailService)
    {
    }
	
	public function getBasicTrans(): array {
		return [
			'app_label' => $this->translator->trans('app.label'),
		];
	}
	
	public function sendActivationEmail(AbstractUser $user, array $data): EmailMessage {
		$data['user'] = $user;
		$emailMessage = $this->emailService->createFromTemplate(
			$this->translator->trans('registerEmail.subject', $this->getBasicTrans(), 'emails'),
			EnumEmailPurposeType::USER_REGISTRATION,
			$user,
			'@SoCore/admin/email/user-register.html.twig',
			$data
		);
		if( $emailMessage ) {
			$this->emailService->send($emailMessage);
		} // else subscription prevents us to send this email to the user
		
		return $emailMessage;
	}
	
	public function sendRecoveryEmail(AbstractUser $user): EmailMessage {
		$emailMessage = $this->emailService->createFromTemplate(
			$this->translator->trans('recoveryEmail.subject', $this->getBasicTrans(), 'emails'),
			EnumEmailPurposeType::USER_RECOVER,
			$user,
			'@SoCore/admin/email/user-recover.html.twig',
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
