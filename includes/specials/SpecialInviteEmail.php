<?php

class InviteEmail extends UnlistedSpecialPage {

	/**
	 * @var string $from Email address from which the invite emails are sent out;
	 *  value is calculated in execute() and is one of the defined globals, either
	 *  MiniInvite's own one or the one from MW core
	 */
	private $from;

	/**
	 * @var int $track Numeric tracking code thing, see UserEmailTrack#track_email
	 */
	private $track;

	/**
	 * @var string $email_type Type of the invitation email we're sending out;
	 *  either rate, edit or view
	 */
	private $email_type;

	/**
	 * @var string $page Page name without the namespace
	 */
	private $page;

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'InviteEmail' );
	}

	/**
	 * Show the special page
	 *
	 * @param mixed|null $par Parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgPasswordSender, $wgEmailFrom;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// Check blocks
		if ( $user->getBlock() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// Need to be logged in to use this special page
		$this->requireLogin( 'invite-email-anon-text', 'invite-not-logged-in' );

		// Add CSS
		$out->addModuleStyles( 'ext.miniInvite.css' );

		if ( $wgEmailFrom ) {
			$this->from = $wgEmailFrom;
		} else {
			$this->from = $wgPasswordSender;
		}

		if ( $request->wasPosted() && $_SESSION['alreadysubmitted'] == false ) {
			$_SESSION['alreadysubmitted'] = true;
			$message = $request->getVal( 'body' );
			$subject = $request->getVal( 'subject' );
			$addresses = explode( ',', $request->getVal( 'email_to' ) );
			$mailResult = '';

			foreach ( $addresses as $address ) {
				$to = trim( $address );
				if ( Sanitizer::validateEmail( $to ) ) {
					$mailResult = UserMailer::send(
						new MailAddress( $to ),
						new MailAddress( $this->from ),
						$subject,
						$message,
						[
							'replyTo' => new MailAddress( $this->from ),
							'contentType' => 'text/html; charset=UTF-8'
						]
					);
				}
			}

			if ( class_exists( 'UserEmailTrack' ) ) {
				$mail = new UserEmailTrack( $user );
				$mail->track_email(
					$request->getInt( 'track' ),
					count( $addresses ),
					$request->getVal( 'page_title' )
				);
			}

			$out->setPageTitle( $this->msg( 'invite-sent' )->text() );

			$html = '';

			if ( $user->isLoggedIn() ) {
				$html .= '<div class="invite-links">';
				$html .= $this->getLinkRenderer()->makeLink(
					$user->getUserPage(),
					$this->msg( 'invite-back-to-userpage' )->plain()
				);
				$html .= '</div>';
			}

			$html .= $this->msg( 'invite-sent-thanks' )->parse();

			$html .= '<p>
				<input type="button" class="invite-form-button" value="' .
					$this->msg( 'invite-more-friends' )->escaped() .
					'" onclick="window.location=\'' .
					htmlspecialchars( $this->getPageTitle()->getFullURL(), ENT_QUOTES ) . '\'" />
			</p>';

			$out->addHTML( $html );
		} else {
			$_SESSION['alreadysubmitted'] = false;
			$out->addHTML( $this->displayForm() );
		}
	}

	/**
	 * Figure out what kind of a message we should be sending, based on the
	 * given parameter.
	 *
	 * @param string $type rate, edit or view
	 * @return array Subject key contains the subject, body key contains the
	 *                e-mail body
	 */
	function getInviteEmailContent( $type ) {
		$user = $this->getUser();

		$title = Title::makeTitle( NS_USER, $user->getName() );
		$user_label = $user->getRealName();
		if ( !trim( $user_label ) ) {
			$user_label = $user->getName();
		}

		$email = [];

		switch ( $type ) {
			case 'rate':
				$this->track = 6;
				$rate_title = Title::makeTitle( NS_MAIN, $this->page );
				$email['subject'] = $this->msg(
					'invite-rate-subject',
					$user_label,
					$rate_title->getText()
				)->text();
				$email['body'] = $this->msg(
					'invite-rate-body',
					$user_label,
					$user_label,
					$title->getFullURL(),
					$rate_title->getText(),
					$rate_title->getFullURL()
				)->text();
				break;
			case 'edit':
				$this->track = 5;
				$rate_title = Title::makeTitle( NS_MAIN, $this->page );
				$email['subject'] = $this->msg(
					'invite-edit-subject',
					$user_label,
					$rate_title->getText()
				)->text();
				$email['body'] = $this->msg(
					'invite-edit-body',
					$user_label,
					$user_label,
					$title->getFullURL(),
					$rate_title->getText(),
					$rate_title->getFullURL()
				)->text();
				break;
			case 'view':
				$this->track = 4;
				$rate_title = Title::makeTitle( NS_MAIN, $this->page );
				$email['subject'] = $this->msg(
					'invite-view-subject',
					$user_label,
					$rate_title->getText()
				)->text();
				$email['body'] = $this->msg(
					'invite-view-body',
					$user_label,
					$user_label,
					$title->getFullURL(),
					$rate_title->getText(),
					$rate_title->getFullURL()
				)->text();
				break;
			default:
				$this->track = 3;
				$register = SpecialPage::getTitleFor( 'Userlogin', 'signup' );
				$user_title = Title::makeTitle( NS_USER, $user->getName() );
				$email['subject'] = $this->msg( 'invite-subject', $user_label )->parse();

				$email['body'] = $this->msg(
					'invite-body',
					$user_label,
					$user_label,
					$title->getFullURL(),
					$register->getFullURL( 'from=1&referral=' . urlencode( $user_title->getDBkey() ) )
				)->text();
				break;
		}
		return $email;
	}

	function displayForm() {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$out->setPageTitle( $this->msg( 'invite-your-friends' )->text() );

		$this->email_type = $request->getVal( 'email_type' );
		$this->page = $request->getVal( 'page' );

		$email = $this->getInviteEmailContent( $this->email_type );

		$html = '';
		/*
		$html .= "<div class=\"invite-links\">
				<a href=\"index.php?title=Special:InviteContacts\">Find Your Friends</a>
				- <span class=\"profile-on\"><a href=\"index.php?title=Special:InviteEmail\">Invite Your Friends</a></span>
			</div>";
		*/
		// $html .= "<div class=\"invite-links\"><a href=\"index.php?title=Special:InviteContacts\">< Back to Invite</a></div>";

		if ( $request->getVal( 'from' ) == 'register' ) {
			$html .= '<div class="invite-skip-link">';
			$html .= $this->getLinkRenderer()->makeLink(
				$user->getUserPage(),
				$this->msg( 'invite-skip-step' )->plain()
			);
			$html .= '</div>';
		}

		$html .= '<p class="invite-message">' . $this->msg( 'invite-message' )->parse() . '</p>
			<form name="email" action="" method="post">
				<input type="hidden" value="' . $this->track . '" name="track" />

				<div class="invite-form-enter-email">
					<p class="invite-email-title">' . $this->msg( 'invite-enter-emails' )->escaped() . '</p>
					<p class="invite-email-submessage">' . $this->msg( 'invite-comma-separated' )->escaped() . '</p>
					<p>
						<textarea name="email_to" id="email_to" rows="15" cols="42"></textarea>
					</p>
				</div>
				<div class="invite-email-content">
					<p class="invite-email-title">' . $this->msg( 'invite-customize-email' )->escaped() . '</p>
					<p class="email-field">' . $this->msg( 'invite-customize-subject' )->escaped() . '</p>
					<p class="email-field"><input type="text" name="subject" id="subject" value="' . htmlspecialchars( $email['subject'], ENT_QUOTES ) . '" /></p>
					<p class="email-field">' . $this->msg( 'invite-customize-body' )->escaped() . '</p>
					<p class="email-field">
						<textarea name="body" id="body" rows="15" cols="45" wrap="hard">'
							. $email['body'] .
						'</textarea>
					</p>
					<div class="email-buttons">
						<input type="button" class="site-button" onclick="document.email.submit()" value="' .
							$this->msg( 'invite-customize-send' )->escaped() . '" />
					</div>
				</div>
				<div class="visualClear"></div>
				<input type="hidden" value="' . htmlspecialchars( $this->page, ENT_QUOTES ) . '" name="page_title" />
			</form>';

		return $html;
	}
}
