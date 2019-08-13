<?php
/*
 * Company : ONS Interactive Pvt. Ltd
 * Version : 1.1
 * Author : Ruchi Kapil
 *
 */

class Appypie_Mobile_Model_Cart_Product_Api extends Mage_Checkout_Model_Api_Resource_Product
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
     * @param  $quoteId
     * @param  $productsData
     * @param  $store
     * @return bool
     */
    public function moveToCustomerQuote($quoteId, $productsData, $store=null)
    {
        $quote = $this->_getQuote($quoteId, $store);

        if (empty($store)) {
            $store = $quote->getStoreId();
        }

        $customer = $quote->getCustomer();
        if (is_null($customer->getId())) {
            $this->_fault('customer_not_set_for_quote');
        }

        /** @var $customerQuote Mage_Sales_Model_Quote */
        $customerQuote = Mage::getModel('sales/quote')
            ->setStoreId($store)
            ->loadByCustomer($customer);

        if (is_null($customerQuote->getId())) {
            $this->_fault('customer_quote_not_exist');
        }

        if ($customerQuote->getId() == $quote->getId()) {
            $this->_fault('quotes_are_similar');
        }

        $productsData = $this->_prepareProductsData($productsData);
        if (empty($productsData)) {
            $this->_fault('invalid_product_data');
        }

        $errors = array();
        foreach($productsData as $key => $productItem){
            if (isset($productItem['product_id'])) {
                $productByItem = $this->_getProduct($productItem['product_id'], $store, "id");
            } else if (isset($productItem['sku'])) {
                $productByItem = $this->_getProduct($productItem['sku'], $store, "sku");
            } else {
                $errors[] = Mage::helper('checkout')->__("One item of products do not have identifier or sku");
                continue;
            }

            try {
                /** @var $quoteItem Mage_Sales_Model_Quote_Item */
                $quoteItem = $this->_getQuoteItemByProduct($quote, $productByItem,
                    $this->_getProductRequest($productItem));
                if($quoteItem->getId()){
                    $customerQuote->addItem($quoteItem);
                    $quote->removeItem($quoteItem->getId());
                    unset($productsData[$key]);
                } else {
                     $errors[] = Mage::helper('checkout')->__("One item of products is not belong any of quote item");
                }
            } catch (Mage_Core_Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (count($productsData) || !empty($errors)) {
            $this->_fault('unable_to_move_all_products', implode(PHP_EOL, $errors));
        }

        try {
            $customerQuote
                ->collectTotals()
                ->save();

            $quote
                ->collectTotals()
                ->save();
        } catch (Exception $e) {
             $this->_fault("product_move_quote_save_fault", $e->getMessage());
        }

        return true;
    }
    /**
     * @param  $customerId
     * @return bool
     */
    public function getCustomerQuote($customerId)
    {
		$customerQuote = Mage::getModel('sales/quote')->loadByCustomer($customerId);
        return $customerQuote;
	}
}
