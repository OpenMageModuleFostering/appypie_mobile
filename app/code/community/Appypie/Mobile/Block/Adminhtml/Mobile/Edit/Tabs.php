<?php

class Appypie_Mobile_Block_Adminhtml_Mobile_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('mobile_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('mobile')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('mobile')->__('Item Information'),
          'title'     => Mage::helper('mobile')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('mobile/adminhtml_mobile_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}