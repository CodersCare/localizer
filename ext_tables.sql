#
# Table structure for table 'tx_localizer_settings'
#
CREATE TABLE tx_localizer_settings
(
    uid                             int(11)                   NOT NULL auto_increment,
    pid                             int(11)       DEFAULT '0' NOT NULL,
    tstamp                          int(11)       DEFAULT '0' NOT NULL,
    crdate                          int(11)       DEFAULT '0' NOT NULL,
    cruser_id                       int(11)       DEFAULT '0' NOT NULL,
    sorting                         int(10)       DEFAULT '0' NOT NULL,
    deleted                         tinyint(4)    DEFAULT '0' NOT NULL,
    hidden                          tinyint(4)    DEFAULT '0' NOT NULL,
    type                            varchar(255)  DEFAULT '0' NOT NULL,
    title                           varchar(255)  DEFAULT ''  NOT NULL,
    description                     text,
    url                             varchar(255)  DEFAULT ''  NOT NULL,
    out_folder                      varchar(255)  DEFAULT ''  NOT NULL,
    in_folder                       varchar(255)  DEFAULT ''  NOT NULL,
    workflow                        varchar(255)  DEFAULT ''  NOT NULL,
    deadline                        tinyint(4)    DEFAULT '0' NOT NULL,
    projectkey                      varchar(255)  DEFAULT ''  NOT NULL,
    username                        varchar(255)  DEFAULT ''  NOT NULL,
    password                        varchar(255)  DEFAULT ''  NOT NULL,
    project_settings                text,
    last_error                      text,
    l10n_cfg                        int(11)       DEFAULT '0' NOT NULL,
    sortexports                     tinyint(4)    DEFAULT '1' NOT NULL,
    plainxmlexports                 tinyint(4)    DEFAULT '0' NOT NULL,
    uid_local                       int(11)       DEFAULT '0' NOT NULL,
    uid_foreign                     int(11)       DEFAULT '0' NOT NULL,
    tablenames                      varchar(30)   DEFAULT ''  NOT NULL,
    allow_adding_to_export          int(11)       DEFAULT '0' NOT NULL,
    automatic_export_pages          text,
    collect_pages_marked_for_export int(11)       DEFAULT '0' NOT NULL,
    automatic_export_minimum_age    int(11)       DEFAULT '0' NOT NULL,
    source_locale                   int(11)       DEFAULT '0' NOT NULL,
    target_locale                   int(11)       DEFAULT '0' NOT NULL,
    source_language                 int(11)       DEFAULT '0' NOT NULL,
    target_languages                varchar(30)   DEFAULT ''  NOT NULL,
    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY titlet (title),
    KEY uid_local (uid_local),
    KEY uid_foreign (uid_foreign),
    KEY title (title)
);

CREATE TABLE tx_localizer_settings_l10n_cfg_mm
(
    uid_local   int(11)     DEFAULT '0' NOT NULL,
    uid_foreign int(11)     DEFAULT '0' NOT NULL,
    tablenames  varchar(60) DEFAULT ''  NOT NULL,
    sorting     int(11)     DEFAULT '0' NOT NULL,
    KEY uid_local (uid_local),
    KEY uid_foreign (uid_foreign)
);

CREATE TABLE tx_localizer_language_mm
(
    uid         int(11)                 NOT NULL auto_increment,
    pid         int(11)     DEFAULT '0' NOT NULL,
    uid_local   int(11)     DEFAULT '0' NOT NULL,
    uid_foreign int(11)     DEFAULT '0' NOT NULL,
    tablenames  varchar(60) DEFAULT ''  NOT NULL,
    source      varchar(60) DEFAULT ''  NOT NULL,
    ident       varchar(30) DEFAULT ''  NOT NULL,
    sorting     int(11)     DEFAULT '0' NOT NULL,
    PRIMARY KEY (uid),
    KEY uid_local (uid_local),
    KEY uid_foreign (uid_foreign),
    KEY ident (source, ident)
);

CREATE TABLE tx_localizer_settings_l10n_exportdata_mm
(
    uid             int(11)                   NOT NULL auto_increment,
    pid             int(11)       DEFAULT '0' NOT NULL,
    tstamp          int(11)       DEFAULT '0' NOT NULL,
    crdate          int(11)       DEFAULT '0' NOT NULL,
    cruser_id       int(11)       DEFAULT '0' NOT NULL,
    sorting         INT(10)       DEFAULT '0' NOT NULL,
    deleted         tinyint(4)    DEFAULT '0' NOT NULL,
    hidden          tinyint(4)    DEFAULT '0' NOT NULL,
    description     varchar(255)  DEFAULT ''  NOT NULL,
    deadline        int(11)       DEFAULT '0' NOT NULL,
    source_locale   int(11)       DEFAULT '0' NOT NULL,
    target_locale   int(11)       DEFAULT '0' NOT NULL,
    all_locale      tinyint(4)    DEFAULT '0' NOT NULL,
    localizer_path  varchar(4096) DEFAULT ''  NOT NULL,
    filename        varchar(4096) DEFAULT ''  NOT NULL,
    status          int(11)       DEFAULT '0' NOT NULL,
    previous_status int(11)       DEFAULT '0' NOT NULL,
    action          int(11)       DEFAULT '0' NOT NULL,
    uid_local       int(11)       DEFAULT '0' NOT NULL,
    uid_export      int(11)       DEFAULT '0' NOT NULL,
    uid_foreign     int(11)       DEFAULT '0' NOT NULL,
    tablenames      varchar(30)   DEFAULT ''  NOT NULL,
    processid       varchar(32)   DEFAULT ''  NOT NULL,
    configuration   text,
    last_error      text,
    response        text,
    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY processid (processid),
    KEY uid_local (uid_local),
    KEY uid_export (uid_export),
    KEY uid_foreign (uid_foreign),
    KEY localizer_status (status),
    KEY localizer_action (action),
    KEY last_error (last_error(1))
);

CREATE TABLE tx_localizer_cart
(
    uid             int(11)                   NOT NULL auto_increment,
    pid             int(11)       DEFAULT '0' NOT NULL,
    tstamp          int(11)       DEFAULT '0' NOT NULL,
    crdate          int(11)       DEFAULT '0' NOT NULL,
    cruser_id       int(11)       DEFAULT '0' NOT NULL,
    sorting         INT(10)       DEFAULT '0' NOT NULL,
    deleted         tinyint(4)    DEFAULT '0' NOT NULL,
    hidden          tinyint(4)    DEFAULT '0' NOT NULL,
    description     varchar(255)  DEFAULT ''  NOT NULL,
    deadline        int(11)       DEFAULT '0' NOT NULL,
    source_locale   int(11)       DEFAULT '0' NOT NULL,
    target_locale   int(11)       DEFAULT '0' NOT NULL,
    all_locale      tinyint(4)    DEFAULT '0' NOT NULL,
    localizer_path  varchar(4096) DEFAULT ''  NOT NULL,
    filename        varchar(4096) DEFAULT ''  NOT NULL,
    status          int(11)       DEFAULT '0' NOT NULL,
    previous_status int(11)       DEFAULT '0' NOT NULL,
    action          int(11)       DEFAULT '0' NOT NULL,
    uid_local       int(11)       DEFAULT '0' NOT NULL,
    uid_export      int(11)       DEFAULT '0' NOT NULL,
    uid_foreign     int(11)       DEFAULT '0' NOT NULL,
    tablenames      varchar(30)   DEFAULT ''  NOT NULL,
    processid       varchar(32)   DEFAULT ''  NOT NULL,
    configuration   text,
    last_error      text,
    response        text,
    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY processid (processid),
    KEY uid_local (uid_local),
    KEY uid_export (uid_export),
    KEY uid_foreign (uid_foreign),
    KEY localizer_status (status),
    KEY localizer_action (action),
    KEY last_error (last_error(1))
);

CREATE TABLE tx_localizer_cart_table_record_language_mm
(
    pid        int(11)      DEFAULT '0' NOT NULL,
    identifier varchar(32)  DEFAULT ''  NOT NULL,
    cart       int(11)      DEFAULT '0' NOT NULL,
    tablename  varchar(255) DEFAULT ''  NOT NULL,
    recordId   int(11)      DEFAULT '0' NOT NULL,
    languageId int(11)      DEFAULT '0' NOT NULL,
    KEY identifier (identifier),
    KEY tablename (tablename),
    KEY recordId (recordId),
    KEY languageId (languageId)
);

CREATE TABLE tx_localizer_settings_pages_mm
(
    uid         int(11)                 NOT NULL auto_increment,
    pid         int(11)     DEFAULT '0' NOT NULL,
    uid_local   int(11)     DEFAULT '0' NOT NULL,
    uid_foreign int(11)     DEFAULT '0' NOT NULL,
    tablenames  varchar(60) DEFAULT ''  NOT NULL,
    source      varchar(60) DEFAULT ''  NOT NULL,
    ident       varchar(30) DEFAULT ''  NOT NULL,
    sorting     int(11)     DEFAULT '0' NOT NULL,
    PRIMARY KEY (uid),
    KEY uid_local (uid_local),
    KEY uid_foreign (uid_foreign),
    KEY ident (source, ident)
);

CREATE TABLE pages
(
    localizer_include_with_automatic_export tinyint(4) DEFAULT '0' NOT NULL,
    localizer_include_with_specific_export  text
);

CREATE TABLE tx_l10nmgr_cfg
(
    tx_localizer_id int(11) DEFAULT '0' NOT NULL
);
