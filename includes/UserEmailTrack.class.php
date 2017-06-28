<?php
/**
 * Class for tracking email invitations
 */
class UserEmailTrack {

	/**
	 * Constructor
	 *
	 * @param int $user_id ID number of the user that we want to track stats for
	 * @param string $user_name User's name; if not supplied, then the user ID will be used to get the user name from DB.
	 */
	public function __construct( $user_id, $user_name ) {
		$this->user_id = $user_id;
		if ( !$user_name ) {
			$user = User::newFromId( $this->user_id );
			$user->loadFromDatabase();
			$user_name = $user->getName();
		}
		$this->user_name = $user_name;
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
		if ( $this->user_id > 0 ) {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->insert(
				'user_email_track',
				array(
					'ue_user_id' => $this->user_id,
					'ue_user_name' => $this->user_name,
					'ue_type' => $type,
					'ue_count' => $count,
					'ue_page_title' => $page_title,
					'ue_date' => date( 'Y-m-d H:i:s' ),
				),
				__METHOD__
			);
		}
	}
}