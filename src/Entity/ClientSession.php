<?php

namespace Sowapps\SoCore\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sowapps\SoCore\Repository\ClientSessionRepository;

#[ORM\Entity(repositoryClass: ClientSessionRepository::class)]
class ClientSession extends AbstractEntity {
	
	#[ORM\ManyToOne(inversedBy: 'clientSessions')]
	private ?AbstractUser $user = null;
	
	public function __construct() {
		parent::__construct();
	}
	
	public function getUser(): ?AbstractUser {
		return $this->user;
	}
	
	public function setUser(?AbstractUser $user): static {
		$this->user = $user;
		
		return $this;
	}
	
}
