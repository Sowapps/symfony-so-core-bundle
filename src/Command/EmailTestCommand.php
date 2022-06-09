<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigService;

class EmailTestCommand extends Command {
	
	private array $config;
	
	private TranslatorInterface $translator;
	
	private MailerInterface $mailer;
	
	private TwigService $twig;
	
	public function __construct(TranslatorInterface $translator, MailerInterface $mailer, TwigService $twig, array $configEmail) {
		$this->config = $configEmail;
		$this->translator = $translator;
		$this->mailer = $mailer;
		$this->twig = $twig;
		
		parent::__construct();
	}
	
	protected function configure() {
		$this
			->setDescription('Send a test email')
			->setHelp('This command allows you to test a email sending...')
			->addArgument('recipientEmail', InputArgument::OPTIONAL, 'Recipient email', $this->config['contact']['email']);
	}
	
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$recipientEmail = $input->getArgument('recipientEmail');
		
		$io = new SymfonyStyle($input, $output);
		
		$this->sendTestEmail($recipientEmail);
		
		$io->success(sprintf('Email queued to deliver to %s', $recipientEmail));
		
		return 0;
	}
	
	public function sendTestEmail($recipient) {
		$email = new Email();
		$email
			->subject(sprintf('%s - Email Test', $this->translator->trans('app.label', [], 'messages')))
			->from($this->getAddress($this->config['from']))
			->to($recipient ?? $this->getAddress($this->config['contact']))
			->html($this->twig->render('@SoCore/system/email/email.test.html.twig'));
		
		$this->mailer->send($email);
	}
	
	protected function getAddress(array $config) {
		return new Address($config['email'], $config['name']);
	}
	
}
