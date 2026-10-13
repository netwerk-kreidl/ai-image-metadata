#
# Table structure for table 'sys_file_metadata'
#
CREATE TABLE sys_file_metadata (
	copyright varchar(255) DEFAULT '' NOT NULL
);

#
# Table structure for table 'tx_aiimagemetadata_settings'
#
CREATE TABLE tx_aiimagemetadata_settings (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	business_name varchar(255) DEFAULT '' NOT NULL,
	business_type varchar(255) DEFAULT '' NOT NULL,
	location varchar(255) DEFAULT '' NOT NULL,
	target_group varchar(255) DEFAULT '' NOT NULL,
	tone varchar(32) DEFAULT 'neutral' NOT NULL,
	output_language varchar(8) DEFAULT 'de' NOT NULL,
	alt_max int(11) DEFAULT '125' NOT NULL,
	title_max int(11) DEFAULT '60' NOT NULL,
	keywords text,
	forbidden_words text,
	examples text,
	extra_instructions text,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
