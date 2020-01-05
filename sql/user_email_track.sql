CREATE TABLE /*_*/user_email_track (
	ue_id int(11) NOT NULL PRIMARY KEY auto_increment,
	ue_actor bigint unsigned NOT NULL,
	ue_count int(5) default 0,
	ue_page_title varchar(255) default NULL,
	ue_type int(5) default 0,
	ue_date datetime default NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/ue_actor ON /*_*/user_email_track (ue_actor);
