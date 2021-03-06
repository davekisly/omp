<?php

/**
 * @file controllers/listbuilder/settings/CategoriesListbuilderHandler.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CategoriesListbuilderHandler
 * @ingroup listbuilder
 *
 * @brief Class for assigning categories to series.
 */

import('controllers.listbuilder.settings.SetupListbuilderHandler');

class CategoriesListbuilderHandler extends SetupListbuilderHandler {
	/** @var The group ID for this listbuilder */
	var $seriesId;

	/**
	 * Constructor
	 */
	function CategoriesListbuilderHandler() {
		parent::SetupListbuilderHandler();
		$this->addRoleAssignment(
			ROLE_ID_PRESS_MANAGER,
			array('fetch', 'fetchRow', 'fetchOptions')
		);
	}

	/**
	 * Set the series ID
	 * @param $seriesId int
	 */
	function setSeriesId($seriesId) {
		$this->seriesId = $seriesId;
	}

	/**
	 * Get the series ID
	 * @return int
	 */
	function getSeriesId() {
		return $this->seriesId;
	}

	/**
	 * Load the list from an external source into the grid structure
	 * @param $request PKPRequest
	 */
	function loadData(&$request) {
		$press =& $this->getPress();
		$seriesId = $this->getSeriesId();

		$seriesDao =& DAORegistry::getDAO('SeriesDAO');
		$assignedCategories =& $seriesDao->getCategories($seriesId, $press->getId());
		return $assignedCategories;
	}

	/**
	 * Get possible items to populate autosuggest list with
	 */
	function getOptions() {
		$press =& $this->getPress();
		$seriesDao =& DAORegistry::getDAO('SeriesDAO');

		$unassignedCategories =& $seriesDao->getUnassignedCategories($this->getSeriesId(), $press->getId());

		$itemList = array(0 => array());
		while ($category =& $unassignedCategories->next()) {
			$itemList[0][$category->getId()] = $category->getLocalizedTitle();
			unset($category);
		}
		return $itemList;
	}

	/**
	 * Preserve the series ID for internal listbuilder requests.
	 * @see GridHandler::getRequestArgs
	 */
	function getRequestArgs() {
		$args = parent::getRequestArgs();
		$args['seriesId'] = $this->getSeriesId();
		return $args;
	}


	/**
	 * @see GridHandler::getRowDataElement
	 * Get the data element that corresponds to the current request
	 * Allow for a blank $rowId for when creating a not-yet-persisted row
	 */
	function getRowDataElement(&$request, $rowId) {
		// fallback on the parent if a rowId is found
		if ( !empty($rowId) ) {
			return parent::getRowDataElement($request, $rowId);
		}

		// Otherwise return from the $newRowId
		$newRowId = $this->getNewRowId($request);
		$categoryId = $newRowId['name'];
		$categoryDao =& DAORegistry::getDAO('CategoryDAO');
		$press =& $request->getPress();
		$category =& $categoryDao->getById($categoryId, $press->getId());
		return $category;
	}

	//
	// Overridden template methods
	//
	/**
	 * Configure the grid
	 * @param PKPRequest $request
	 */
	function initialize(&$request) {
		parent::initialize($request);
		AppLocale::requireComponents(array(LOCALE_COMPONENT_PKP_MANAGER));

		// Basic configuration
		$this->setTitle('grid.category.categories');
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_SELECT);
		$this->setSaveType(LISTBUILDER_SAVE_TYPE_EXTERNAL);
		$this->setSaveFieldName('categories');

		$this->setSeriesId($request->getUserVar('seriesId'));

		// Name column
		$nameColumn = new ListbuilderGridColumn($this, 'name', 'common.name');

		import('controllers.listbuilder.categories.CategoryListbuilderGridCellProvider');
		$nameColumn->setCellProvider(new CategoryListbuilderGridCellProvider());
		$this->addColumn($nameColumn);
	}
}

?>
