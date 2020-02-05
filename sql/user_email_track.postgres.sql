DROP SEQUENCE IF EXISTS user_email_track_ue_id_seq CASCADE;
CREATE SEQUENCE user_email_track_ue_id_seq;

CREATE TABLE user_email_track (
	ue_id INTEGER NOT NULL PRIMARY KEY DEFAULT nextval('user_email_track_ue_id_seq'),
	ue_actor INTEGER NOT NULL,
	ue_count SMALLINT default 0,
	ue_page_title TEXT default NULL,
	ue_type SMALLINT default 0,
	ue_date TIMESTAMPTZ default NULL
);

ALTER SEQUENCE user_email_track_ue_id_seq OWNED BY user_email_track.ue_id;

CREATE INDEX ue_actor ON user_email_track (ue_actor);
