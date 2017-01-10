CREATE TABLE /*_*/user_email_track (
	ue_id int(11) NOT NULL PRIMARY KEY auto_increment,
	ue_user_id int(11) NOT NULL default 0,
	ue_user_name varchar(255) NOT NULL default '',
	ue_count int(5) default 0,
	ue_page_title varchar(255) default NULL,
	ue_type int(5) default 0,
	ue_date datetime default NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/ue_user_id ON /*_*/user_email_track (ue_user_id);
