BEGIN;

CREATE TYPE permission AS ENUM (
    'none', 'read',
    'reply', 'invite'
);

CREATE TABLE users (
    user_id     SERIAL      PRIMARY KEY,
    username    varchar(20) UNIQUE NOT NULL,
    email       varchar(50) UNIQUE NOT NULL,
    password    char(34)
);

CREATE TABLE discuss (
    discuss_id  SERIAL      PRIMARY KEY,
    initiater   integer     REFERENCES users NOT NULL,
    title       varchar(80) NOT NULL,
    permission  permission  DEFAULT 'none' NOT NULL,
    last_update timestamp   DEFAULT CURRENT_TIMESTAMP NOT NULL
);
CREATE INDEX discuss_initiater_index ON discuss (
    initiater
);
CREATE INDEX discuss_permission_index ON discuss (
    permission
);

CREATE TABLE reply (
    discuss_id  integer     REFERENCES discuss NOT NULL,
    user_id     integer     REFERENCES users NOT NULL,
    content     text,
    last_update timestamp   DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (discuss_id, user_id)
);
CREATE INDEX reply_user_id_index ON reply (
    user_id
);

CREATE TABLE history (
    history_id  SERIAL      PRIMARY KEY,
    content     text,
    time        timestamp   DEFAULT CURRENT_TIMESTAMP NOT NULL,
    discuss_id  integer     NOT NULL,
    user_id     integer     NOT NULL,
    FOREIGN KEY (discuss_id, user_id) REFERENCES reply
);
CREATE INDEX history_user_id_index ON history (
    user_id
);

CREATE TABLE user_permission (
    discuss_id  integer     REFERENCES discuss NOT NULL,
    user_id     integer     REFERENCES users NOT NULL,
    permission  permission  NOT NULL,
    PRIMARY KEY (discuss_id, user_id)
);
CREATE INDEX user_permission_user_id_index ON user_permission (
    user_id
);

COMMIT;
