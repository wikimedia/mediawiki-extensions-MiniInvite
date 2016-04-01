<?php
/**
 * Mini-Invite -- allows wiki users to invite their friends to the wiki via
 * different special pages.
 * Based on the InviteContacts extension, without the contact importing
 * functionality because GetMyContacts sucks and so does OpenInviter.
 *
 * Originally hacked together on 15 August 2012, then revived again on 28
 * September 2014.
 *
 * @file
 * @ingroup Extensions
 * @author Aaron Wright <aaron.wright@gmail.com>
 * @author David Pean <david.pean@gmail.com>
 * @author Jack Phoenix <jack@countervandalism.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'MiniInvite' );
	$wgMessagesDirs['MiniInvite'] =  __DIR__ . '/i18n';
	wfWarn(
		'Deprecated PHP entry point used for MiniInvite extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the MiniInvite extension requires MediaWiki 1.25+' );
}