<?php

/**
 * @file classes/monograph/MonographKeyword.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MonographKeyword
 * @ingroup monograph
 * @see MonographKeywordEntryDAO
 *
 * @brief Basic class describing a monograph keyword
 */

import('lib.pkp.classes.controlledVocab.ControlledVocabEntry');

class MonographKeyword extends ControlledVocabEntry {
	//
	// Get/set methods
	//

	/**
	 * Get the keyword
	 * @return string
	 */
	function getKeyword() {
		return $this->getData('monographKeyword');
	}

	/**
	 * Set the keyword text
	 * @param keyword
	 */
	function setKeyword($keyword) {
		$this->setData('monographKeyword', $keyword);
	}
}
?>
