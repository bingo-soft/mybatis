drop table if exists users cascade;
drop table if exists friend cascade;

create table users (
  id int,
  name varchar(20)
);

create table friend (
  user_id int,
  friend_id int
);

insert into users (id, name) values
(1, 'User1'), (2, 'User2'), (3, 'User3');

insert into friend (user_id, friend_id) values
(1, 2), (2, 2), (2, 3);

drop table if exists post cascade;
drop table if exists blog cascade;
drop table if exists author cascade;

create table blog (
  id int,
  title varchar(16),
  author_id int,
  co_author_id int
);

create table author (
  id int,
  name varchar(16),
  reputation int,
  permissions int
);

insert into blog (id, title, author_id, co_author_id) values
(1, 'Blog1', 1, 2), (2, 'Blog2', 2, 3);

insert into author (id, name, reputation, permissions) values
(1, 'Author1', 1, 2), (2, 'Author2', 2, 3), (3, 'Author3', 3, 4);
