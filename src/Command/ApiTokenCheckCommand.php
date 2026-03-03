<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Command;

use Sowapps\SoCore\Service\ApiTokenService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
	name: 'so:api-token:check',
	description: 'Send a test email',
	help: 'This command allows you to test a email sending...',
)]
class ApiTokenCheckCommand extends Command {
	const ARG_TOKEN = 'token';
	
	public function __construct(
		private readonly ApiTokenService $apiTokenService,
	) {
		parent::__construct();
	}
	
	protected function configure(): void {
		$this
			->addArgument(self::ARG_TOKEN, InputArgument::REQUIRED, 'Token string');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$apiToken = $input->getArgument(self::ARG_TOKEN);
		
		$io = new SymfonyStyle($input, $output);
		
		$token = $this->apiTokenService->getToken($apiToken);
		
		if( $token ) {
			$io->writeln(sprintf("Token #%s found", $token->getId()));
			if( $token->getExpireDate() ) {
				$io->writeln(sprintf("Expires at %s", $token->getExpireDate()->format('c')));
			} else {
				$io->writeln('Is never expiring');
			}
			$io->writeln('Is restricted to IP address "%s"', $token->getIp());
			$io->writeln('Is related to user "%s"', $token->getUser()->getEntityLabel());
		} else {
			$io->writeln('Token not found');
		}
		
		return 0;
	}
	
}
