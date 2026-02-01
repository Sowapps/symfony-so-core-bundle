<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller\Api;

use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Sowapps\SoCore\Core\Controller\AbstractApiController;
use Sowapps\SoCore\Core\Logger\Parser\LogParser;
use Sowapps\SoCore\Service\SecurityService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use ValueError;

/**
 * Routes for Log API
 */
class LogApiController extends AbstractApiController {
	
	#[Route("/api/log/default", methods: ['GET'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function getLogDetails(): Response {
		$handler = $this->getBestFileHandler();
		
		return $this->json([
			'logFile'         => $handler->getUrl(),
			'logLevelName'    => $handler->getLevel()->getName(),
			'logLevelValue'   => $handler->getLevel()->value,
			'errorLevelName'  => Level::Error->getName(),
			'errorLevelValue' => Level::Error->value,
			'levels'          => $this->getLogLevels(),
		]);
	}
	
	#[Route("/api/log/default/entry", methods: ['GET'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function getLogEntryList(LogParser $logParser): Response {
		$handler = $this->getBestFileHandler();
		[$logEntries, $otherCount] = $logParser->parseFileLogEntries($handler->getUrl(), Level::Error->value);
		
		return $this->json([
			'items'      => $logEntries,
			'otherCount' => $otherCount,
		]);
	}
	
	#[Route("/api/log/default/raw", methods: ['GET'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function getLogRawList(
		LogParser                $logParser,
		#[MapQueryParameter] int $max = 50
	): Response {
		$handler = $this->getBestFileHandler();
		$rows = $logParser->parseFileLastRows($handler->getUrl(), $max);
		$headers = [];
		$headers['Pagination-Ressources-Total'] = (int)array_key_last($rows);// Total number of resources
		$headers['Pagination-Set-Total'] = count($rows);// Number of rows in this set
		$headers['Pagination-Set-Offset'] = (int)array_key_first($rows);// Offset of this set
		
		return $this->json($rows, Response::HTTP_OK, $headers);
	}
	
	#[Route("/api/log/default/file", methods: ['GET'])]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function downloadLogFile(): Response {
		$handler = $this->getBestFileHandler();
		
		return $this->file($handler->getUrl());
	}
	
	/**
	 * Remove by line number, allows a set of ranges
	 */
	#[Route("/api/log/default/raw", methods: ['DELETE'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function deleteLogRawList(LogParser $logParser, Request $request): Response {
		$handler = $this->getBestFileHandler();
		$url = $handler->getUrl();
		$list = $this->formatLineList($request->toArray());
		$count = $logParser->removeRows($url, $list);
		$headers = [];
		$headers['Log-File'] = basename($url);// The file name
		$headers['Operation-Set-Total'] = $count;// Number of deleted rows
		
		return $this->json('ok', Response::HTTP_ACCEPTED, $headers);
	}
	
	/**
	 * Remove by all lines, even if not previously shown to the user
	 */
	#[Route("/api/log/default/raw/all", methods: ['DELETE'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function deleteLog(LogParser $logParser): Response {
		$handler = $this->getBestFileHandler();
		$url = $handler->getUrl();
		$logParser->removeFile($url);
		$headers = [];
		$headers['Log-File'] = basename($url);// The file name
		
		return $this->json('ok', Response::HTTP_ACCEPTED, $headers);
	}
	
	/**
	 * Remove by non-error lines
	 */
	#[Route("/api/log/default/raw/non-error", methods: ['DELETE'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function deleteLogNonErrors(LogParser $logParser): Response {
		$handler = $this->getBestFileHandler();
		$url = $handler->getUrl();
		$count = $logParser->removeNonErrorRows($url);
		$headers = [];
		$headers['Log-File'] = basename($url);// The file name
		$headers['Operation-Set-Total'] = $count;// Number of deleted rows
		
		return $this->json('ok', Response::HTTP_ACCEPTED, $headers);
	}
	
	/**
	 * Validate and format a list of lines and ranges to a list of lines
	 */
	public function formatLineList(?array $list): array {
		if( !$list ) {
			throw new ValueError('List cannot be empty');
		}
		$result = [];
		foreach( $list as $line ) {
			if( is_array($line) ) {
				$from = $line[0] ?? null;
				$to = $line[1] ?? null;
				if( !is_int($from) || !is_int($to) || $from > $to ) {
					throw new ValueError('Invalid line range');
				}
				for( $i = $from; $i <= $to; $i++ ) {
					$result[] = $i;
				}
			} else {
				$result[] = (int)$line;
			}
		}
		
		return $result;
	}
	
	public function getBestFileHandler(): ?StreamHandler {
		if( !$this->logger instanceof Logger ) {
			throw new InvalidArgumentException(sprintf('Logger must be an instance of %s', Logger::class));
		}
		foreach( $this->logger->getHandlers() as $handler ) {
			if( $handler instanceof StreamHandler ) {
				return $handler;
			}
		}
		
		return null;
	}
	
	public function getLogLevels(): array {
		return array_combine(Level::NAMES, Level::VALUES);
	}
}
