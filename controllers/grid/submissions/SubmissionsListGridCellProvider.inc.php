<?php

/**
 * @file controllers/grid/submissions/SubmissionsListGridCellProvider.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionsListGridCellProvider
 * @ingroup controllers_grid_submissions
 *
 * @brief Class for a cell provider that can retrieve labels from submissions
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

class SubmissionsListGridCellProvider extends DataObjectGridCellProvider {
	/**
	 * Constructor
	 */
	function SubmissionsListGridCellProvider() {
		parent::DataObjectGridCellProvider();
	}


	/**
	 * Gathers the state of a given cell given a $row/$column combination
	 * @param $row GridRow
	 * @param $column GridColumn
	 */
	function getCellState(&$row, &$column) {
		return '';
	}


	/**
	 * Get cell actions associated with this row/column combination
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array an array of LinkAction instances
	 */
	function getCellActions(&$request, &$row, &$column, $position = GRID_ACTION_POSITION_DEFAULT) {
		if ( $column->getId() == 'title' ) {
			$monograph =& $row->getData();
			$router =& $request->getRouter();
			$dispatcher =& $router->getDispatcher();

			$title = $monograph->getLocalizedTitle();
			if ( empty($title) ) $title = __('common.untitled');

			$pressId = $monograph->getPressId();
			$pressDao = DAORegistry::getDAO('PressDAO');
			$press = $pressDao->getPress($pressId);

			import('lib.pkp.classes.linkAction.request.RedirectAction');

			if (is_a($monograph, 'ReviewerSubmission')) {
				// Reviewer: Add a review link action.
				return array(new LinkAction(
					'details',
					new RedirectAction(
						$dispatcher->url(
							$request, ROUTE_PAGE,
							$press->getPath(),
							'reviewer', 'submission',
							$monograph->getId()
						)
					),
					$title
				));
				return array();
			} else {
				// Press assistant, Series Editor, or Press Manager:
				// Add a workflow link action.
				$stageIdWorkflowPathMap = array(
					WORKFLOW_STAGE_ID_SUBMISSION => WORKFLOW_STAGE_PATH_SUBMISSION,
					WORKFLOW_STAGE_ID_INTERNAL_REVIEW => WORKFLOW_STAGE_PATH_INTERNAL_REVIEW,
					WORKFLOW_STAGE_ID_EXTERNAL_REVIEW => WORKFLOW_STAGE_PATH_EXTERNAL_REVIEW,
					WORKFLOW_STAGE_ID_EDITING => WORKFLOW_STAGE_PATH_EDITING,
					WORKFLOW_STAGE_ID_PRODUCTION => WORKFLOW_STAGE_PATH_PRODUCTION
				);
				$stageId = $monograph->getStageId();
				if (!isset($stageIdWorkflowPathMap[$stageId])) {
					fatalError('Invalid stage ID!');
				}

				return array(new LinkAction(
					'details',
					new RedirectAction(
						$dispatcher->url(
							$request, ROUTE_PAGE,
							$press->getPath(),
							'workflow', $stageIdWorkflowPathMap[$stageId],
							$monograph->getId()
						)
					),
					$title
				));
			}

			// This should be unreachable code.
			assert(false);
		}
		return parent::getCellActions($request, $row, $column, $position);
	}

	//
	// Template methods from GridCellProvider
	//
	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn(&$row, $column) {
		$monograph =& $row->getData();
		$columnId = $column->getId();
		assert(is_a($monograph, 'DataObject') && !empty($columnId));

		$pressId = $monograph->getPressId();
		$pressDao = DAORegistry::getDAO('PressDAO');
		$press = $pressDao->getPress($pressId);

		switch ($columnId) {
			case 'title':
				$title = $monograph->getLocalizedTitle();
				if ( empty($title) ) $title = __('common.untitled');
				return array('label' => $title);
				break;
			case 'press':
				return array('label' => $press->getLocalizedName());
				break;
			case 'author':
				return array('label' => $monograph->getAuthorString(true));
				break;
			case 'dateAssigned':
				$dateAssigned = strftime(Config::getVar('general', 'date_format_short'), strtotime($monograph->getDateAssigned()));
				if ( empty($dateAssigned) ) $dateAssigned = '--';
				return array('label' => $dateAssigned);
				break;
			case 'dateDue':
				$dateDue = strftime(Config::getVar('general', 'date_format_short'), strtotime($monograph->getDateDue()));
				if ( empty($dateDue) ) $dateDue = '--';
				return array('label' => $dateDue);
				break;
			case 'status':
				$stageId = $monograph->getStageId();
				switch ($stageId) {
					case WORKFLOW_STAGE_ID_SUBMISSION: default:
						// FIXME: better way to determine if submission still incomplete?
						if ($monograph->getSubmissionProgress() > 0 && $monograph->getSubmissionProgress() <= 3) {
							$returner = array('label' => __('submissions.incomplete'));
						} else {
							$returner = array('label' => __('submission.status.submission'));
						}
						break;
					case WORKFLOW_STAGE_ID_INTERNAL_REVIEW:
						$returner = array('label' => __('submission.status.review'));
						break;
					case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
						$returner = array('label' => __('submission.status.review'));
						break;
					case WORKFLOW_STAGE_ID_EDITING:
						$returner = array('label' => __('submission.status.editorial'));
						break;
					case WORKFLOW_STAGE_ID_PRODUCTION:
						$returner = array('label' => __('submission.status.production'));
						break;
				}
				return $returner;
		}
	}
}

?>
