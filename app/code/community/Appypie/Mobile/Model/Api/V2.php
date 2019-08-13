<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Customer
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Customer api V2
 *
 * @category   Mage
 * @package    Mage_Customer
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Appypie_Mobile_Model_Api_V2 extends Appypie_Mobile_Model_Api
{
	public function info()
    {
		 return "Ruchi Kapil";
    }
   public function categorylist($sessionId,$categoryId="")
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

		return json_encode($arr);
	}
}
