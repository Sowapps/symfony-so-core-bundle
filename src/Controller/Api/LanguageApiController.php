<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller\Api;

use Sowapps\SoCore\Core\Controller\AbstractApiEntityController;
use Sowapps\SoCore\Core\FileExport\CsvExporter;
use Sowapps\SoCore\Entity\Language;
use Sowapps\SoCore\Model\FileImportDto;
use Sowapps\SoCore\Model\LanguageCreateDto;
use Sowapps\SoCore\Model\LanguageEnabledDto;
use Sowapps\SoCore\Model\LanguageUpdateDto;
use Sowapps\SoCore\Service\ImportService;
use Sowapps\SoCore\Service\LanguageService;
use Sowapps\SoCore\Service\SecurityService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LanguageApiController extends AbstractApiEntityController {
	private array $exportColumns = ['name', 'locale', 'primaryCode', 'regionCode', 'enabled'];
	
	public function __construct(LanguageService $languageService) {
		parent::__construct($languageService->getLanguageRepository());
	}
	
	#[Route("/api/language", methods: ['GET'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function list(Request $request): Response {
		return $this->processRequestListWithBasicPagination($request);
	}
	
	#[Route("/api/language", methods: ['POST'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function createOne(
		Request                                $request,
		#[MapRequestPayload] LanguageCreateDto $languageCreateDto
	): Response {
		return $this->processRequestEntityBasicCreate(new Language(), $languageCreateDto, $request);
	}
	
	#[Route("/api/language/{id}", methods: ['GET'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function getOne(Language $language, Request $request): Response {
		return $this
			->processRequestEntityGet($language, $request, true);
	}
	
	#[Route("/api/language/{id}", methods: ['PATCH'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function patchOne(Language $language, #[MapRequestPayload] LanguageUpdateDto $languageDto, Request $request): Response {
		return $this->processRequestEntityBasicPatch($language, $languageDto, $request);
	}
	
	#[Route("/api/language/{id}/enabled", methods: ['PATCH'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function patchOneEnabled(Language $language, #[MapRequestPayload] LanguageEnabledDto $languageDto, Request $request): Response {
		return $this->processRequestEntityBasicPatch($language, $languageDto, $request);
	}
	
	#[Route("/api/languages.csv", methods: ['GET'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function exportCsv(Request $request, CsvExporter $exporter): Response {
		return $this->processRequestExport($this->exportColumns, 'languages.csv', $request, $exporter);
	}
	
	#[Route("/api/languages.csv", methods: ['POST'], format: 'json')]
	#[IsGranted(SecurityService::ROLE_SYSTEM)]
	public function importCsv(#[MapRequestPayload] FileImportDto $importDto, ImportService $importService): Response {
		return $this->json($importService->importDtoCsv($importDto, $this->repository->getClassName(), $this->exportColumns, ['locale']), Response::HTTP_CREATED);
	}
}
