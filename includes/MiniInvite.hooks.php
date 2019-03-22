<?php

class MiniInviteHooks {

	// Sets the default value of $wgEmailFrom since that cannot be done in
	// extension.json, obviously, because JSON is not PHP
	public static function registerExtension() {
		global $wgEmailFrom, $wgPasswordSender;
		// The email address where invite emails are sent out from
		$wgEmailFrom = $wgPasswordSender;
	}

	/**
	 * PageContentSaveComplete hook handler
	 *
	 * @param WikiPage $wikiPage
	 * @param $user
	 * @param $content
	 * @param $summary
	 * @param $isMinor
	 * @param $isWatch
	 * @param $section
	 * @param $flags
	 * @param $revision
	 * @param $status
	 * @param $baseRevId
	 *
	 * @return bool
	 */
	public static function inviteFriendToEdit( WikiPage $wikiPage, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId ) {
		if ( !( $flags & EDIT_NEW ) ) {
			// Increment edits for this page by one (for this user's session)
			$edits_views = ( $_SESSION['edits_views'] ?? [ $wikiPage->getID() => 0 ] );
			$page_edits_views = $edits_views[$wikiPage->getID()];
			$edits_views[$wikiPage->getID()] = ( $page_edits_views + 1 );

			$_SESSION['edits_views'] = $edits_views;
		}
		return true;
	}

	/**
	 * If the user just created a new page in the NS_BLOG namespace (defined by the
	 * BlogPage extension) and $wgSendNewArticleToFriends is set to true, this
	 * function sets the $_SESSION['new_opinion'] flag to the name of the new Blog:
	 * page.
	 *
	 * inviteRedirect() below then redirects the user to Special:EmailNewArticle/<name of the new Blog: page>,
	 * which allows the user to advertise their new page to their friends via email.
	 */
	public static function createOpinionCheck( $wikiPage, User $user, $content, $summary, $isMinor, $isWatch, $section, $flags, Revision $revision ) {
		global $wgSendNewArticleToFriends;

		if ( $wgSendNewArticleToFriends ) {
			$title = $wikiPage->getTitle();
			if ( defined( 'NS_BLOG' ) && $title->inNamespace( NS_BLOG ) ) {
				$_SESSION['new_opinion'] = $title->getPrefixedText();
			}
		}
	}

	public static function inviteRedirect( OutputPage &$out, &$text ) {
		global $wgSendNewArticleToFriends;
		if ( $wgSendNewArticleToFriends ) {
			if ( isset( $_SESSION['new_opinion'] ) ) {
				$invite = SpecialPage::getTitleFor( 'EmailNewArticle' );
				$out->redirect( $invite->getFullURL( [ 'page' => $_SESSION['new_opinion'] ] ) );
				unset( $_SESSION['new_opinion'] );
			}
		}
	}

	public static function displayInviteLinks( OutputPage &$out, &$text ) {
		// We need a WikiPage in order to get the page ID
		if ( !$out->canUseWikiPage() ) {
			return true;
		}

		if ( !isset( $_SESSION['edits_views'] ) ) {
			// To avoid "undefined <whatever variable/offset/etc.>" bullshit
			return true;
		}

		$t = $out->getTitle();
		$user = $out->getUser();
		$wikiPage = $out->getWikiPage();
		$s = ''; // the stuff that should be shown to the end-user

		if (
			!$out->isArticle() || $t->isMainPage() || $t->isTalkPage() ||
			$t->inNamespaces( NS_SPECIAL, NS_MEDIAWIKI ) ||
			$t->equals( Title::makeTitleSafe( NS_USER, $t->getText() ) )
		) {
			return true;
		}

		$edits_views = $_SESSION['edits_views'];
		// page ID is not set when creating a new page (obviously), so using $wikiPage->getID()
		// directly as-is can result in an E_NOTICE about undefined offsets on the
		// $page_edits_views variable definition line below
		$pageId = ( $wikiPage->getID() !== null ) ? $wikiPage->getID() : 0;
		$page_edits_views = $edits_views[$pageId] ?? 0;

		$invite_title = SpecialPage::getTitleFor( 'InviteEmail' );

		if ( $page_edits_views == 1 && $user->isLoggedIn() ) {
			$s .= '<span id="invite_to_edit" class="edit">';
			$s .= Linker::link(
				$invite_title,
				wfMessage( 'invite-friend-to-edit' )->escaped(),
				[],
				[ 'email_type' => 'edit', 'page' => $t->getText() ]
			);
			$s .= '</span>';
			$edits_views[$wikiPage->getID()] = $page_edits_views + 1;
			$_SESSION['edits_views'] = $edits_views;
		}

		// This was originally commented out, but I have no idea why...
		// Oh, maybe it conflicts with wfInviteRedirect()? Not sure, @todo CHECKME
		if ( isset( $_SESSION['new_opinion'] ) && $_SESSION['new_opinion'] == 1 ) {
			$s .= '<span id="invite_to_read" class="edit">';
			$s .= Linker::link(
				$invite_title,
				wfMessage( 'invite-friend-to-read' )->escaped(),
				[],
				[ 'email_type' => 'view', 'page' => $t->getText() ]
			);
			$s .= '</span>';
			$_SESSION['new_opinion'] = 0;
		}

		if ( !empty( $s ) ) {
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
		/*
		if ( !in_array( $dbType, [ 'mysql', 'sqlite' ] ) ) {
			$filename = "user_email_track.{$dbType}.sql";
		}
		*/

		$updater->addExtensionTable( 'user_email_track', "{$dir}/{$filename}" );
	}
}
