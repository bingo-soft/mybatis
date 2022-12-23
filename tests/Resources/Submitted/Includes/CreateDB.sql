drop table if exists SomeTable;

create table SomeTable (
  id int,
  field1 varchar(20),
  field2 varchar(20),
  field3 varchar(20)
);

insert into SomeTable (id, field1, field2, field3) values(1, 'a', 'b', 'c');