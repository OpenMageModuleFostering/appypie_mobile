<?php
class Appypie_Mobile_Block_Mobile extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getMobile()     
     { 
        if (!$this->hasData('mobile')) {
            $this->setData('mobile', Mage::registry('mobile'));
        }
        return $this->getData('mobile');
        
    }
}