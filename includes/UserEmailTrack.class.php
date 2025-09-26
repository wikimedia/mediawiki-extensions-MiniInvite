<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\User\User;

/**
 * Class for tracking email invitations
 */
class UserEmailTrack {

	/**
	 * @var User
	 */
	private $user;

	/**
	 * Constructor
	 *
	 * @param MediaWiki\User\User $user
	 */
	public function __construct( User $user ) {
		$this->user = $user;
	}

	/**
	 * Insert an entry into the user_email_track DB table (if and only if the
	 * current user isn't an anon).
	 *
	 * @param int $type One of the following:
	 * 						1 = Invite - Email Contacts sucker
	 * 						2 = Invite - CVS Contacts importer
	 * 						3 = Invite - Manually Address enter
	 * 						4 = Invite to Read - Manually Address enter
	 * 						5 = Invite to Edit - Manually Address enter
	 * 						6 = Invite to Rate - Manually Address enter
	 * @param int $count
	 * @param string $page_title
	 */
	public function track_email( $type, $count, $page_title = '' ) {
		if ( $this->user->isRegistered() ) {
			$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
			$dbw->insert(
				'user_email_track',
				[
					'ue_actor' => $this->user->getActorId(),
					'ue_type' => $type,
					'ue_count' => $count,
					'ue_page_title' => $page_title,
					'ue_date' => $dbw->timestamp( date( 'Y-m-d H:i:s' ) ),
				],
				__METHOD__
			);
		}
	}
}
