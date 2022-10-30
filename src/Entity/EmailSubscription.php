<?php

namespace Sowapps\SoCore\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sowapps\SoCore\Repository\EmailSubscriptionRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmailSubscriptionRepository::class)]
#[UniqueEntity(fields: ['email', 'purpose'], message: 'emailSubscription.exists')]
class EmailSubscription extends AbstractEntity {
	
	#[ORM\Column(type: 'string', length: 32, nullable: true)]
	private ?string $privateKey = null;
	
	#[ORM\Column(type: 'enum_email_purpose')]
	private ?string $purpose = null;
	
	#[ORM\Column(type: 'string', length: 100)]
	#[Assert\Email]
	private ?string $email = null;
	
	#[ORM\Column(type: 'boolean')]
	private bool $disabled = false;
	
	#[ORM\Column(type: 'datetime', nullable: true)]
	private ?DateTimeInterface $disabledDate = null;
	
	#[ORM\OneToOne(targetEntity: EmailMessage::class, cascade: ['persist', 'remove'])]
	private ?EmailMessage $disabledMessage = null;
	
	#[ORM\OneToMany(targetEntity: EmailMessage::class, mappedBy: 'subscription')]
	private Collection $emailMessages;
	
	public function __construct() {
		parent::__construct();
		
		$this->emailMessages = new ArrayCollection();
	}
	
	public function getPrivateKey(): ?string {
		return $this->privateKey;
	}
	
	public function setPrivateKey(?string $privateKey): self {
		$this->privateKey = $privateKey;
		
		return $this;
	}
	
	public function getPurpose(): ?string {
		return $this->purpose;
	}
	
	public function setPurpose($purpose): self {
		$this->purpose = $purpose;
		
		return $this;
	}
	
	public function getEmail(): ?string {
		return $this->email;
	}
	
	public function setEmail(string $email): self {
		$this->email = $email;
		
		return $this;
	}
	
	public function isDisabled(): ?bool {
		return $this->disabled;
	}
	
	public function setDisabled(bool $disabled): self {
		if( $this->disabled !== $disabled ) {
			$this->disabled = $disabled;
			$this->setDisabledDate($disabled ? new DateTime() : null);
		}
		
		return $this;
	}
	
	public function getDisabledDate(): ?DateTimeInterface {
		return $this->disabledDate;
	}
	
	public function setDisabledDate(?DateTimeInterface $disabledDate): self {
		$this->disabledDate = $disabledDate;
		
		return $this;
	}
	
	public function getDisabledMessage(): ?EmailMessage {
		return $this->disabledMessage;
	}
	
	public function setDisabledMessage(?EmailMessage $disabledMessage): self {
		$this->disabledMessage = $disabledMessage;
		
		return $this;
	}
	
	/**
	 * @return Collection|EmailMessage[]
	 */
	public function getEmailMessages(): Collection {
		return $this->emailMessages;
	}
	
	public function addEmailMessage(EmailMessage $emailMessage): self {
		if( !$this->emailMessages->contains($emailMessage) ) {
			$this->emailMessages[] = $emailMessage;
			$emailMessage->setSubscription($this);
		}
		
		return $this;
	}
	
	public function removeEmailMessage(EmailMessage $emailMessage): self {
		if( $this->emailMessages->contains($emailMessage) ) {
			$this->emailMessages->removeElement($emailMessage);
			// set the owning side to null (unless already changed)
			if( $emailMessage->getSubscription() === $this ) {
				$emailMessage->setSubscription(null);
			}
		}
		
		return $this;
	}
	
}
