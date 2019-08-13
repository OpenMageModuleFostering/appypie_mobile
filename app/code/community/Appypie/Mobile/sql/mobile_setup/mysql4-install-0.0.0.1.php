<?php
$installer = $this;
$installer->startSetup();
$table = $installer->getTable('catalog/eav_attribute'); 

//********* encript api user password **************//
$pass = Mage::helper('core')->getHash('1234567', 2);

$installer->run("
ALTER TABLE ".$table." ADD COLUMN used_in_mobile_listing TINYINT(1);

INSERT INTO {$this->getTable('api/user')} (`firstname`,`lastname`,`email`,`username`,`api_key`,`created`,`lognum`,`reload_acl_flag`,`is_active`) VALUES('Ravinesh','Raj','ravinesh@appypie.com','ravinesh','".$pass."',NOW(),0,0,1);

INSERT INTO {$this->getTable('api/role')} (`parent_id`,`tree_level`,`sort_order`,`role_type`,`user_id`,`role_name`) VALUES(0,1,0,'G',0,'api');

");
$installer->endSetup(); 

//********* Ger role id **************//
$fields = array();
$fields[] = 'role_id';
$stmt = $installer->getConnection()->select()
	->from($installer->getTable('api/role'), $fields)
    ->where('role_name = ?', 'api');
$result = $installer->getConnection()->fetchRow($stmt);

//********* Get User id **************//
$fieldUser = array();
$fieldUser[] = 'user_id';
$stmt1 = $installer->getConnection()->select()
	->from($installer->getTable('api/user'), $fieldUser)
    ->where('username = ?', 'ravinesh');
$result1 = $installer->getConnection()->fetchRow($stmt1);

$installer->run("
INSERT INTO
`{$installer->getTable('api/role')}` (`parent_id`,`tree_level`,`sort_order`,`role_type`,`user_id`,`role_name`) VALUES('".$result['role_id']."',2,0,'U','".$result1['user_id']."','Ravinesh');

INSERT INTO
`{$installer->getTable('api/rule')}` (`role_id`,`resource_id`,`api_privileges`,`assert_id`,`role_type`,`api_permission`) VALUES('".$result['role_id']."','all',NULL,0,'G','allow');

");
$installer->endSetup();
