<?php
/*
 * Company : ONS Interactive Pvt. Ltd
 * Version : 1.1
 * Author : Ruchi Kapil
 *
 */
class Appypie_Mobile_Model_Api extends Mage_Api_Model_Resource_Abstract
{

    /**
     * Returns version of the installed magento
     * @return String
     */
    public function getVersion()
    {
        return "Ruchi Kapil";
    }
     /**
     * @param string $categoryId
     * @return String
     */
    public function categorylist($categoryId)
    {
		if($categoryId=="")
		{
			$categories = Mage::getModel('catalog/category')
				->getCollection()
				->addAttributeToSelect('*')
				->addIsActiveFilter()
				->addAttributeToFilter('level',2);
		}else{
			$categories = Mage::getModel('catalog/category')
				->getCollection()
				->addAttributeToSelect('*')
				->addIsActiveFilter()
				->addAttributeToFilter('parent_id',$categoryId);
		}
		$arr = array();
		foreach ($categories as $cat){
			//$cat = Mage::getModel('catalog/category');
			//$cat->load($id);
			$arr[$cat->getId()]['id'] = $cat->getId();
			$arr[$cat->getId()]['name'] = $cat->getName();
			$arr[$cat->getId()]['parent_id'] = $cat->getParentId();
			$arr[$cat->getId()]['level'] = $cat->getLevel();
			$arr[$cat->getId()]['children_count'] = $cat->getChildrenCount();
			$arr[$cat->getId()]['url_path'] = $cat->getUrlPath();
			$arr[$cat->getId()]['is_active'] = $cat->getIsActive();
		}
		return $arr;
	}
}
?>
