CREATE TABLE IF NOT EXISTS market_store (
	store_id bigint(20) NOT NULL AUTO_INCREMENT,
	com_id int(10) NOT NULL,
	con_com_id int(10) NOT NULL,
	prod_com_id int(10) NOT NULL,
	user_id int(10) NOT NULL,
	prod_name	varchar(255) NOT NULL,
	unit_measure varchar(10),
	quantity float(10,2) DEFAULT NULL,
	quality varchar(255) DEFAULT NULL,
	price  decimal(10,2) DEFAULT NULL,
	currency varchar(10) DEFAULT NULL,
	ts_date_entered datetime NOT NULL,
	contact_name varchar(255) NOT NULL,
	contact_address varchar(255) DEFAULT NULL,
	village varchar(255) DEFAULT NULL,
	zone varchar(255) DEFAULT NULL,
	
	PRIMARY KEY (store_id)

)ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `market_store` (`store_id`, `com_id`, `con_com_id`, `prod_com_id`, `user_id`, `com_number`, `prod_name`, `unit_measure`, `quantity`, `quality`, `price`,`currency`, `ts_date_entered`, `ts_date_delivered`, `contact_name`, `contact_tel`, `contact_address`, `village`,`zone`) VALUES
(1,'4ef066fd51ef2',13,9,6,1,'tamarin','kg',105,'Tamarin decortique propre',1000,'CFA','2011-12-20 10:42:16','2011-12-22 16:52:40','zakary diarra','78571298',NULL,'bokuy mankoina','mafoune')
(2,'4ef3652fb3157',17,2,6,1,'beurre de karite','kg',50,'beurre issu des amandes ebouillantées',1000,'CFA','2011-12-22 16:59:34','2011-12-22 17:46:19','ives diarra','74634097',NULL,'kinlokuy','diora')
(3,'4ef3652fb3157',17,4,6,1,'tamarin','kg',900,'tamarin décortiqué propre',750,'CFA','2011-12-22 16:59:34','2011-12-22 17:46:19','ives diarra','74634097',NULL,'kinlokuy','diora')
(4,'4ef3652fb3157',17,1,6,1,'amande de karite','kg',3000,'amande ébouillanté',150,'CFA','2011-12-22 16:59:34','2011-12-22 17:46:19','ives diarra','74634097',NULL,'kinlokuy','diora')