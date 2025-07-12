create database minichat;

use minichat;

create table user(
	user_id int auto_increment,
	fullname varchar(255),
	email varchar(255),
	address varchar(255),
	username varchar(255),
	password varchar(255),
	primary key (user_id)
);

create table parties(
	parties_id int auto_increment,
	initiator int,
	recipient int,
	started_at datetime,
	primary key (parties_id),
	foreign key (initiator) references user(user_id),
	foreign key (recipient) references user(user_id)
);

create table priv_chat(
	chat_id int auto_increment,
	parties_id int,
	message_sender varchar(255),
	timestamp datetime,
	message text,
	primary key (chat_id),
	foreign key (parties_id) references parties(parties_id)
);

create table groupss (
	group_id int auto_increment,
	group_name varchar(255),
	owner int,
	created_date date,
	primary key (group_id),
	foreign key (owner) references user(user_id)
);

create table group_member(
	gmember_id int auto_increment,
	user_id int,
	group_id int,
	join_date date,
	primary key (gmember_id),
	foreign key (user_id) references user(user_id),
	foreign key (group_id) references groupss(group_id)
);

create table group_chat(
	chat_id int auto_increment,
	group_id int,
	message_sender int,
	timestamp datetime,
	message text,
	primary key (chat_id),
	foreign key (group_id) references groupss(group_id),
	foreign key (message_sender) references user(user_id)
);