<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Entity;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Superclass must be in Sowapps\SoCoreBundle\Entity namespace
 */
#[ORM\MappedSuperclass]
#[UniqueEntity(fields: ['email'], message: 'user.email.exists')]
class AbstractUser extends AbstractEntity implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface {
	
	const ROLE_USER = 'ROLE_USER';
	const ROLE_ADMIN = 'ROLE_ADMIN';
	const ROLE_DEVELOPER = 'ROLE_DEVELOPER';
	
	#[ORM\Column(type: 'integer')]
	#[ORM\Version]
	private int $version = 1;
	
	#[ORM\Column(type: 'string', length: 180, unique: true)]
	private ?string $email = null;
	
	#[ORM\Column(type: 'json')]
	private array $roles = [];
	
	#[ORM\Column(type: 'string')]
	private ?string $password = null;
	
	#[ORM\Column(type: 'string', length: 50)]
	#[Assert\Length(min: 3, max: 50)]
	private ?string $name = null;
	
	#[ORM\Column(type: 'datetime', nullable: true)]
	private ?DateTimeInterface $activationDate = null;
	
	#[ORM\Column(type: 'datetime', nullable: true)]
	private ?DateTimeInterface $activationExpireDate = null;
	
	#[ORM\Column(type: 'string', length: 32, nullable: true)]
	private ?string $activationKey = null;
	
	#[ORM\Column(type: 'datetime', nullable: true)]
	private ?DateTimeInterface $recoverRequestDate = null;
	
	#[ORM\Column(type: 'string', length: 32, nullable: true)]
	private ?string $recoveryKey = null;
	
	#[ORM\Column(type: 'boolean')]
	private bool $disabled = false;
	
	#[ORM\Column(type: 'string', length: 20)]
	private ?string $timezone = null;
	
	#[ORM\ManyToOne(targetEntity: Language::class)]
	#[ORM\JoinColumn(nullable: false)]
	private ?Language $language = null;
	
	/**
	 * A visual identifier that represents this user.
	 *
	 * @see UserInterface
	 */
	public function getUserIdentifier(): string {
		return (string) $this->email;
	}
	
	/**
	 * @see UserInterface
	 */
	public function eraseCredentials() {
		// If you store any temporary, sensitive data on the user, clear it here
		// $this->plainPassword = null;
	}
	
	/**
	 * @param UserInterface $user
	 * @return bool
	 */
	public function isEqualTo(UserInterface $user): bool {
		/** @var AbstractUser $user */
		return $user->isDisabled() === $this->isDisabled() && $user->getRoles() === $this->getRoles();
	}
	
	public function getGenderKey(): string {
		return 'unknown';
	}
	
	public function getLabel(): string {
		return $this->getName();
	}
	
	public function getName(): ?string {
		return $this->name;
	}
	
	public function setName(string $name): self {
		$this->name = $name;
		
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getEmail(): ?string {
		return $this->email;
	}
	
	/**
	 * @param string|null $email
	 */
	public function setEmail(?string $email): void {
		$this->email = $email;
	}
	
	public function getVersion(): ?int {
		return $this->version;
	}
	
	public function setVersion(int $version): self {
		$this->version = $version;
		
		return $this;
	}
	
	public function isActivated(): bool {
		return !!$this->activationDate;
	}
	
	public function getTimezone(): ?string {
		return $this->timezone;
	}
	
	public function setTimezone(string $timezone): self {
		$this->timezone = $timezone;
		
		return $this;
	}
	
	public function getActivationDate(): ?DateTimeInterface {
		return $this->activationDate;
	}
	
	public function setActivationDate(?DateTimeInterface $activationDate): self {
		$this->activationDate = $activationDate;
		
		return $this;
	}
	
	public function getActivationExpireDate(): ?DateTimeInterface {
		return $this->activationExpireDate;
	}
	
	public function setActivationExpireDate(?DateTimeInterface $activationExpireDate): self {
		$this->activationExpireDate = $activationExpireDate;
		
		return $this;
	}
	
	public function getActivationKey(): ?string {
		return $this->activationKey;
	}
	
	public function setActivationKey(?string $activationKey): self {
		$this->activationKey = $activationKey;
		
		return $this;
	}
	
	public function canRecoverByDate(): ?bool {
		return !$this->recoverRequestDate ||
			((new DateTime())->sub(DateInterval::createFromDateString('2 minutes')) > $this->recoverRequestDate);
	}
	
	public function getRecoverRequestDate(): ?DateTimeInterface {
		return $this->recoverRequestDate;
	}
	
	public function setRecoverRequestDate(?DateTimeInterface $recoverRequestDate): self {
		$this->recoverRequestDate = $recoverRequestDate;
		
		return $this;
	}
	
	public function getRecoveryKey(): ?string {
		return $this->recoveryKey;
	}
	
	public function setRecoveryKey(?string $recoveryKey): self {
		$this->recoveryKey = $recoveryKey;
		
		return $this;
	}
	
	public function isDisabled(): ?bool {
		return $this->disabled;
	}
	
	public function setDisabled(bool $disabled): self {
		$this->disabled = $disabled;
		
		return $this;
	}
	
	/**
	 * @see UserInterface
	 */
	public function getRoles(): array {
		$roles = $this->roles;
		// guarantee every user at least has ROLE_USER
		$roles[] = 'ROLE_USER';
		
		return array_unique($roles);
	}
	
	public function setRoles(array $roles): self {
		$this->roles = $roles;
		
		return $this;
	}
	
	/**
	 * @see PasswordAuthenticatedUserInterface
	 */
	public function getPassword(): string {
		return $this->password;
	}
	
	public function setPassword(string $password): self {
		$this->password = $password;
		
		return $this;
	}
	
	public function getLanguage(): Language {
		return $this->language;
	}
	
	public function setLanguage(Language $language): self {
		$this->language = $language;
		
		return $this;
	}
	
}
