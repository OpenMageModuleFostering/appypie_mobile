<?php
/*
 * Company : ONS Interactive Pvt. Ltd
 * Version : 1.1
 * Author : Ruchi Kapil
 *
 */

class Appypie_Mobile_Model_Cart_Payment_Api extends Mage_Checkout_Model_Api_Resource
{

    protected function _preparePaymentData($data)
    {
        if (!(is_array($data) && is_null($data[0]))) {
            return array();
        }

        return $data;
    }

    /**
     * @param  $method
     * @param  $quote
     * @return bool
     */
    protected function _canUsePaymentMethod($method, $quote)
    {
        if ( !($method->isGateway() || $method->canUseInternal()) ) {
            return true;
        }

        if (!$method->canUseForCountry($quote->getBillingAddress()->getCountry())) {
            return false;
        }

        if (!$method->canUseForCurrency(Mage::app()->getStore($quote->getStoreId())->getBaseCurrencyCode())) {
            return false;
        }

        /**
         * Checking for min/max order total for assigned payment method
         */
        $total = $quote->getBaseGrandTotal();
        $minTotal = $method->getConfigData('min_order_total');
        $maxTotal = $method->getConfigData('max_order_total');

        if ((!empty($minTotal) && ($total < $minTotal)) || (!empty($maxTotal) && ($total > $maxTotal))) {
            return false;
        }

        return true;
    }

    protected function _getPaymentMethodAvailableCcTypes($method)
    {
        $ccTypes = Mage::getSingleton('payment/config')->getCcTypes();
        $methodCcTypes = explode(',',$method->getConfigData('cctypes'));
        foreach ($ccTypes as $code=>$title) {
            if (!in_array($code, $methodCcTypes)) {
                unset($ccTypes[$code]);
            }
        }
        if (empty($ccTypes)) {
            return null;
        }

        return $ccTypes;
    }

    /**
     * @param  $quoteId
     * @param  $store
     * @return array
     */
    public function getPaymentMethodsList($quoteId, $store=null)
    {
        $quote = $this->_getQuote($quoteId, $store);
        $store = $quote->getStoreId();

        $total = $quote->getBaseSubtotal();

        $methodsResult = array();
        $methods = Mage::helper('payment')->getStoreMethods($store, $quote);
        foreach ($methods as $key=>$method) {
            /** @var $method Mage_Payment_Model_Method_Abstract */
            if ($this->_canUsePaymentMethod($method, $quote)
                    && ($total != 0
                        || $method->getCode() == 'free'
                        || ($quote->hasRecurringItems() && $method->canManageRecurringProfiles()))) {
                $methodsResult[] =
                        array(
                            "code" => $method->getCode(),
                            "title" => $method->getTitle(),
                            "ccTypes" => $this->_getPaymentMethodAvailableCcTypes($method)
                        );
            }
        }

        return $methodsResult;
    }

    /**
     * @param  $quoteId
     * @param  $paymentData
     * @param  $store
     * @return bool
     */
    public function setPaymentMethod($quoteId, $paymentData, $store=null)
    {
        $quote = $this->_getQuote($quoteId, $store);
        $store = $quote->getStoreId();

        $paymentData = $this->_preparePaymentData($paymentData);

        if (empty($paymentData)) {
            $this->_fault("payment_method_empty");
        }

        if ($quote->isVirtual()) {
            // check if billing address is set
            if (is_null($quote->getBillingAddress()->getId()) ) {
                $this->_fault('billing_address_is_not_set');
            }
            $quote->getBillingAddress()->setPaymentMethod(isset($paymentData['method']) ? $paymentData['method'] : null);
        } else {
            // check if shipping address is set
            if (is_null($quote->getShippingAddress()->getId()) ) {
                $this->_fault('shipping_address_is_not_set');
            }
            $quote->getShippingAddress()->setPaymentMethod(isset($paymentData['method']) ? $paymentData['method'] : null);
        }

        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }

        $total = $quote->getBaseSubtotal();
        $methods = Mage::helper('payment')->getStoreMethods($store, $quote);
        foreach ($methods as $key=>$method) {
            if ($method->getCode() == $paymentData['method']) {
                /** @var $method Mage_Payment_Model_Method_Abstract */
                if (!($this->_canUsePaymentMethod($method, $quote)
                        && ($total != 0
                            || $method->getCode() == 'free'
                            || ($quote->hasRecurringItems() && $method->canManageRecurringProfiles())))) {
                    $this->_fault("method_not_allowed");
                }
            }
        }

        try {
            $payment = $quote->getPayment();
            $payment->importData($paymentData);


            $quote->setTotalsCollectedFlag(false)
                    ->collectTotals()
                    ->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('payment_method_is_not_set', $e->getMessage());
        }
        return true;
    }


    /**
     * @param  $quoteId
     * @param  $paymentData
     * @param  $store
     * @return bool
     */
   /* public function setpaymentmethod($paymentData)
    {
		//$store=1;
		$paymentData = json_decode($paymentData,true);
		$quoteId = $paymentData['quoteId'];
		$paymentData = $paymentData['payment'];
        $quote = $this->_getQuote($quoteId, $store);
        $store = $quote->getStoreId();

        $paymentData = $this->_preparePaymentData($paymentData);

        if (empty($paymentData)) {
            $this->_fault("payment_method_empty");
        }

        if ($quote->isVirtual()) {
            // check if billing address is set
            if (is_null($quote->getBillingAddress()->getId()) ) {
                $this->_fault('billing_address_is_not_set');
            }
            $quote->getBillingAddress()->setPaymentMethod(isset($paymentData['method']) ? $paymentData['method'] : null);
        } else {
            // check if shipping address is set
            if (is_null($quote->getShippingAddress()->getId()) ) {
                $this->_fault('shipping_address_is_not_set');
            }
            $quote->getShippingAddress()->setPaymentMethod(isset($paymentData['method']) ? $paymentData['method'] : null);
        }

        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }

        $total = $quote->getBaseSubtotal();
        $methods = Mage::helper('payment')->getStoreMethods($store, $quote);
        foreach ($methods as $key=>$method) {
            if ($method->getCode() == $paymentData['method']) {
                /** @var $method Mage_Payment_Model_Method_Abstract */
             /*   if (!($this->_canUsePaymentMethod($method, $quote)
                        && ($total != 0
                            || $method->getCode() == 'free'
                            || ($quote->hasRecurringItems() && $method->canManageRecurringProfiles())))) {
                    $this->_fault("method_not_allowed");
                }
            }
        }

        try {
            $payment = $quote->getPayment();
            $payment->importData($paymentData);


            $quote->setTotalsCollectedFlag(false)
                    ->collectTotals()
                    ->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('payment_method_is_not_set', $e->getMessage());
        }
        return true;
    }*/
    public function placeorder($orderdata)
	{
		$orderdata = json_decode($orderdata,true);
		$quoteId = $orderdata['quoteId'];
		$paymentMethod = $orderdata['payment']['method'];
		$paymentData = $orderdata['payment'];
		$quoteObj = Mage::getModel('sales/quote')->setStoreId($orderdata['store']);
		$quoteObj = $quoteObj->load($quoteId);
		$items = $quoteObj->getAllItems();
		$quoteObj->reserveOrderId();
		$quotePaymentObj = $quoteObj->getPayment();
		$quotePaymentObj->setMethod($paymentMethod);
		$quoteObj->setPayment($quotePaymentObj);
		$convertQuoteObj = Mage::getSingleton('sales/convert_quote');
		$orderObj = $convertQuoteObj->addressToOrder($quoteObj->getShippingAddress());
		$orderPaymentObj = $convertQuoteObj->paymentToOrderPayment($quotePaymentObj);
		$orderObj->setBillingAddress($convertQuoteObj->addressToOrderAddress($quoteObj->getBillingAddress()));
		$orderObj->setShippingAddress($convertQuoteObj->addressToOrderAddress($quoteObj->getShippingAddress()));
		$orderObj->setPayment($convertQuoteObj->paymentToOrderPayment($quoteObj->getPayment()));
		foreach ($items as $item)
		{
			$orderItem = $convertQuoteObj->itemToOrderItem($item);
			$options = array();
			if ($productOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct()))
			{
				$options = $productOptions;
			}
			if ($addOptions = $item->getOptionByCode('additional_options'))
			{
				$options['additional_options'] = unserialize($addOptions->getValue());
			}
			if ($options)
			{
				$orderItem->setProductOptions($options);
			}
			if ($item->getParentItem())
			{
				$orderItem->setParentItem($orderObj->getItemByQuoteItemId($item->getParentItem()->getId()));
			}
			$orderObj->addItem($orderItem);
		}
		$quoteObj->collectTotals();
		$quoteObj->setIsActive(false);

		$service = Mage::getModel('sales/service_quote', $quoteObj);
		$service->submitAll();
		$orderObj->setCanShipPartiallyItem(false);
		try
		{
			$last_order_increment_id = Mage::getModel("sales/order")->getCollection()->getLastItem()->getIncrementId();
			$order = $service->getOrder();
			$order->sendNewOrderEmail();
			return $last_order_increment_id;
		}
		catch (Exception $e)
		{
			Mage::log($e->getMessage());
			Mage::log($e->getTraceAsString());
			return "Exception:".$e;
		}
	}
	public function savepayment($data)
	{
		$data = json_decode($data,true);
		$order = Mage::getModel('sales/order')->loadByIncrementId($data['orderId']);
		$payment = $order->getPayment();
		$payment->setTransactionId($data['payment']['pay_key'])

            ->setShouldCloseParentTransaction('Completed' === 'Completed')
            ->setIsTransactionClosed(0)
                        ->setAdditionalInformation($data['payment']['app_id'])
            ->registerCaptureNotification($data['payment']['amount']);
        $order->save();
	}
}
