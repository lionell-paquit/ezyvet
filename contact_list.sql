CREATE TABLE contact (
id INT(11) NOT NULL AUTO_INCREMENT,
title ENUM('Mr', 'Mrs', 'Miss', 'Ms', 'Dr'),
first_name VARCHAR(64),
last_name VARCHAR(64),
company_name VARCHAR(64),
date_of_birth DATETIME,
notes VARCHAR(255),
PRIMARY KEY(id)
);
CREATE TABLE address (
id INT(11) NOT NULL AUTO_INCREMENT,
contact_id INT(11) NOT NULL,
street1 VARCHAR(100),
street2 VARCHAR(100),
suburb VARCHAR(64),
city VARCHAR(64),
post_code VARCHAR(16),
PRIMARY KEY(id)
);
CREATE TABLE phone (
id INT(11) NOT NULL AUTO_INCREMENT,
contact_id INT(11) NOT NULL,
name VARCHAR(64),
content VARCHAR(64),
type ENUM('Home', 'Work', 'Mobile', 'Fax', 'Other'),
PRIMARY KEY(id)
);
