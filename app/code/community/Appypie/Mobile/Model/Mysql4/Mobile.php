<?php

class Appypie_Mobile_Model_Mysql4_Mobile extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the mobile_id refers to the key field in your database table.
        $this->_init('mobile/mobile', 'mobile_id');
    }
}