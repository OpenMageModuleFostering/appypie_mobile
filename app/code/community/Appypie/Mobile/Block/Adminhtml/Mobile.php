<?php
class Appypie_Mobile_Block_Adminhtml_Mobile extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_mobile';
    $this->_blockGroup = 'mobile';
    $this->_headerText = Mage::helper('mobile')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('mobile')->__('Add Item');
    parent::__construct();
  }
}