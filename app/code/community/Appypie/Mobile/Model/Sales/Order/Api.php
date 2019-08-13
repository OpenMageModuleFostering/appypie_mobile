<?php
/*
 * Company : ONS Interactive Pvt. Ltd
 * Version : 1.1
 * Author : Ruchi Kapil
 *
 */
class Appypie_Mobile_Model_Sales_Order_Api extends Mage_Sales_Model_Api_Resource
{
    /**
     * Initialize attributes map
     */
    public function __construct()
    {
        $this->_attributesMap = array(
            'order' => array('order_id' => 'entity_id'),
            'order_address' => array('address_id' => 'entity_id'),
            'order_payment' => array('payment_id' => 'entity_id')
        );
    }

    /**
     * Initialize basic order model
     *
     * @param mixed $orderIncrementId
     * @return Mage_Sales_Model_Order
     */
    protected function _initOrder($orderIncrementId)
    {
        $order = Mage::getModel('sales/order');

        /* @var $order Mage_Sales_Model_Order */

        $order->loadByIncrementId($orderIncrementId);

        if (!$order->getId()) {
            $this->_fault('not_exists');
        }

        return $order;
    }

    /**
     * Retrieve list of orders. Filtration could be applied
     *
     * @param null|object|array $filters
     * @return array
     */
    public function items($filters = null)
    {
		$filters = json_decode($filters,true);
		$customer = Mage::getModel('customer/customer');
        $customer->setWebsiteId($filters['websiteId']);
		$customer->loadByEmail($filters['email']);
        $orders = array();

        //TODO: add full name logic
        $billingAliasName = 'billing_o_a';
        $shippingAliasName = 'shipping_o_a';

        /** @var $orderCollection Mage_Sales_Model_Mysql4_Order_Collection */
        $orderCollection = Mage::getModel("sales/order")->getCollection();
        $billingFirstnameField = "$billingAliasName.firstname";
        $billingLastnameField = "$billingAliasName.lastname";
        $shippingFirstnameField = "$shippingAliasName.firstname";
        $shippingLastnameField = "$shippingAliasName.lastname";
        $orderCollection->addAttributeToSelect('*')
            ->addAddressFields()
            ->addExpressionFieldToSelect('billing_firstname', "{{billing_firstname}}",
                array('billing_firstname' => $billingFirstnameField))
            ->addExpressionFieldToSelect('billing_lastname', "{{billing_lastname}}",
                array('billing_lastname' => $billingLastnameField))
            ->addExpressionFieldToSelect('shipping_firstname', "{{shipping_firstname}}",
                array('shipping_firstname' => $shippingFirstnameField))
            ->addExpressionFieldToSelect('shipping_lastname', "{{shipping_lastname}}",
                array('shipping_lastname' => $shippingLastnameField))
            ->addExpressionFieldToSelect('billing_name', "CONCAT({{billing_firstname}}, ' ', {{billing_lastname}})",
                array('billing_firstname' => $billingFirstnameField, 'billing_lastname' => $billingLastnameField))
            ->addExpressionFieldToSelect('shipping_name', 'CONCAT({{shipping_firstname}}, " ", {{shipping_lastname}})',
                array('shipping_firstname' => $shippingFirstnameField, 'shipping_lastname' => $shippingLastnameField)
        );

        /** @var $apiHelper Mage_Api_Helper_Data */
        try {
            //foreach ($filters as $field => $value) {
                //$orderCollection->addFieldToFilter($field, $value);
                $orderCollection->addFieldToFilter('customer_id', array('eq'=>$customer->getId()));
            //}
            $orderCollection->addAttributeToSort('created_at','desc');
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }
        $i=0;
        foreach ($orderCollection as $order) {
            //$orders[] = $this->_getAttributes($order, 'order');
           	$orders[$i]['status'] = $order->status;
           	$orders[$i]['coupon_code'] = $order->coupon_code;
           	$orders[$i]['shipping_description'] = $order->shipping_description;
           	$orders[$i]['customer_id'] = $order->customer_id;
           	$orders[$i]['discount_amount'] = $order->discount_amount;
           	$orders[$i]['shipping_amount'] = $order->shipping_amount;
           	$orders[$i]['tax_amount'] = $order->tax_amount;
           	$orders[$i]['subtotal'] = $order->subtotal;
           	$orders[$i]['grand_total'] = $order->grand_total;
           	$orders[$i]['total_qty_ordered'] = $order->total_qty_ordered;
           	$orders[$i]['order_currency_code'] = $order->order_currency_code;
           	$orders[$i]['customer_firstname'] = $order->customer_firstname;
           	$orders[$i]['customer_lastname'] = $order->customer_lastname;
           	$orders[$i]['increment_id'] = $order->increment_id;
           	$orders[$i]['order_id'] = $order->entity_id;
           	$orders[$i]['created_at'] = date("d/m/y",strtotime($order->created_at));
           	$i++;
        }
        return $orders;
    }

    /**
     * Retrieve full order information
     *
     * @param string $orderIncrementId
     * @return array
     */
    public function info($orderIncrementId)
    {
        $order = $this->_initOrder($orderIncrementId);

        if ($order->getGiftMessageId() > 0) {
            $order->setGiftMessage(
                Mage::getSingleton('giftmessage/message')->load($order->getGiftMessageId())->getMessage()
            );
        }

        $result = $this->_getAttributes($order, 'order');

        $result['shipping_address'] = $this->_getAttributes($order->getShippingAddress(), 'order_address');
        $result['shipping_address']['country_name'] = Mage::getModel('directory/country')->load($result['shipping_address']['country_id'])->getName();
        $result['billing_address']  = $this->_getAttributes($order->getBillingAddress(), 'order_address');
        $result['billing_address']['country_name'] = Mage::getModel('directory/country')->load($result['billing_address']['country_id'])->getName();
        $result['items'] = array();
		$i=0;
        foreach ($order->getAllItems() as $item) {
            if ($item->getGiftMessageId() > 0) {
                $item->setGiftMessage(
                    Mage::getSingleton('giftmessage/message')->load($item->getGiftMessageId())->getMessage()
                );
            }

            //$result['items'] = $this->_getAttributes($item, 'order_item');
            if($item->parent_item_id =="" || $item->parent_item_id <= 0)
            {
				$result['items'][$i]['item_id'] = $item->item_id;
				$result['items'][$i]['order_id'] = $item->order_id;
				$result['items'][$i]['parent_item_id'] = $item->parent_item_id;
				$result['items'][$i]['quote_item_id'] = $item->quote_item_id;
				$result['items'][$i]['store_id'] = $item->store_id;
				$result['items'][$i]['product_id'] = $item->product_id;
				$result['items'][$i]['product_type'] = $item->product_type;
				$result['items'][$i]['product_options'] = unserialize($item->product_options);
				$result['items'][$i]['weight'] = $item->weight;
				$result['items'][$i]['sku'] = $item->sku;
				$result['items'][$i]['name'] = $item->name;
				$result['items'][$i]['description'] = $item->description;
				$result['items'][$i]['qty_ordered'] = $item->qty_ordered;
				$result['items'][$i]['qty_canceled'] = $item->qty_canceled;
				$result['items'][$i]['qty_invoiced'] = $item->qty_invoiced;
				$result['items'][$i]['price'] = $item->price;
				$result['items'][$i]['base_price'] = $item->base_price;
				$result['items'][$i]['tax_amount'] = $item->tax_amount;
				$result['items'][$i]['discount_amount'] = $item->discount_amount;
				$result['items'][$i]['amount_refunded'] = $item->amount_refunded;
				$result['items'][$i]['row_total'] = $item->row_total;
				$i++;
			}
        }

        $result['payment'] = $this->_getAttributes($order->getPayment(), 'order_payment');

        $result['status_history'] = array();

        foreach ($order->getAllStatusHistory() as $history) {
            $result['status_history'][] = $this->_getAttributes($history, 'order_status_history');
        }

        return $result;
    }

    /**
     * Add comment to order
     *
     * @param string $orderIncrementId
     * @param string $status
     * @param string $comment
     * @param boolean $notify
     * @return boolean
     */
    public function addComment($orderIncrementId, $status, $comment = null, $notify = false)
    {
        $order = $this->_initOrder($orderIncrementId);

        $order->addStatusToHistory($status, $comment, $notify);


        try {
            if ($notify && $comment) {
                $oldStore = Mage::getDesign()->getStore();
                $oldArea = Mage::getDesign()->getArea();
                Mage::getDesign()->setStore($order->getStoreId());
                Mage::getDesign()->setArea('frontend');
            }

            $order->save();
            $order->sendOrderUpdateEmail($notify, $comment);
            if ($notify && $comment) {
                Mage::getDesign()->setStore($oldStore);
                Mage::getDesign()->setArea($oldArea);
            }

        } catch (Mage_Core_Exception $e) {
            $this->_fault('status_not_changed', $e->getMessage());
        }

        return true;
    }

    /**
     * Hold order
     *
     * @param string $orderIncrementId
     * @return boolean
     */
    public function hold($orderIncrementId)
    {
        $order = $this->_initOrder($orderIncrementId);

        try {
            $order->hold();
            $order->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('status_not_changed', $e->getMessage());
        }

        return true;
    }

    /**
     * Unhold order
     *
     * @param string $orderIncrementId
     * @return boolean
     */
    public function unhold($orderIncrementId)
    {
        $order = $this->_initOrder($orderIncrementId);

        try {
            $order->unhold();
            $order->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('status_not_changed', $e->getMessage());
        }

        return true;
    }

    /**
     * Cancel order
     *
     * @param string $orderIncrementId
     * @return boolean
     */
    public function cancel($orderIncrementId)
    {
        $order = $this->_initOrder($orderIncrementId);

        if (Mage_Sales_Model_Order::STATE_CANCELED == $order->getState()) {
            $this->_fault('status_not_changed');
        }
        try {
            $order->cancel();
            $order->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('status_not_changed', $e->getMessage());
        }
        if (Mage_Sales_Model_Order::STATE_CANCELED != $order->getState()) {
            $this->_fault('status_not_changed');
        }
        return true;
    }

} // Class Mage_Sales_Model_Order_Api End
