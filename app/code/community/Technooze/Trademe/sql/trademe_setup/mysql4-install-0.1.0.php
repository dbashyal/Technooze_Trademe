<?php
/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
$sql = <<<SQLTEXT
create table tablename(tablename_id int not null auto_increment, name varchar(100), primary key(tablename_id));
    insert into tablename values(1,'tablename1');
    insert into tablename values(2,'tablename2');
		
SQLTEXT;

$installer->run($sql);
//demo 
//Mage::getModel('core/url_rewrite')->setId(null);
//demo 
$installer->endSetup();
