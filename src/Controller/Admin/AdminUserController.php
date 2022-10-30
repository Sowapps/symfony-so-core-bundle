<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Controller\Admin;

use App\TestEntity;
use App\TestForm;
use Sowapps\SoCore\Core\Controller\AbstractAdminController;
use Sowapps\SoCore\Entity\AbstractUser;
use Sowapps\SoCore\Form\User\UserAdminForm;
use Sowapps\SoCore\Form\User\UserAdminPasswordForm;
use Sowapps\SoCore\Form\User\UserPictureForm;
use Sowapps\SoCore\Form\User\UserUpdateForm;
use Sowapps\SoCore\Service\AbstractUserService;
use Sowapps\SoCore\Service\MailingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminUserController extends AbstractAdminController {
	
	const FORM_ACTIVATION = 'admin.user.activation';
	
	public function dashboard(): Response {
		return $this->render('@SoCore/admin/page/dashboard.html.twig');
	}
	
	public function list(Request $request): Response {
		$this->addRequestToBreadcrumb($request);
		
		$userService = $this->userService;
		$allowUserActivate = $userService->isCurrentUserAdmin();
		$formSuccess = [];
		
		if( $request->get('submitActivate') ) {
			if( !$allowUserActivate ) {
				throw $this->createAccessDeniedException('Operation not permitted');
			}
			$user = $userService->getUser($request->get('submitActivate'));
			$userService->activate($user);
			$formSuccess = [['page.so_core_admin_user_list.activate.success', ['user' => $user->getLabel()]]];
		}
		
		$userQuery = $userService->getUserRepository()->query();
		
		//		$userQuery->addSelect('person', 'avatar', 'personalAddress')
		//			->leftJoin('user.person', 'person')
		//			->leftJoin('person.avatar', 'avatar')
		//			->leftJoin('person.personalAddress', 'personalAddress');
		
		return $this->render('@SoCore/admin/page/user-list.html.twig', [
			'allowUserActivate' => $allowUserActivate,
			'formSuccess'       => $formSuccess,
			'users'             => $userQuery->getQuery()->toIterable(),
		]);
	}
	
	public function mySettings(Request $request, MailingService $mailingService, AbstractUserService $userService): Response {
		$this->addRequestToBreadcrumb($request);
		
		return $this->edit($request, $mailingService, $userService, $this->getUser(), true);
	}
	
	public function edit(Request $request, MailingService $mailingService, AbstractUserService $userService, AbstractUser $user, bool $mySettings = false): Response {
		if( !$mySettings ) {
			$this->addRouteToBreadcrumb('so_core_admin_user_list');
			$this->addRouteToBreadcrumb('so_core_admin_user_edit', $user->getLabel(), false);
		}
		//		$this->addRouteToBreadcrumb('so_core_admin_user_edit', $user->getLabel(), ['id' => $user->getId()]);// Child
		//		$this->addRouteToBreadcrumb('so_core_admin_user_edit', $user->getLabel(), false);
		
		// Permissions
		$allowUserSelf = $mySettings;
		$allowUserAdmin = !$mySettings;
		$allowUserEnable = !$mySettings && $user->isDisabled();
		$allowUserDisable = !$mySettings && !$user->isDisabled();
		$allowUserActivationResend = !$mySettings && !$user->isActivated();
		$allowUserPasswordEdit = true;
		$allowUserAccountRecover = !$mySettings;
		
		$securityToken = $this->getSecurityToken('admin.user', $newSecurityToken) ?? 'none';
		
		// Roles before form
		$userRoles = $user->getRoles();
		
		$userAdminForm = $this->createForm($allowUserAdmin ? UserAdminForm::class : UserUpdateForm::class, ['user' => $user]);
		$userPasswordForm = $this->createForm(UserAdminPasswordForm::class, ['user' => $user]);
		//		$userPictureForm = $this->createForm(UserPictureForm::class, $user);
		//		$formSuccess = [];
		
		if( $userAdminForm->isValidRequest($request) ) {
			if( !$allowUserAdmin && !$allowUserSelf ) {
				throw $this->createForbiddenOperationException();
			}
			// Check roles & re-assign if needed
			if( $allowUserAdmin ) {
				$requestRoles = $user->getRoles();
				foreach( $userService->getRoles() as $role => [, $roleRestriction] ) {
					$requiredRole = $roleRestriction;
					// Can not change unavailable role or role current have not
					if( !$requiredRole || !$userService->isCurrentHavingRole($requiredRole) ) {
						continue;
					}
					$currentlyHavingRole = in_array($role, $userRoles);
					$requestHavingRole = in_array($role, $requestRoles);
					if( $requestHavingRole !== $currentlyHavingRole ) {
						// Role presence changed
						if( $currentlyHavingRole ) {
							// Removing role
							unset($userRoles[array_search($role, $userRoles)]);
						} else {
							// Adding role
							$userRoles[] = $role;
						}
					}
				}
				$user->setRoles($userRoles);
			}
			$userService->update($user);
			$userAdminForm->addSuccess('page.so_core_admin_user_edit.edit.success');
			
			return $this->redirectToRequest($request, $userAdminForm);
		}
		
		if( $userPasswordForm->isValidRequest($request) ) {
			if( !$allowUserPasswordEdit ) {
				throw $this->createForbiddenOperationException();
			}
			$user->setPassword($this->userService->encodePassword($userPasswordForm->get('plainPassword')->getData(), $user));
			$userService->update($user);
			$userPasswordForm->addSuccess('page.so_core_admin_user_edit.edit.success');
			
			return $this->redirectToRequest($request, $userPasswordForm);
		}
		
		$userPictureForm = $this->createForm(UserPictureForm::class, ['user' => $user]);
		if( $userPictureForm->isValidRequest($request) ) {
			if( !$allowUserAdmin && !$allowUserSelf ) {
				throw $this->createForbiddenOperationException();
			}
			$userService->update($user);
			$userPictureForm->addSuccess('page.so_core_admin_user_edit.picture.success');
			
			//			return $this->redirectToRequest($request, $userPasswordForm);
		}
		
		// Send account recover to user's email
		if( $request->request->get('submitAccountRecover') === $securityToken ) {
			if( !$allowUserAccountRecover ) {
				throw $this->createForbiddenOperationException();
			}
			$this->userService->requestRecover($user);
			$userService->update($user);
			$mailingService->sendRecoveryEmail($user);
			$userPasswordForm->addSuccess('page.so_core_admin_user_edit.accountRecover.success');
			
			return $this->redirectToRequest($request, $userPasswordForm);
		}
		
		// Save even if submit to disable and submit to disable fails
		if( $request->get('submitDisable') === $securityToken ) {
			if( !$allowUserDisable ) {
				throw $this->createForbiddenOperationException();
			}
			
			$user->setDisabled(true);
			$userService->update($user);
			$this->saveSuccesses([['page.so_core_admin_user_edit.disable.success']], self::FORM_ACTIVATION);
			
			return $this->redirectToRequest($request);
		}
		
		// Save even if submit to enable and submit to disable fails
		if( $request->get('submitEnable') === $securityToken ) {
			if( !$allowUserEnable ) {
				throw $this->createForbiddenOperationException();
			}
			
			$user->setDisabled(false);
			$userService->update($user);
			$this->saveSuccesses([['page.so_core_admin_user_edit.enable.success']], self::FORM_ACTIVATION);
			
			return $this->redirectToRequest($request);
		}
		
		// Resend user activation
		if( $request->request->get('submitActivationResend') === $securityToken ) {
			if( !$allowUserActivationResend ) {
				throw $this->createForbiddenOperationException();
			}
			$userService->startNewActivation($user);
			$userService->update($user);
			$mailingService->sendRegistrationEmail($user);
			$this->saveSuccesses([['page.so_core_admin_user_edit.activationResend.success']], self::FORM_ACTIVATION);
			
			return $this->redirectToRequest($request);
		}
		
		// Show password form
		$userPasswordForm->setViewOption('user/plainPassword/help', $this->translator->trans('page.so_core_admin_user_edit.password.password.help', [], 'admin'));
		
		return $this->render('@SoCore/admin/page/user-edit.html.twig', [
			'securityToken'              => $newSecurityToken,
			'user'                       => $user,
			'userAdminForm'              => $userAdminForm->createView(),
			'userPictureForm'            => $userPictureForm->createView(),
			'userPasswordForm'           => $userPasswordForm->createView(),
			'userActivationReports'      => $this->consumeSavedReports(self::FORM_ACTIVATION),
			'allowUserAdmin'             => $allowUserAdmin,
			'allowUserEnable'            => $allowUserEnable,
			'allowUserDisable'           => $allowUserDisable,
			'allowUserAccountActivation' => $allowUserAdmin,
			'allowUserActivationResend'  => $allowUserActivationResend,
			'allowUserPasswordEdit'      => $allowUserPasswordEdit,
			'allowUserAccountRecover'    => $allowUserAccountRecover,
		]);
	}
	
}
