<?php

/**
 * @file controllers/grid/settings/PreparedEmails/PreparedEmailsGridRow.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PreparedEmailsGridRow
 * @ingroup controllers_grid_settings_PreparedEmails
 *
 * @brief Handle PreparedEmails grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class PreparedEmailsGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function PreparedEmailsGridRow() {
		parent::GridRow();
	}

	//
	// Overridden template methods
	//
	/**
	 * Configure the grid row
	 * @param $request PKPRequest
	 */
	function initialize(&$request) {
		parent::initialize($request);
		// add Grid Row Actions
		$this->setTemplate('controllers/grid/gridRowWithActions.tpl');

		$press =& $request->getPress();

		$rowId = $this->getId();
		if (isset($rowId) && is_string($rowId)) {
			$pressId = $press->getId();
			$router =& $request->getRouter();

			// Row action to edit the email template
			import('controllers.api.preparedEmails.linkAction.EditEmailLinkAction');
			$this->addAction(new EditEmailLinkAction($request, $rowId));

			// Row action to disable/delete the email template
			$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO'); /* @var $emailTemplateDao EmailTemplateDAO */
			$emailTemplate =& $emailTemplateDao->getLocaleEmailTemplate($rowId, $pressId);
			if (isset($emailTemplate) && $emailTemplate->isCustomTemplate()) {
				$this->addAction(
					new LinkAction(
						'deleteEmail',
						new ConfirmationModal(
							__('manager.emails.confirmDelete'), null,
							$router->url($request, null, 'api.preparedEmails.PreparedEmailsApiHandler',
								'deleteCustomEmail', null, array('emailKey' => $rowId))
						),
						__('common.delete'),
						'disable'
					)
				);
			} else {
				// Row actions to enable/disable the email depending on its current state
				if($emailTemplate->getCanDisable()) {
					if($emailTemplate->getEnabled()) {
						$this->addAction(
							new LinkAction(
								'disableEmail',
								new ConfirmationModal(
									__('manager.emails.disable.message'), null,
									$router->url($request, null, 'api.preparedEmails.PreparedEmailsApiHandler',
										'disableEmail', null, array('emailKey' => $rowId))
								),
								__('manager.emails.disable'),
								'disable'
							)
						);
					} else {
						$this->addAction(
							new LinkAction(
								'enableEmail',
								new ConfirmationModal(
									__('manager.emails.enable.message'), null,
									$router->url($request, null, 'api.preparedEmails.PreparedEmailsApiHandler',
										'enableEmail', null, array('emailKey' => $rowId))
								),
								__('manager.emails.enable'),
								'enable'
							)
						);
					}
				}
			}

			// Row action to reset the email template to stock
			if (isset($emailTemplate) && !$emailTemplate->isCustomTemplate()) {
				$this->addAction(
					new LinkAction(
						'resetEmail',
						new ConfirmationModal(
							__('manager.emails.reset.message'), null,
							$router->url($request, null, 'api.preparedEmails.PreparedEmailsApiHandler',
								'resetEmail', null, array('emailKey' => $rowId))
						),
						__('manager.emails.reset'),
						'delete'
					)
				);
			}
		}
	}
}