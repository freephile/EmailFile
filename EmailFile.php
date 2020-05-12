<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'EmailFile' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgExtensionMessagesFiles['EmailFile'] = __DIR__ . '/EmailFile.i18n.php';
	wfWarn(
		'Deprecated PHP entry point used for the EmailFile extension. ' .
		'Please use wfLoadExtension() instead, ' .
		'see https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the EmailFile extension requires MediaWiki 1.29+' );
}
