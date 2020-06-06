<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['suppress_issue_types'] = array_merge( $cfg['suppress_issue_types'], [
	// NS_BLOG is a false-positive; we first check if it's define()d (by BlogPage) before trying
	// to use it but Phan somehow misses that defined() check and thinks we're unconditionally
	// trying to use something that hasn't been declared, while clearly that isn't the case
	'PhanUndeclaredConstant',
] );

return $cfg;
