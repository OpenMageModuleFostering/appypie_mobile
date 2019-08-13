<?php
/*
 * Company : ONS Interactive Pvt. Ltd
 * Version : 1.1
 * Author : Ruchi Kapil
 *
 */
class Appypie_Mobile_Model_Category_Api extends Appypie_Mobile_Model_Api
{
     /**
     * @param string $categoryId
     * @return String
     */
    public function categorylist($categoryId)
    {
		if($categoryId=="")
		{
			/*=========== Get Root Level categories ===========*/
			$categories = Mage::getModel('catalog/category')
				->getCollection()
				->addAttributeToSelect('*')
				->addIsActiveFilter()
				->addAttributeToFilter('level',2)
				->addOrderField('name');
		}else{
			/*=========== Get Sub categories ===========*/
			/*$categories = Mage::getModel('catalog/category')
				->getCollection()
				->addAttributeToSelect('*')
				->addIsActiveFilter()
				->addAttributeToFilter('parent_id',$categoryId)
				->addOrderField('name');*/
			$categories = Mage::getModel('catalog/category')->getCategories($categoryId);
		}
		$arr = array();
		foreach ($categories as $cat){
			$flag = 0;
			/*=========== Check Next level category has product or not ===========*/
			$catego = Mage::getModel('catalog/category')->load($cat->getId());
			$catProdCount = Mage::getModel('catalog/product')->getCollection()->addCategoryFilter($catego);
			$categoriesNext = Mage::getModel('catalog/category')
				->getCollection()
				->addAttributeToSelect('*')
				->addIsActiveFilter()
				->addAttributeToFilter('parent_id',$catego->getId())
				->addOrderField('name');
			foreach ($categoriesNext as $catNext){
				$categoNext = Mage::getModel('catalog/category')->load($catNext->getId());
				$collection = Mage::getModel('catalog/product')->getCollection()
				->addAttributeToFilter('status', 1)
				->addAttributeToFilter('visibility', 4)
				->addCategoryFilter($categoNext);
				if(count($collection) > 0)
				{
					$flag = 1;
				}
			}
			if($categoryId!="")
			{
				$parent = Mage::getModel('catalog/category')->load($categoryId);
				$arr['parent'] = $parent->getName();
			}
				if($cat->getIncludeInMenu())
				{
					$subcats = $catego->getChildrenCategories();
					$arr[$cat->getId()]['id'] = $cat->getId();
					$arr[$cat->getId()]['name'] = $cat->getName();
					$arr[$cat->getId()]['parent_id'] = $cat->getParentId();
					$arr[$cat->getId()]['level'] = $cat->getLevel();
					if($flag!=1)
					{
						$arr[$cat->getId()]['children_count'] = count($catProdCount);
					}else{
						$arr[$cat->getId()]['children_count'] = 0;
					}
					$arr[$cat->getId()]['url_path'] = $cat->getUrlPath();
					$arr[$cat->getId()]['is_active'] = $cat->getIsActive();
					$arr[$cat->getId()]['flag'] = $flag;
				}
		}
		return $arr;
	}
	/**
     * @param string $data
     * @return String
    */
    public function productlist($data)
    {
		$data = json_decode($data,true);
		$configPageValue = Mage::getStoreConfig('mobile/general/productperpage');
		$page = empty($data['page']) ? 1 : $data['page'];

		$_category = Mage::getModel('catalog/category')->load($data['categoryId']);
		$products = Mage::getModel('catalog/product')->getCollection()
		->addAttributeToSelect('*')
		->addAttributeToFilter('status', 1)
		->addAttributeToFilter('visibility', 4)
		->addAttributeToFilter('price',array('gt' => 0))
		->joinField('inventory_in_stock', 'cataloginventory_stock_item', 'is_in_stock', 'product_id=entity_id','is_in_stock>=1', 'inner')
		->addCategoryFilter($_category);


		if($data['sort']!="")
        {
			if($data['sorttype']==""){ $data['sorttype'] = 'asc';	}
			$products->addAttributeToSort($data['sort'], $data['sorttype']);
		}
		$products->setPageSize($configPageValue);
		$products->setCurPage($page);

		$collectionCount = Mage::getModel('catalog/product')->getCollection()
			->addAttributeToSelect('name')
			->addAttributeToFilter('status', 1)
			->addAttributeToFilter('visibility', 4)
			->joinField('inventory_in_stock', 'cataloginventory_stock_item', 'is_in_stock', 'product_id=entity_id','is_in_stock>=1', 'inner')
			->addCategoryFilter($_category);
		$totalCount = count($collectionCount);
		$arr = array();
		$resultcat = array();
		$arr['total'] = $totalCount;
		foreach ($products as $product){
			$arr[$product->getId()]['product_id'] = $product->getId();
			$arr[$product->getId()]['name'] = $product->getName();
			$arr[$product->getId()]['price'] = $product->getPrice();
			$arr[$product->getId()]['special_price'] = $product->getSpecialPrice();
			$arr[$product->getId()]['thumbnail'] = (string)Mage::helper('catalog/image')->init($product, 'image')->resize(150);
			$arr[$product->getId()]['is_in_stock'] = $product->getStockItem()->getIsInStock();
			$arr[$product->getId()]['short_description'] = $product->getShortDescription();
			$arr[$product->getId()]['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();

		}
		return $arr;
	}

	public function brandslist( )
    {
		return $brandData;
		die;
		$brandData  = json_decode($brandData,true);
		return $brandData ;
		die;
		$collection  = Mage::getModel('brands/brands')->getCollection();
		$brandsArray = array();
		$i = 0;
		foreach($collection as $values )
		{
			$collection[$values->getCategory[]][$i]['brands_name'] =$values->getTitle();
			$collection[$values->getCategory[]][$i]['brands_id'] =$values->getBrandsId();
			$collection[$values->getCategory[]][$i]['filename'] =$values->getFilename();
			$collection[$values->getCategory[]][$i]['brands_attribute_id'] =$values->getBrandsAttributeId();
		}
		$returnArray = array();
		foreach( $collection as $key => $value )
		{
			$category 	=Mage::getModel('catalog/category')->load($key);
			$returnArray[$category->getName()]= $value;
		}
		return $returnArray;
	}
	}

}
?>
