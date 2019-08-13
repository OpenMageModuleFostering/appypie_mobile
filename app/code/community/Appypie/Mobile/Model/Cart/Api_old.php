<?php
/*
 * Company : ONS Interactive Pvt. Ltd
 * Version : 1.1
 * Author : Ruchi Kapil
 *
 */
class Appypie_Mobile_Model_Cart_Api extends Mage_Checkout_Model_Api_Resource_Product
{
	/**
     * Base preparation of product data
     *
     * @param mixed $data
     * @return null|array
     */
    protected function _prepareProductsData($data)
    {
        return is_array($data) ? $data : null;
    }

	/**
     * Create new quote for shopping cart
     *
     * @param int|string $store
     * @return int
     */
    public function create($store = null)
    {
        $storeId = $this->_getStoreId($store);

        try {
            /*@var $quote Mage_Sales_Model_Quote*/
            $quote = Mage::getModel('sales/quote');
            $quote->setStoreId($storeId)
                    ->setIsActive(true)
                    ->setIsMultiShipping(false)
                    ->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('create_quote_fault', $e->getMessage());
        }
        return (int) $quote->getId();
    }

    /**
     * @param  $products
     * @return bool
     */
    public function addcart($products)
    {
		$products = json_decode($products,true);
		$count = count($products);
		foreach($products as $product)
		{
			//$productId = $product['product_id'];
			//$chkProduct = Mage::getModel('catalog/product')->load($productId);
			//if($chkProduct->getStockItem()->getQty() >= $product['qty'])
			//{
				//return $product;
				$quoteId = $product['quoteId'];
				$itemId = $product['itemId'];
				unset($product['quoteId']);
				unset($product['itemId']);
				$productsData[] =  $product;
			//}
		}

		//return $productsData;
        $quote = $this->_getQuote($quoteId, $store);
        if (empty($store)) {
            $store = $quote->getStoreId();
        }

        $productsData = $this->_prepareProductsData($productsData);
        if (empty($productsData)) {
            $this->_fault('invalid_product_data');
        }

        $errors = array();
        foreach ($productsData as $productItem) {
            if (isset($productItem['product_id'])) {
                $productByItem = $this->_getProduct($productItem['product_id'], $store, "id");
            } else if (isset($productItem['sku'])) {
                $productByItem = $this->_getProduct($productItem['sku'], $store, "sku");
            } else {
                $errors[] = Mage::helper('checkout')->__("One item of products do not have identifier or sku");
                continue;
            }

            $productRequest = $this->_getProductRequest($productItem);
            try {
                $result = $quote->addProduct($productByItem, $productRequest);
                if (is_string($result)) {
                    Mage::throwException($result);
                }
            } catch (Mage_Core_Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            $this->_fault("add_product_fault", implode(PHP_EOL, $errors));
        }

        try {
            $quote->collectTotals()->save();
            if($itemId!="")
            {
				$wishlistItem=Mage::getModel('wishlist/item')->load($itemId)->delete();
			}
        } catch(Exception $e) {
            $this->_fault("add_product_quote_save_fault", $e->getMessage());
        }

        return true;
    }
	/**
    * @param  $quoteId
    * @return String
    */
    public function cartlist($quoteId)
    {
		$result = array();
		$quote = Mage::getModel("sales/quote");
		$quote->loadByIdWithoutStore($quoteId);
		if (!$quote->getItemsCount()) {
            return array();
        }
		$productsResult = array();
		$productsResult['items_count'] = $quote->getItemsCount();
		$productsResult['items_qty'] = $quote->getItemsQty();
        foreach ($quote->getAllItems() as $item) {
            /** @var $item Mage_Sales_Model_Quote_Item */
            if($item->getParentItemId()=="")
            {
				// return
				$product = $item->getProduct();
				$infoBuyRequest = $item->getOptionByCode('info_buyRequest');
				$buyRequest = new Varien_Object(unserialize($infoBuyRequest->getValue()));
				$myData = $buyRequest->getData();
				$productModel = Mage::getModel('catalog/product');
				if(!empty($myData['super_attribute']))
				{
					$i=0; $prodSuper = array();
					foreach($myData['super_attribute'] as $key=>$val)
					{
						$attr = $productModel->getResource()->getAttribute($key);
						$prodSuper[$i][$attr->getFrontendLabel()] = $attr->getSource()->getOptionText($val);
						$i++;
					}
				}
				$productsResult[] = array( // Basic product data
					'item_id'   => $item->getItemId(),
					'product_id'   => $product->getId(),
					'sku'          => $product->getSku(),
					'name'         => $product->getName(),
					'super_attribute' => $myData['super_attribute'],
					'qty'         => $item->getQty(),
					'price'         => $item->getPrice(),
					'parent'         => $item->getParentItemId(),
					'subtotal'         => $item->getRowTotal(),
					'thumbnail'         => (string)Mage::helper('catalog/image')->init($product, 'small_image')->resize(135),
					'set'          => $product->getAttributeSetId(),
					'type'         => $product->getTypeId(),
					'category_ids' => $product->getCategoryIds(),
					'website_ids'  => $product->getWebsiteIds(),
					'product_attribue'  => $prodSuper
				);

				unset($prodSuper);
			}
        }

        return $productsResult;
	}
	/**
     * @param  $quoteId
     * @param  $productsData
     * @param  $store
     * @return bool
     */
    public function update($products)
    {
        $products = json_decode($products,true);
		$count = count($products);
		foreach($products as $product)
		{
			//$productId = $product['product_id'];
			//$chkProduct = Mage::getModel('catalog/product')->load($productId);
			//if($chkProduct->getStockItem()->getQty() >= $product['qty'])
			//{
				//return $product;
				$quoteId = $product['quoteId'];
				$store = $product['store'];
				unset($product['quoteId']);
				unset($product['store']);
				unset($chkProduct);
				$productsData[] =  $product;
			//}else{
				//$this->_fault("product_qty_not_available_fault", $e->getMessage());
			//}
		}
		if($productsData)
		{
			$quote = $this->_getQuote($quoteId, $store);
			if (empty($store)) {
				$store = $quote->getStoreId();
			}

			$productsData = $this->_prepareProductsData($productsData);
			if (empty($productsData)) {
				$this->_fault('invalid_product_data');
			}

			$errors = array();
			foreach ($productsData as $productItem) {
				if (isset($productItem['product_id'])) {
					$productByItem = $this->_getProduct($productItem['product_id'], $store, "id");
				} else if (isset($productItem['sku'])) {
					$productByItem = $this->_getProduct($productItem['sku'], $store, "sku");
				} else {
					$errors[] = Mage::helper('checkout')->__("One item of products do not have identifier or sku");
					continue;
				}

				/** @var $quoteItem Mage_Sales_Model_Quote_Item */
				$quoteItem = $this->_getQuoteItemByProduct($quote, $productByItem,
					$this->_getProductRequest($productItem));
				if (is_null($quoteItem->getId())) {
					$errors[] = Mage::helper('checkout')->__("One item of products is not belong any of quote item");
					continue;
				}

				if ($productItem['qty'] > 0) {
					$quoteItem->setQty($productItem['qty']);
				}else{
					$productItem['quoteId'] = $quoteId;
					$productItem['itemId'] = $quoteItem->getItemId();
					$quote = $this->remove(json_encode(array($productItem)));
				}
			}

			if (!empty($errors)) {
				$this->_fault("update_product_fault", implode(PHP_EOL, $errors));
			}

			try {
				$quote->collectTotals()->save();
			} catch(Exception $e) {
				$this->_fault("update_product_quote_save_fault", $e->getMessage());
			}

			return true;
		}else{
			return false;
		}
    }

	/**
    * @param  $quoteId
    * @return String
    */
    public function emptycart($quoteId)
    {
		$result = array();
		$quote = Mage::getModel("sales/quote");
		$quote->loadByIdWithoutStore($quoteId);
		$emptyQuote = $this->_getQuote($quoteId);
		if (!$quote->getItemsCount()) {
            return array();
        }
        foreach ($quote->getAllItems() as $item) {
            /** @var $item Mage_Sales_Model_Quote_Item */
            $product = $item->getProduct();
			$emptyQuote->removeItem($item->getId());
			//$emptyQuote->save();
			$emptyQuote->collectTotals()->save();
        }
	}
	/**
     * @param  $quoteId
     * @param  $productsData
     * @param  $store
     * @return bool
     */
    public function remove($products)
    {
		$products = json_decode($products,true);
		$count = count($products);
		foreach($products as $product)
		{
			$quoteId = $product['quoteId'];
			//unset($product['quoteId']);
			$productsData[] =  $product;
			//return $product['itemId'];
			$quote = $this->_getQuote($quoteId, $product['store']);
			$quote->removeItem($product['itemId']);
			$quote->collectTotals()->save();

		}
		//return $productsData;

        return $quote;
    }
    /**
     * @param  $quoteId
     * @param  $couponCode
     * @param  $storeId
     * @return bool
     */
    public function addcoupon($data)
    {
		$data = json_decode($data,true);
        return $this->_applyCoupon($data['quoteId'], $data['couponCode'], $store = null);
    }

    /**
     * @param  $quoteId
     * @param  $couponCode
     * @param  $store
     * @return bool
     */
    protected function _applyCoupon($quoteId, $couponCode, $store = null)
    {
        $quote = $this->_getQuote($quoteId, $store);

        if (!$quote->getItemsCount()) {
            $this->_fault('quote_is_empty');
        }

        $oldCouponCode = $quote->getCouponCode();
        if (!strlen($couponCode) && !strlen($oldCouponCode)) {
            return false;
        }

        try {
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->setCouponCode(strlen($couponCode) ? $couponCode : '')
                ->collectTotals()
                ->save();
        } catch (Exception $e) {
            $this->_fault("cannot_apply_coupon_code", $e->getMessage());
        }

        if ($couponCode) {
            if (!$couponCode == $quote->getCouponCode()) {
                $this->_fault('coupon_code_is_not_valid');
                //return 'coupon code is not valid';
            }
        }

        return true;
    }
    /**
     * @param  $products
     * @return bool
     */
    public function wishlisttocart($products)
    {
		$return = array();
		$products = json_decode($products,true);
		$count = count($products);
		foreach($products as $product)
		{
			//return $product;
			$quoteId = $product['quoteId'];
			unset($product['quoteId']);
			//unset($product['itemid']);
			$productsData[] =  $product;
		}

		//return $productsData;
        $quote = $this->_getQuote($quoteId, $store);
        if (empty($store)) {
            $store = $quote->getStoreId();
        }

        $productsData = $this->_prepareProductsData($productsData);
        if (empty($productsData)) {
            //$this->_fault('invalid_product_data');
        }

        $errors = array();
        foreach ($productsData as $productItem) {
            if (isset($productItem['product_id'])) {
                $productByItem = $this->_getProduct($productItem['product_id'], $store, "id");
            } else if (isset($productItem['sku'])) {
                $productByItem = $this->_getProduct($productItem['sku'], $store, "sku");
            } else {
                $errors[] = Mage::helper('checkout')->__("One item of products do not have identifier or sku");
                continue;
            }

            $productRequest = $this->_getProductRequest($productItem);
            try {
                $result = $quote->addProduct($productByItem, $productRequest);

                if (is_string($result)) {
					$return[] = $productItem['product_id'];
                   // Mage::throwException($result);
                }else{
					$customer=Mage::getModel('customer/customer');
					$customer->setWebsiteId(1);
					$customer->loadByEmail($productItem['email']);
					$wishlistItem=Mage::getModel('wishlist/item')->load($productItem['itemid'])->delete();
				}
            } catch (Mage_Core_Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            //$this->_fault("add_product_fault", implode(PHP_EOL, $errors));
        }

        try {
            $quote->collectTotals()->save();
        } catch(Exception $e) {
            //$this->_fault("add_product_quote_save_fault", $e->getMessage());
        }
		if(!empty($return))
		{
			return $return;
		}else{
			return true;
		}
    }

}
?>
