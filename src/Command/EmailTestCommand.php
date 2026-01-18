<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Command;

use Sowapps\SoCore\Service\SoAppService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigService;

#[AsCommand(
	name: 'so:email:send-test',
	description: 'Send a test email',
	help: 'This command allows you to test a email sending...',
)]
class EmailTestCommand extends Command {
	
	public function __construct(
		private readonly MailerInterface $mailer,
		private readonly TwigService     $twig,
		private readonly SoAppService    $appService,
		#[Autowire('%so_core.email%')]
		private readonly array           $configEmail
	) {
		parent::__construct();
	}
	
	protected function configure(): void {
		$this
			->addArgument('recipientEmail', InputArgument::OPTIONAL, 'Recipient email', $this->configEmail['contact']['email']);
	}
	
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$recipientEmail = $input->getArgument('recipientEmail');
		
		$io = new SymfonyStyle($input, $output);
		
		$this->sendTestEmail($recipientEmail);
		
		$io->success(sprintf('Email queued to deliver to %s', $recipientEmail));
		
		return 0;
	}
	
	public function sendTestEmail($recipient): void {
		$email = new Email();
		$email
			->subject(sprintf('%s - Email Test', $this->appService->getAppName()))
			->from($this->getAddress($this->configEmail['from']))
			->to($recipient ?? $this->getAddress($this->configEmail['contact']))
			->html($this->twig->render('@SoCore/system/email/email.test.html.twig'));
		
		$this->mailer->send($email);
	}
	
	protected function getAddress(array $config): Address {
		return new Address($config['email'], $config['name']);
	}
	
}
