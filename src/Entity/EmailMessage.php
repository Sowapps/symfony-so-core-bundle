<?php

namespace Sowapps\SoCore\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Sowapps\SoCore\DBAL\EnumEmailPurposeType;
use Sowapps\SoCore\Repository\EmailMessageRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmailMessageRepository::class)]
class EmailMessage extends AbstractEntity {
	
	#[ORM\Column(type: 'datetime', nullable: true)]
	private ?DateTime $sendDate = null;
	
	#[ORM\Column(type: 'datetime', nullable: true)]
	private ?DateTime $openDate = null;
	
	#[ORM\Column(type: 'datetime', nullable: true)]
	private ?DateTime $onlineExpireDate = null;
	
	#[ORM\Column(type: 'string', length: 32)]
	private ?string $privateKey = null;
	
	#[ORM\Column(type: 'string', length: 100)]
	private ?string $fromUserEmail = null;
	
	#[ORM\Column(type: 'string', length: 50)]
	private ?string $fromUserName = null;
	
	#[ORM\Column(type: 'string', length: 100)]
	private ?string $toUserEmail = null;
	
	#[ORM\Column(type: 'string', length: 50, nullable: true)]
	private ?string $toUserName = null;
	
	#[ORM\ManyToOne(targetEntity: AbstractUser::class)]
	private ?AbstractUser $toUser = null;
	
	#[ORM\Column(type: 'string', length: 124)]
	private ?string $subject = null;
	
	#[ORM\Column(type: 'text', nullable: true)]
	private ?string $bodyHtml = null;
	
	#[ORM\Column(type: 'text', nullable: true)]
	private ?string $bodyText = null;
	
	#[ORM\ManyToOne(targetEntity: EmailSubscription::class, inversedBy: 'emailMessages')]
	#[ORM\JoinColumn(nullable: false)]
	private ?EmailSubscription $subscription = null;
	
	#[ORM\Column(type: 'enum_email_purpose')]
	#[Assert\Choice(choices: EnumEmailPurposeType::VALUES, message: 'emailMessage.purpose.invalid')]
	private ?string $purpose = null;
	
	#[ORM\Column(type: 'json', nullable: true)]
	private ?array $data = [];
	
	#[ORM\Column(type: 'string', length: 255, nullable: true)]
	private ?string $templateHtml = null;
	
	public function getSendDate(): ?DateTime {
		return $this->sendDate;
	}
	
	public function setSendDate(?DateTime $sendDate = null): self {
		$this->sendDate = $sendDate ?: new DateTime();
		
		return $this;
	}
	
	public function getOpenDate(): ?DateTime {
		return $this->openDate;
	}
	
	public function setOpenDate(?DateTime $openDate): self {
		$this->openDate = $openDate;
		
		return $this;
	}
	
	public function getOnlineExpireDate(): ?DateTime {
		return $this->onlineExpireDate;
	}
	
	public function setOnlineExpireDate(?DateTime $onlineExpireDate): self {
		$this->onlineExpireDate = $onlineExpireDate;
		
		return $this;
	}
	
	public function getPrivateKey(): ?string {
		return $this->privateKey;
	}
	
	public function setPrivateKey(string $privateKey): self {
		$this->privateKey = $privateKey;
		
		return $this;
	}
	
	public function getFromUserEmail(): ?string {
		return $this->fromUserEmail;
	}
	
	public function setFromUserEmail(?string $fromUserEmail): self {
		$this->fromUserEmail = $fromUserEmail;
		
		return $this;
	}
	
	public function getFromUserName(): ?string {
		return $this->fromUserName;
	}
	
	public function setFromUserName(string $fromUserName): self {
		$this->fromUserName = $fromUserName;
		
		return $this;
	}
	
	public function getToUserEmail(): ?string {
		return $this->toUserEmail;
	}
	
	public function setToUserEmail(string $toUserEmail): self {
		$this->toUserEmail = $toUserEmail;
		
		return $this;
	}
	
	public function getToUserName(): ?string {
		return $this->toUserName;
	}
	
	public function setToUserName(?string $toUserName): self {
		$this->toUserName = $toUserName;
		
		return $this;
	}
	
	public function getToUser(): ?AbstractUser {
		return $this->toUser;
	}
	
	public function setToUser(?AbstractUser $toUser): self {
		$this->toUser = $toUser;
		$this->toUserEmail = $toUser->getEmail();
		$this->toUserName = $toUser->getName();
		
		return $this;
	}
	
	public function getSubject(): ?string {
		return $this->subject;
	}
	
	public function setSubject(string $subject): self {
		$this->subject = $subject;
		
		return $this;
	}
	
	public function getBodyHtml(): ?string {
		return $this->bodyHtml;
	}
	
	public function setBodyHtml(?string $bodyHtml): self {
		$this->bodyHtml = $bodyHtml;
		
		return $this;
	}
	
	public function getBodyText(): ?string {
		return $this->bodyText;
	}
	
	public function setBodyText(?string $bodyText): self {
		$this->bodyText = $bodyText;
		
		return $this;
	}
	
	public function getSubscription(): ?EmailSubscription {
		return $this->subscription;
	}
	
	public function setSubscription(?EmailSubscription $subscription): self {
		$this->subscription = $subscription;
		
		return $this;
	}
	
	public function getPurpose(): ?string {
		return $this->purpose;
	}
	
	public function setPurpose($purpose): self {
		$this->purpose = $purpose;
		
		return $this;
	}
	
	public function getData(): ?array {
		return $this->data;
	}
	
	public function setData(?array $data): self {
		$this->data = $data;
		
		return $this;
	}
	
	public function getTemplateHtml(): ?string {
		return $this->templateHtml;
	}
	
	public function setTemplateHtml(?string $templateHtml): self {
		$this->templateHtml = $templateHtml;
		
		return $this;
	}
	
}
