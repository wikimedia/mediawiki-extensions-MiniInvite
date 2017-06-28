<?php

class EmailNewArticle extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'EmailNewArticle' );
	}

	/**
	 * Show the special page
	 *
	 * @param mixed|null $par Parameter passed to the page or null
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'invite-share-article' )->text() );

		$page = $this->getRequest()->getVal( 'page', $par );

		$new_page = Title::makeTitle( NS_MAIN, $page );
		if ( !$new_page instanceof Title ) {
			$out->addWikiMsg( 'invite-invalid-page' );
			return;
		}

		$invite = SpecialPage::getTitleFor( 'InviteEmail' );

		$out->addModuleStyles( 'ext.miniInvite.emailNewArticle.css' );

		$out->addHTML(
			'<div class="email-new-article-message">'
				. $this->msg( 'invite-send-new-article-to-friends' )->text() .
			'</div>
			<input type="button" class="site-button" onclick="window.location=\'' .
				$invite->getFullURL( array( 'email_type' => 'view', 'page' => $page ) ) . '\'" value="' .
				$this->msg( 'invite-my-friends' )->text() . '" />
			<input type="button" class="site-button" onclick="window.location=\'' .
				$new_page->getFullURL() . '\'" value="' .
				$this->msg( 'invite-no-thanks' )->text() . '" />' . "\n"
		);
	}
}