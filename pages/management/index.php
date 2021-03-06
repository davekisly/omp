<?php

/**
 * @defgroup pages_settings
 */

/**
 * @file pages/settings/index.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_settings
 * @brief Handle requests for settings pages.
 *
 */


switch ($op) {
	//
	// Settings
	//
	case 'settings':
	case 'categories':
	case 'series':
		import('pages.management.SettingsHandler');
		define('HANDLER_CLASS', 'SettingsHandler');
		break;
}

?>
