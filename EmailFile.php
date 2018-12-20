<?php
	/**
	 * Extension: EmailFile
	 * Started: 04-21-2008
	 *
	 * This extensions requires two options in LocalSettings.php be set to TRUE:
	 * $wgEnableEmail = true;
	 * $wgEnableUserEmail = true;
	 * $wgEnableUploads  = true;
	 *
	 * Intellectual Reserve, Inc.(c) 2008
	 *
	 * @file
	 * @author Don B. Stringham (stringhamdb@ldschurch.org)
	 */

// Not a valid entry point, skip unless MEDIAWIKI is defined
	if (!defined('MEDIAWIKI')) {
		echo <<<EOT
To install the EmailFile extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/EmailFile/EmailFile.php" );
EOT;
		exit(1);
	}

	$dir = dirname(__FILE__) . '/';

	$wgAutoloadClasses['EmailFile'] = $dir . 'EmailFile_body.php';

	$wgExtensionMessagesFiles['EmailFile'] = $dir . 'EmailFile.i18n.php';

	$wgSpecialPages['EmailFile'] = 'EmailFile';
	$wgSpecialPageGroups['EmailFile'] = 'pagetools';

	$wgExtensionCredits['specialpage'][] = array(
		'name' => 'EmailFile',
		'author' => 'Don Stringham',
		'version' => '0.2.0',
		'url' => 'http://familysearch.org',
		'description' => 'Email a file for upload authorization.'
	);
