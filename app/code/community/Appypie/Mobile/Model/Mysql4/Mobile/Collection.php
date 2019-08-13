<?php

class Appypie_Mobile_Model_Mysql4_Mobile_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('mobile/mobile');
    }
}