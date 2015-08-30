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
 * @version 2.0
 * @author Aaron Wright <aaron.wright@gmail.com>
 * @author David Pean <david.pean@gmail.com>
 * @author Jack Phoenix <jack@countervandalism.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

// Extension credits that show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'MiniInvite',
	'version' => '2.0',
	'author' => array( 'Aaron Wright', 'David Pean', 'Jack Phoenix' ),
	'description' => '[[Special:InviteEmail|A special page to invite your friends to join the wiki]]',
	'url' => 'https://www.mediawiki.org/wiki/Extension:MiniInvite',
);

// Internationalization
$wgMessagesDirs['MiniInvite'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['MiniInviteAliases'] = __DIR__ . '/MiniInvite.alias.php';

// Set up the new special pages
$wgAutoloadClasses['InviteEmail'] = __DIR__ . '/SpecialInviteEmail.php';
$wgSpecialPages['InviteEmail'] = 'InviteEmail';
$wgAutoloadClasses['EmailNewArticle'] = __DIR__ . '/SpecialEmailNewArticle.php';
$wgSpecialPages['EmailNewArticle'] = 'EmailNewArticle';

// Load the hooked functions
require_once 'InviteFriendOnEdit.php';

// ResourceLoader support for MediaWiki 1.17+
$wgResourceModules['ext.miniInvite.css'] = array(
	'styles' => 'resources/css/invite.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'MiniInvite',
	'position' => 'top'
);

$wgResourceModules['ext.miniInvite.emailNewArticle.css'] = array(
	'styles' => 'resources/css/EmailNewArticle.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'MiniInvite',
	'position' => 'top'
);

$wgResourceModules['ext.miniInvite.DisplayInviteLinks.js'] = array(
	'scripts' => 'resources/js/DisplayInviteLinks.js',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'MiniInvite'
);

# Configuration settings
// The email address where invite emails are sent out from
$wgEmailFrom = $wgPasswordSender;

// When set to true, after a user has created a page in the blog category (see
// [[mw:Extension:BlogPage]]), they are redirected to Special:EmailNewArticle,
// prompting them to email their friends about the newly-created blog article.
$wgSendNewArticleToFriends = false;