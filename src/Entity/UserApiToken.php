<?php

namespace Sowapps\SoCore\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Sowapps\SoCore\Constraints as FileAssert;
use Sowapps\SoCore\Core\ProcessOption\FormatOptions;
use Sowapps\SoCore\Repository\FileRepository;
use Sowapps\SoCore\Repository\UserApiTokenRepository;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Token to authenticate the user through API
 */
#[ORM\Entity(repositoryClass: UserApiTokenRepository::class)]
class UserApiToken extends AbstractEntity {
	#[ORM\ManyToOne(targetEntity: AbstractUser::class)]
	protected ?AbstractUser $user = null;
	
	#[ORM\Column(type: 'string', length: 60)]
	protected ?string $ip = null;
	
	#[ORM\Column(type: "string", length: 255, unique: true)]
	private ?string $tokenHash = null;
	
	#[ORM\Column(type: "datetime", nullable: true)]
	private ?DateTime $expireDate = null;
	
	#[ORM\Column(nullable: true)]
	private ?DateTime $lastUseDate = null;
	
	public function getUser(): ?AbstractUser {
		return $this->user;
	}
	
	public function setUser(AbstractUser $user): static {
		$this->user = $user;
		
		return $this;
	}
	
	public function getIp(): ?string {
		return $this->ip ?? null;
	}
	
	public function setIp(string $ip): static {
		$this->ip = $ip;
		
		return $this;
	}
	
	public function getTokenHash(): ?string {
		return $this->tokenHash;
	}
	
	public function setTokenHash(?string $tokenHash): static {
		$this->tokenHash = $tokenHash;
		return $this;
	}
	
	public function getExpireDate(): ?DateTime {
		return $this->expireDate;
	}
	
	public function setExpireDate(?DateTime $expireDate): static {
		$this->expireDate = $expireDate;
		
		return $this;
	}
	
	public function getLastUseDate(): ?DateTime {
		return $this->lastUseDate;
	}
	
	public function setLastUseDate(?DateTime $lastUseDate): static {
		$this->lastUseDate = $lastUseDate;
		return $this;
	}
	
}
