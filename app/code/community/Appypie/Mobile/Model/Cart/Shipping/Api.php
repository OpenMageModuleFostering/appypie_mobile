<?php
/*
 * Company : ONS Interactive Pvt. Ltd
 * Version : 1.1
 * Author : Ruchi Kapil
 *
 */

class Appypie_Mobile_Model_Cart_Shipping_Api extends Mage_Checkout_Model_Api_Resource
{
    public function __construct()
    {
        $this->_ignoredAttributeCodes['quote_shipping_rate'] = array('address_id', 'created_at', 'updated_at', 'rate_id', 'carrier_sort_order');
    }

    /**
     * Set an Shipping Method for Shopping Cart
     *
     * @param  $quoteId
     * @param  $shippingMethod
     * @param  $store
     * @return bool
     */
    public function setshippingmethod($shippingData)
    {
		$shippingData = json_decode($shippingData,true);
		$quoteId = $shippingData['quoteId'];
		$shippingMethod = $shippingData['method'];
        $quote = $this->_getQuote($quoteId, $store);

        $quoteShippingAddress = $quote->getShippingAddress();
        if(is_null($quoteShippingAddress->getId()) ) {
            $this->_fault("shipping_address_is_not_set");
        }

        $rate = $quote->getShippingAddress()->collectShippingRates()->getShippingRateByCode($shippingMethod);
        $quote->getShippingAddress()->setCollectShippingRates(true);
        if (!$rate) {
            $this->_fault('shipping_method_is_not_available');
        }

        try {
            $quote->getShippingAddress()->setShippingMethod($shippingMethod);
            $quote->collectTotals()->save();
        } catch(Mage_Core_Exception $e) {
            $this->_fault('shipping_method_is_not_set', $e->getMessage());
        }

        return true;
    }

	/**
     * Calculate an Shipping Method & Tax for Shopping Cart
     *
     * @param  $data
     * @return string
     */
	public function calculatetax($data)
	{
		$data = json_decode($data,true);
		$quoteId = $data['quoteId'];
		$country = (string) $data['country'];
		$postcode = (string) $data['postcode'];
		$regionId = (string) $data['region_id'];
		$region = (string) $data['region'];
		$city = (string) $data['city'];

		$quote = $this->_getQuote($quoteId, $store)->getShippingAddress()
        ->setCountryId($country)
        ->setCity($city)
        ->setPostcode($postcode)
        ->setRegionId($regionId)
        ->setRegion($region)
		->setCollectShippingRates(1);
		 $quote->save();


		 $quotes = $this->_getQuote($quoteId, $store)->getShippingAddress();

        if (is_null($quotes->getId())) {
            $this->_fault("shipping_address_is_not_set");
        }

        try {
            $groupedRates = $quotes->getGroupedAllShippingRates();

            $ratesResult = array();
            foreach ($groupedRates as $carrierCode => $rates ) {
                $carrierName = $carrierCode;
                if (!is_null(Mage::getStoreConfig('carriers/'.$carrierCode.'/title'))) {
                    $carrierName = Mage::getStoreConfig('carriers/'.$carrierCode.'/title');
                }

                foreach ($rates as $rate) {
                    $rateItem = $this->_getAttributes($rate, "quote_shipping_rate");
                    $rateItem['carrierName'] = $carrierName;
                    $ratesResult[] = $rateItem;
                    unset($rateItem);
                }
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('shipping_methods_list_could_not_be_retrived', $e->getMessage());
        }

        return $ratesResult;

	}
	/**
     * Calculate an Shipping Method & Tax for Shopping Cart
     *
     * @param  $data
     * @return string
     */
	public function calculation($data)
	{
		$data = json_decode($data,true);
		$arrAddresses = array('quoteId' => $data['quoteId'],
		array(
			"mode" => "shipping",
			"firstname" => "NULL",
			"lastname" => "NULL",
			"street" => "NULL",
			"city" => "NULL",
			"postcode" => "201301",
			"country_id" => "IN",
			"region_id" => "NULL",
			"telephone" => "NULL",
			"is_default_shipping" => 'NULL',
			"is_default_billing" => 'NULL'
		),
		array(
			"mode" => "billing",
			"firstname" => "NULL",
			"lastname" => "NULL",
			"street" => "NULL",
			"city" => "NULL",
			"postcode" => "201301",
			"country_id" => "IN",
			"region_id" => "NULL",
			"telephone" => "NULL",
			"is_default_shipping" => 'NULL',
			"is_default_billing" => 'NULL'
		)
		);
		$model = Mage::getModel("appypie_mobile/cart_customer_api");
		$model->addresses(json_encode($arrAddresses));

	}



}
