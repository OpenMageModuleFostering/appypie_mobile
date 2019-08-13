<?php
/*
 * Company : ONS Interactive Pvt. Ltd
 * Version : 1.1
 * Author : Ruchi Kapil
 *
 */
class Appypie_Mobile_Model_Wishlist_Api extends Mage_Api_Model_Resource_Abstract
{
	/**
	 *  @param string $data
     * Returns version of the installed magento
     * @return String
     */
    public function addwishlist($data)
    {
		$data = json_decode($data,true);
		//return $data;
		$customer=Mage::getModel('customer/customer');
		$customer->setWebsiteId(1);
		$customer->loadByEmail($data['email']);
		$product=Mage::getModel('catalog/product')->load($data['productid']);
		$product->setWishlistStoreId($data['store']);
		$wishlist=Mage::getModel('wishlist/wishlist')->loadByCustomer($customer,true);
		$wishlist->addNewItem($product,'',1);
		$wishListItemCollection = $wishlist->getItemCollection();
		return count($wishListItemCollection);

	}
	/**
	 *  @param string $customerEmail
     * @return String
     */
	public function listwishlist($customerEmail)
    {
		//return $data;
		$customer=Mage::getModel('customer/customer');
		$customer->setWebsiteId(1);
		$customer->loadByEmail($customerEmail);
		$wishlist=Mage::getModel('wishlist/wishlist')->loadByCustomer($customer,true);
		$wishListItemCollection = $wishlist->getItemCollection();
		if (count($wishListItemCollection))
		{
			$arrProductIds = array();
			$i =0;
			foreach ($wishListItemCollection as $item)
			{
				/* @var $product Mage_Catalog_Model_Product */
				$product = $item->getProduct();

				$arrProductIds[$i]['itemid'] = $item->getId();
				$arrProductIds[$i]['productid'] = $item->getProductId();
				$arrProductIds[$i]['qty'] = $item->getQty();
				$arrProductIds[$i]['name'] = $item->getName();
				$arrProductIds[$i]['price'] = $item->getPrice();
				$arrProductIds[$i]['special_price'] = $product->getSpecialPrice();
				$arrProductIds[$i]['description'] = $product->getShortDescription();
				$arrProductIds[$i]['thumbnail'] = (string)Mage::helper('catalog/image')->init($product, 'small_image')->resize(135);
				$arrProductIds[$i]['type'] = $product->getTypeId();
				//$arrProductIds[$item->getProductId()]['option'] = $item->getValue();
				if($product->getTypeId()=="configurable")
				{
					$childProducts = Mage::getModel('catalog/product_type_configurable')
							->getUsedProducts(null,$product);
					$productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
					$attributeOptions = array();
					foreach ($productAttributeOptions as $productAttribute) {
						$arrProductIds[$i]['dropdown'][$productAttribute['attribute_id']]['label'] = $productAttribute['label'];
						$arrProductIds[$i][$productAttribute['attribute_id']]['code'] = $productAttribute['attribute_code'];
						foreach ($productAttribute['values'] as $attribute) {
							$arrProductIds[$i]['dropdown'][$productAttribute['attribute_id']][$attribute['value_index']] = $attribute['store_label']."###".$attribute['is_percent']."###".$attribute['pricing_value'];
							//$arr['dropdown'][$productAttribute['attribute_id']][$attribute['value_index']] = $attribute;

						}
					}
					foreach($childProducts as $cproduct)
					{
						foreach ($productAttributeOptions as $productAttribute) {
							$acode = 'get'.ucfirst($productAttribute['attribute_code']);
							$arrProductIds[$i]['dp'][$cproduct->getSku()][$cproduct->$acode()]=$cproduct->$acode();

						}
						$arrProductIds[$i]['dp'][$cproduct->getSku()]['qty']=$cproduct->getStockItem()->getQty();
					}
				}

				$i++;
			}
		}
		return $arrProductIds;

	}
	/**
	 *  @param string $itemId
     * @return String
     */
	public function removewishlist($itemId)
	{
		$wishlistItem=Mage::getModel('wishlist/item')->load($itemId)->delete();
		return "removed";

	}
	/**
	 *  @param string $data
     * Returns version of the installed magento
     * @return String
     */
    public function updatewishlist($data)
    {
		$data = json_decode($data,true);
		//return $data;
		foreach($data as $prod)
		{
			$customer=Mage::getModel('customer/customer');
			$customer->setWebsiteId(1);
			$customer->loadByEmail($prod['email']);
			//$product=Mage::getModel('catalog/product')->load($prod['productid']);
			//$product->setWishlistStoreId($prod['store']);
			$wishlist=Mage::getModel('wishlist/wishlist')->loadByCustomer($customer,true);
			$wishlist->updateItem($prod['itemid'],'',array('qty'=>$prod['qty']));
		}

		$wishListItemCollection = $wishlist->getItemCollection();
		return 'updated';
	}
	function getStoreByCode($storeCode)
	{
		$stores = array_keys(Mage::app()->getStores());
		foreach($stores as $id){
			$store = Mage::app()->getStore($id);
			if($store->getCode()==$storeCode) {
				return $id;
			}
		}
		return null; // if not found
	}
}
?>
