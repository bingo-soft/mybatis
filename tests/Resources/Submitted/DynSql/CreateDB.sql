drop table if exists ibtest.names;
drop table if exists ibtest.numerics;
drop schema if exists ibtest;

create schema ibtest;

create table ibtest.names (
    id int,
    description varchar(20)
);

insert into ibtest.names (id, description) values(1, 'Fred');
insert into ibtest.names (id, description) values(2, 'Wilma');
insert into ibtest.names (id, description) values(3, 'Pebbles');
insert into ibtest.names (id, description) values(4, 'Barney');
insert into ibtest.names (id, description) values(5, 'Betty');
insert into ibtest.names (id, description) values(6, 'Bamm Bamm');
insert into ibtest.names (id, description) values(7, 'Rock ''n Roll');

create table ibtest.numerics (
  id int,
  tinynumber smallint,
  smallnumber smallint,
  longinteger bigint,
  biginteger bigint,
  numericnumber numeric(10,2),
  decimalnumber decimal(10,2),
  realnumber real,
  floatnumber float,
  doublenumber float
);

insert into ibtest.numerics values(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);