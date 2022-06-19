<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCoreBundle\Controller\Admin;

use Sowapps\SoCoreBundle\Core\Controller\AbstractAdminController;
use Sowapps\SoCoreBundle\Core\Form\AppForm;
use Sowapps\SoCoreBundle\Entity\Language;
use Sowapps\SoCoreBundle\Form\LanguageForm;
use Sowapps\SoCoreBundle\Service\LanguageService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminLanguageController extends AbstractAdminController {
	
	protected array $success = [];
	
	public function list(Request $request, LanguageService $languageService): Response {
		$this->addRequestToBreadcrumb($request);
		
		$allowLanguageCreate = true;
		$allowLanguageUpdate = true;
		$allowLanguageEnable = true;
		$this->success = [];
		
		$createForm = $this->processLanguageCreate($request, $languageService);
		$updateForm = $this->processLanguageUpdate($request, $languageService);
		$this->processLanguageEnable($request, $languageService);
		$this->processLanguageDisable($request, $languageService);
		
		$languageQuery = $languageService->getLanguageRepository()
			->query()
			->orderBy('language.locale', 'ASC');
		
		return $this->render('@SoCore/admin/page/language-list.html.twig', [
			'allowLanguageCreate' => $allowLanguageCreate,
			'allowLanguageUpdate' => $allowLanguageUpdate,
			'allowLanguageEnable' => $allowLanguageEnable,
			'createForm'          => $createForm->createView(),
			'updateForm'          => $updateForm->createView(),
			'success'             => $this->success,
			'languages'           => $languageQuery->getQuery()->toIterable(),
		]);
	}
	
	/**
	 * @param Request $request
	 * @param LanguageService $languageService
	 * @param array $success
	 * @return AppForm
	 */
	public function processLanguageCreate(Request $request, LanguageService $languageService): AppForm {
		$form = $this->createNamedForm('language_create_form', LanguageForm::class);
		
		if( $form->isValidRequest($request) ) {
			/** @var Language $language */
			$language = $form->getData();
			$languageService->create($language);
			
			$this->success[] = ['page.admin_language_list.create.success', ['key' => $language->getKey()]];
			$form = $this->createForm(LanguageForm::class);
		}
		
		return $form;
	}
	
	/**
	 * @param Request $request
	 * @param LanguageService $languageService
	 * @param array $success
	 * @return AppForm
	 */
	public function processLanguageUpdate(Request $request, LanguageService $languageService): AppForm {
		$language = $request->get('id') ? $languageService->getLanguage($request->get('id')) : null;
		
		$form = $this->createNamedForm('language_update_form', LanguageForm::class, $language, ['require_id' => true]);
		
		if( $language && $form->isValidRequest($request) ) {
			/** @var Language $language */
			$language = $form->getData();
			$language->setEnabled(false);
			$languageService->update($language);
			
			$this->success[] = ['page.admin_language_list.update.success', ['key' => $language->getKey()]];
			$form = $this->createNamedForm('language_update_form', LanguageForm::class);
		}
		
		return $form;
	}
	
	public function processLanguageEnable(Request $request, LanguageService $languageService): bool {
		$languageId = $request->get('submitEnable');
		if( !$languageId ) {
			return false;
		}
		$language = $languageService->getLanguage($languageId);
		$language->setEnabled(true);
		$languageService->update($language);
		$this->success[] = ['page.admin_language_list.enable.success', ['key' => $language->getKey()]];
		
		return true;
	}
	
	public function processLanguageDisable(Request $request, LanguageService $languageService): bool {
		$languageId = $request->get('submitDisable');
		if( !$languageId ) {
			return false;
		}
		$language = $languageService->getLanguage($languageId);
		$language->setEnabled(false);
		$languageService->update($language);
		$this->success[] = ['page.admin_language_list.disable.success', ['key' => $language->getKey()]];
		
		return true;
	}
	
}
