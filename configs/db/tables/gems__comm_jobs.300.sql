
CREATE TABLE if not exists gems__comm_jobs (
        gcj_id_job bigint unsigned not null auto_increment,

        gcj_id_message bigint unsigned not null
                references gems__mail_templates (gmt_id_message),

        gcj_id_user_as bigint unsigned not null
                references gems__staff (gsf_id_user),

        gcj_active boolean not null default 1,

        -- O Use organization from address
        -- S Use site from address
        -- U Use gcj_id_user_as from address
        -- F Fixed gcj_from_fixed
        gcj_from_method varchar(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gcj_from_fixed varchar(254) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' null,

        -- M => multiple per respondent, one for each token
        -- O => One per respondent, mark all tokens as send
        -- A => Send only one token, do not mark
        gcj_process_method varchar(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,

        -- N => notmailed
        -- R => reminder
        gcj_filter_mode          VARCHAR(1) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gcj_filter_days_between  INT UNSIGNED NOT NULL DEFAULT 7,
        gcj_filter_max_reminders INT UNSIGNED NOT NULL DEFAULT 3,

        -- Optional filters
        gcj_id_organization bigint unsigned null references gems__organizations (gor_id_organization),
        gcj_id_track        int unsigned null references gems__tracks (gtr_id_track),
        gcj_id_survey       int unsigned null references gems__surveys (gsu_id_survey),

        gcj_changed timestamp not null default current_timestamp on update current_timestamp,
        gcj_changed_by bigint unsigned not null,
        gcj_created timestamp not null default '0000-00-00 00:00:00',
        gcj_created_by bigint unsigned not null,

        PRIMARY KEY (gcj_id_job)
   )
   ENGINE=InnoDB
   AUTO_INCREMENT = 800
   CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';