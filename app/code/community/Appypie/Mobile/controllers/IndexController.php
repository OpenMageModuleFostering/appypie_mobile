<?php
class Appypie_Mobile_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
    	
    	/*
    	 * Load an object by id 
    	 * Request looking like:
    	 * http://site.com/mobile?id=15 
    	 *  or
    	 * http://site.com/mobile/id/15 	
    	 */
    	/* 
		$mobile_id = $this->getRequest()->getParam('id');

  		if($mobile_id != null && $mobile_id != '')	{
			$mobile = Mage::getModel('mobile/mobile')->load($mobile_id)->getData();
		} else {
			$mobile = null;
		}	
		*/
		
		 /*
    	 * If no param we load a the last created item
    	 */ 
    	/*
    	if($mobile == null) {
			$resource = Mage::getSingleton('core/resource');
			$read= $resource->getConnection('core_read');
			$mobileTable = $resource->getTableName('mobile');
			
			$select = $read->select()
			   ->from($mobileTable,array('mobile_id','title','content','status'))
			   ->where('status',1)
			   ->order('created_time DESC') ;
			   
			$mobile = $read->fetchRow($select);
		}
		Mage::register('mobile', $mobile);
		*/

			
		$this->loadLayout();     
		$this->renderLayout();
    }
}