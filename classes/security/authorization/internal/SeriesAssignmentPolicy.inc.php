<?php
/**
 * @file classes/security/authorization/internal/SeriesAssignmentPolicy.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SeriesAssignmentPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Class to control access to press series.
 *
 * NB: This policy expects a previously authorized monograph in the
 * authorization context.
 */

import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class SeriesAssignmentPolicy extends AuthorizationPolicy {
	/** @var PKPRequest */
	var $_request;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function SeriesAssignmentPolicy(&$request) {
		parent::AuthorizationPolicy('user.authorization.seriesAssignment');
		$this->_request =& $request;
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		// Get the user
		$user =& $this->_request->getUser();
		if (!is_a($user, 'PKPUser')) return AUTHORIZATION_DENY;

		// Get the press
		$router =& $this->_request->getRouter();
		$press =& $router->getContext($this->_request);
		if (!is_a($press, 'Press')) return AUTHORIZATION_DENY;

		// Get the monograph
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);
		if (!is_a($monograph, 'Monograph')) return AUTHORIZATION_DENY;

		// Series editors can access all submissions in their series.
		// Even those they've not been explicitly assigned to.
		$seriesEditorsDao =& DAORegistry::getDAO('SeriesEditorsDAO');
		if ($seriesEditorsDao->editorExists($press->getId(), $monograph->getSeriesId(), $user->getId())) {
			return AUTHORIZATION_PERMIT;
		} else {
			return AUTHORIZATION_DENY;
		}
	}
}

?>
