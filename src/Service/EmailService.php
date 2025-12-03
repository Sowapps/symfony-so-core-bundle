<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Service;

use DateInterval;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Entity\EmailMessage;
use Sowapps\SoCore\Entity\EmailSubscription;
use Sowapps\SoCore\Repository\EmailMessageRepository;
use Sowapps\SoCore\Repository\EmailSubscriptionRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment as TwigService;

class EmailService extends AbstractEntityService {
	
	/**
	 * @deprecated Use url
	 */
	const TOKEN_VIEW_ONLINE = '###EMAIL_ONLINE_VIEW_LINK###';
	
	/**
     * EmailService constructor
     *
     * @param MailerInterface $mailer
     * @param TwigService $twig
     * @param UrlGeneratorInterface $router
     * @param StringHelper $stringHelper
     * @param array $config
     */
    public function __construct(protected MailerInterface $mailer, protected TwigService $twig, protected UrlGeneratorInterface $router, protected StringHelper $stringHelper, protected array $config)
    {
    }
	
	/**
	 * @param $emailMessageId
	 * @param $messageKey
	 * @return EmailMessage
	 */
	public function getEmailMessage($emailMessageId, $messageKey): EmailMessage {
		/** @var EmailMessageRepository $emailMessageRepo */
		$emailMessageRepo = $this->entityManager->getRepository(EmailMessage::class);
		$emailMessage = $emailMessageRepo->find($emailMessageId);
		if( !$emailMessage ) {
			throw new NotFoundHttpException('email.notFound');
		}
		if( $emailMessage->getOnlineExpireDate() && $emailMessage->getOnlineExpireDate() < new DateTime('now') ) {
			throw new NotFoundHttpException('email.onlineView.expired');
		}
		if( $emailMessage->getPrivateKey() !== $messageKey ) {
			throw new NotFoundHttpException('email.wrongKey');
		}
		
		return $emailMessage;
	}
	
	/**
	 * @param string $subject
	 * @param string|EmailSubscription $purpose
	 * @param array|string|AbstractUser $recipient
	 * @param string $template
	 * @param array|null $data
	 * @return EmailMessage|null
	 */
	public function createFromTemplate(string $subject, EmailSubscription|string $purpose, AbstractUser|array|string $recipient, string $template, ?array $data): ?EmailMessage {
		if( $recipient instanceof AbstractUser ) {
			$email = $recipient->getEmail();
		} elseif( is_array($recipient) ) {
			$email = $recipient[1];
		} else {
			$email = $recipient;
		}
		if( $purpose instanceof EmailSubscription ) {
			$emailSubscription = $purpose;
			$purpose = $emailSubscription->getPurpose();
		} else {
			$emailSubscription = $this->getSubscription($email, $purpose);
		}
		if( $emailSubscription->isDisabled() ) {
			// Unsubscribed, we don't send the mail
			return null;
		}
		$emailMessage = new EmailMessage();
		$emailMessage->setFromUserName($this->config['from']['name']);
		$emailMessage->setFromUserEmail($this->config['from']['email']);
		if( $recipient instanceof AbstractUser ) {
			$emailMessage->setToUser($recipient);
		} elseif( is_array($recipient) ) {
			$emailMessage->setToUserName($recipient[0]);
			$emailMessage->setToUserEmail($recipient[1]);
		} else {
			$emailMessage->setToUserEmail($recipient);
		}
		$emailMessage->setPurpose($purpose);
		$emailMessage->setSubject($subject);
		$emailMessage->setOnlineExpireDate(new DateTime(sprintf('+%d hours', $this->config['online_view']['expire_hours'])));
		$emailMessage->setPrivateKey($this->stringHelper->generateKey());
		$emailMessage->setTemplateHtml($template);
		$emailMessage->setData($this->convertEntitiesToReferences($data));
		$emailMessage->setSubscription($emailSubscription);
		
		$data['email'] = null;
		$data['emailMessage'] = $emailMessage;
		$data['subscription'] = $emailSubscription;
		
		$emailMessage->setBodyHtml($this->twig->render($template, $data));
		
		$this->create($emailMessage);
		
		return $emailMessage;
	}
	
	/**
	 * @param string $userEmail
	 * @param string $purpose
	 * @param bool $createMissing
	 * @return EmailSubscription
	 * @throws NonUniqueResultException
	 */
	public function getSubscription(string $userEmail, string $purpose, bool $createMissing = true): EmailSubscription {
		$emailSubscriptionRepo = $this->getSubscriptionRepository();
		$emailSubscription = $emailSubscriptionRepo->findByEmail($userEmail, $purpose);
		
		if( !$emailSubscription && $createMissing ) {
			$emailSubscription = $this->createSubscription($userEmail, $purpose);
		}
		
		return $emailSubscription;
	}
	
	/**
	 * @return EmailSubscriptionRepository
	 */
	public function getSubscriptionRepository(): EmailSubscriptionRepository {
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this->entityManager->getRepository(EmailSubscription::class);
	}
	
	public function createSubscription($userEmail, $purpose): EmailSubscription {
		$emailSubscription = new EmailSubscription();
		$emailSubscription->setEmail($userEmail);
		$this->fillNewSubscription($emailSubscription, $purpose);
		$this->create($emailSubscription);
		
		return $emailSubscription;
	}
	
	public function fillNewSubscription($emailSubscription, $purpose) {
		$emailSubscription->setPrivateKey($this->stringHelper->generateKey());
		$emailSubscription->setPurpose($purpose);
	}
	
	public function send(EmailMessage $emailMessage) {
		$email = new TemplatedEmail();
		$email
			->subject($emailMessage->getSubject())
			->from(new Address($emailMessage->getFromUserEmail(), $emailMessage->getFromUserName()))
			->to(new Address($emailMessage->getToUserEmail(), $emailMessage->getToUserName() ?? ''));
		
		if( $emailMessage->getBodyText() ) {
			$email->text($this->formatContents($emailMessage->getBodyText(), $emailMessage));
		}
		if( $emailMessage->getBodyHtml() ) {
			if( $emailMessage->getTemplateHtml() ) {
				$email->htmlTemplate($emailMessage->getTemplateHtml());
			} else {
				$email->html($this->formatContents($emailMessage->getBodyHtml(), $emailMessage));
			}
		}
		
		$data = [];
		$data['email'] = null;
		$data['emailMessage'] = $emailMessage;
		$data['emailViewUrl'] = $this->getEmailViewUrl($emailMessage);
		$data['subscription'] = $emailMessage->getSubscription();
		
		if( $emailMessage->getData() ) {
			$data = array_merge($data, $this->convertReferencesToEntities($emailMessage->getData()));
		}
		
		$email->context($data);
		
		$this->mailer->send($email);
		$emailMessage->setSendDate();
		$this->update($emailMessage);
	}
	
	public function formatContents($contents, EmailMessage $emailMessage): string {
		return strtr($contents, [static::TOKEN_VIEW_ONLINE => $this->router->generate('email_message_view', [
			'messageId'  => $emailMessage->getId(),
			'messageKey' => $emailMessage->getPrivateKey(),
		], UrlGeneratorInterface::ABSOLUTE_URL)]);
	}
	
	public function getEmailViewUrl(EmailMessage $emailMessage): string {
		return $this->router->generate('email_message_view', [
			'messageId'  => $emailMessage->getId(),
			'messageKey' => $emailMessage->getPrivateKey(),
		], UrlGeneratorInterface::ABSOLUTE_URL);
	}
	
}
