-- create database with the nae that you want
-- next run this command

create table accounts(
		id int not null,
		email varchar(255),
    	name varchar(255),
    	picture varchar(255),
    	registered date,
    	method varchar(255),
		primary key (id)
)

create or alter table posts(
	title varchar(255),
	description varchar(255),
	imageLink varchar(255)
)