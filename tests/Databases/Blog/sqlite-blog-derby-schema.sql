DROP TABLE IF EXISTS comment;
DROP TABLE IF EXISTS post_tag;
DROP TABLE IF EXISTS tag;
DROP TABLE IF EXISTS post;
DROP TABLE IF EXISTS blog;
DROP TABLE IF EXISTS author;

CREATE TABLE author (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    username          VARCHAR(255) NOT NULL,
    password          VARCHAR(255) NOT NULL,
    email             VARCHAR(255) NOT NULL,
    bio               CLOB,
    favourite_section VARCHAR(25)
);

CREATE TABLE blog (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    author_id   INTEGER NOT NULL,
    title       VARCHAR(255)
);

CREATE TABLE post (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    blog_id     INTEGER,
    author_id   INTEGER NOT NULL,
    created_on  TEXT NOT NULL,
    section     VARCHAR(25) NOT NULL,
    subject     VARCHAR(255) NOT NULL,
    body        CLOB NOT NULL,
    draft       INTEGER NOT NULL,
    FOREIGN KEY (blog_id) REFERENCES blog(id)
);

CREATE TABLE tag (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        VARCHAR(255) NOT NULL
);

CREATE TABLE post_tag (
    post_id     INTEGER NOT NULL,
    tag_id      INTEGER NOT NULL,
    PRIMARY KEY (post_id, tag_id)
);

CREATE TABLE comment (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id     INTEGER NOT NULL,
    name        VARCHAR NOT NULL,
    comment     VARCHAR NOT NULL
);

CREATE TABLE node (
    id  INTEGER NOT NULL,
    parent_id INTEGER,
    PRIMARY KEY(id)
);