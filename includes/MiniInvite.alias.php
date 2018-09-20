<?php
/**
 * Aliases for MiniInvite's special pages
 *
 * @file
 * @ingroup Extensions
 */

$specialPageAliases = [];

/** English */
$specialPageAliases['en'] = [
	'EmailNewArticle' => [ 'EmailNewArticle' ],
	// A bunch of other social tools refer to Special:InviteContacts, so let's
	// just redirect it here for the time being, since the original InviteContacts
	// special page sucks due to various factors
	'InviteEmail' => [ 'InviteEmail', 'InviteContacts' ],
];
