<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;

class MiniInviteHooks {

	/**
	 * Sets the default value of $wgEmailFrom since that cannot be done in
	 * extension.json, obviously, because JSON is not PHP
	 */
	public static function registerExtension() {
		global $wgEmailFrom, $wgPasswordSender;

		// The email address where invite emails are sent out from
		$wgEmailFrom = $wgPasswordSender;
	}

	/**
	 * PageSaveComplete hook handler
	 *
	 * If the user just created a new page in the NS_BLOG namespace (defined by the
	 * BlogPage extension) and $wgSendNewArticleToFriends is set to true, this
	 * function sets the session-specific 'new_opinion' flag to the name of the new Blog:
	 * page.
	 *
	 * inviteRedirect() below then redirects the user to Special:EmailNewArticle/<name of the new Blog: page>,
	 * which allows the user to advertise their new page to their friends via email.
	 *
	 * @param WikiPage $wikiPage WikiPage modified
	 * @param UserIdentity $user User performing the modification
	 * @param string $summary Edit summary/comment
	 * @param int $flags Flags passed to WikiPage::doEditContent()
	 */
	public static function inviteFriendToEdit( WikiPage $wikiPage, $user, $summary, $flags ) {
		global $wgSendNewArticleToFriends;

		if ( !( $flags & EDIT_NEW ) ) {
			// Increment edits for this page by one (for this user's session)
			$session = RequestContext::getMain()->getRequest()->getSession();
			$edits_views = ( $session->get( 'edits_views' ) ?? [ $wikiPage->getID() => 0 ] );
			$page_edits_views = $edits_views[$wikiPage->getID()] ?? 0;
			$edits_views[$wikiPage->getID()] = ( $page_edits_views + 1 );

			$session->set( 'edits_views', $edits_views );
		}

		if ( $wgSendNewArticleToFriends ) {
			$title = $wikiPage->getTitle();
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			if ( defined( 'NS_BLOG' ) && $title->inNamespace( NS_BLOG ) ) {
				RequestContext::getMain()->getRequest()->getSession()->set( 'new_opinion', $title->getPrefixedText() );
			}
		}
	}

	/**
	 * @param MediaWiki\Output\OutputPage &$out
	 * @param string &$text
	 */
	public static function inviteRedirect( MediaWiki\Output\OutputPage &$out, &$text ) {
		global $wgSendNewArticleToFriends;

		if ( $wgSendNewArticleToFriends ) {
			$session = $out->getRequest()->getSession();
			$newOpinion = $session->get( 'new_opinion' );

			if ( $newOpinion !== null ) {
				$invite = SpecialPage::getTitleFor( 'EmailNewArticle' );
				$out->redirect( $invite->getFullURL( [ 'page' => $newOpinion ] ) );
				$session->set( 'new_opinion', null );
			}
		}
	}

	public static function displayInviteLinks( MediaWiki\Output\OutputPage &$out, &$text ) {
		$t = $out->getTitle();
		// We need a WikiPage in order to get the page ID
		if ( !$t->canExist() ) {
			return true;
		}

		$session = $out->getRequest()->getSession();
		if ( $session->get( 'edits_views' ) === null ) {
			// To avoid "undefined <whatever variable/offset/etc.>" bullshit
			return true;
		}
		$user = $out->getUser();
		$s = ''; // the stuff that should be shown to the end-user

		if (
			!$out->isArticle() || $t->isMainPage() || $t->isTalkPage() ||
			$t->inNamespaces( NS_SPECIAL, NS_MEDIAWIKI ) ||
			$t->equals( Title::makeTitleSafe( NS_USER, $t->getText() ) )
		) {
			return true;
		}

		$edits_views = $session->get( 'edits_views' );
		// page ID is not set when creating a new page (obviously), so using $t->getID()
		// directly as-is can result in an E_NOTICE about undefined offsets on the
		// $page_edits_views variable definition line below
		$pageId = $t->getArticleID();
		$page_edits_views = $edits_views[$pageId] ?? 0;

		$invite_title = SpecialPage::getTitleFor( 'InviteEmail' );
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

		if ( $page_edits_views == 1 && $user->isRegistered() ) {
			$s .= '<span id="invite_to_edit" class="edit">';
			$s .= $linkRenderer->makeKnownLink(
				$invite_title,
				wfMessage( 'invite-friend-to-edit' )->text(),
				[],
				[ 'email_type' => 'edit', 'page' => $t->getText() ]
			);
			$s .= '</span>';
			$edits_views[$t->getArticleID()] = $page_edits_views + 1;
			$session->set( 'edits_views', $edits_views );
		}

		// This was originally commented out, but I have no idea why...
		// Oh, maybe it conflicts with wfInviteRedirect()? Not sure, @todo CHECKME
		$newOpinion = $session->get( 'new_opinion' );
		if ( $newOpinion !== null && $newOpinion == 1 ) {
			$s .= '<span id="invite_to_read" class="edit">';
			$s .= $linkRenderer->makeKnownLink(
				$invite_title,
				wfMessage( 'invite-friend-to-read' )->text(),
				[],
				[ 'email_type' => 'view', 'page' => $t->getText() ]
			);
			$s .= '</span>';
			$session->set( 'new_opinion', 0 );
		}

		if ( $s ) {
			$out->addModules( 'ext.miniInvite.DisplayInviteLinks.js' );
			$out->addModuleStyles( 'ext.miniInvite.inviteLinks.css' );
			// Output the HTML. addHTML() places it at the very beginning of the
			// page, which is where we want it; appending to $text places it at the
			// very *bottom* of the page, which is what we do *not* want.
			$out->addHTML( $s );
			# $text .= $s;
		}

		return true;
	}

	/**
	 * Adds the new required database table into the database when the user
	 * runs /maintenance/update.php (the core database updater script).
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$dir = __DIR__ . '/../sql';

		$dbType = $updater->getDB()->getType();

		$filename = 'user_email_track.sql';
		// For non-MySQL/MariaDB/SQLite DBMSes, use the appropriately named file
		if ( !in_array( $dbType, [ 'mysql', 'sqlite' ] ) ) {
			$filename = "user_email_track.{$dbType}.sql";
		}

		$updater->addExtensionTable( 'user_email_track', "{$dir}/{$filename}" );
	}
}
