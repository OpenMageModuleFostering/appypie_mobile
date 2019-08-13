<?php

class Appypie_Mobile_Block_Adminhtml_Mobile_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('mobile_form', array('legend'=>Mage::helper('mobile')->__('Item information')));
     
      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('mobile')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
      ));

      $fieldset->addField('filename', 'file', array(
          'label'     => Mage::helper('mobile')->__('File'),
          'required'  => false,
          'name'      => 'filename',
	  ));
		
      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('mobile')->__('Status'),
          'name'      => 'status',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('mobile')->__('Enabled'),
              ),

              array(
                  'value'     => 2,
                  'label'     => Mage::helper('mobile')->__('Disabled'),
              ),
          ),
      ));
     
      $fieldset->addField('content', 'editor', array(
          'name'      => 'content',
          'label'     => Mage::helper('mobile')->__('Content'),
          'title'     => Mage::helper('mobile')->__('Content'),
          'style'     => 'width:700px; height:500px;',
          'wysiwyg'   => false,
          'required'  => true,
      ));
     
      if ( Mage::getSingleton('adminhtml/session')->getMobileData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getMobileData());
          Mage::getSingleton('adminhtml/session')->setMobileData(null);
      } elseif ( Mage::registry('mobile_data') ) {
          $form->setValues(Mage::registry('mobile_data')->getData());
      }
      return parent::_prepareForm();
  }
}