<?php

class Appypie_Mobile_Block_Adminhtml_Mobile_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'mobile';
        $this->_controller = 'adminhtml_mobile';
        
        $this->_updateButton('save', 'label', Mage::helper('mobile')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('mobile')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('mobile_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'mobile_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'mobile_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('mobile_data') && Mage::registry('mobile_data')->getId() ) {
            return Mage::helper('mobile')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('mobile_data')->getTitle()));
        } else {
            return Mage::helper('mobile')->__('Add Item');
        }
    }
}