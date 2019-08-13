<?php
/*
 * Company : ONS Interactive Pvt. Ltd
 * Version : 1.1
 * Author : Ruchi Kapil
 *
 */
class Appypie_Mobile_Model_Directory_Api extends Mage_Api_Model_Resource_Abstract
{
	/**
     * Retrieve countries list
     *
     * @return string
     */
    public function countrylist()
    {
		$countrylist = Mage::getStoreConfig('general/country/allow');
		$clist = explode(",",$countrylist);
		$collection = Mage::getModel('directory/country')->getCollection();
        $collection->addFieldToFilter('country_id', array('IN' => array($clist)));

        $result = array();
        foreach ($collection as $country) {
            /* @var $country Mage_Directory_Model_Country */
            $country->getName(); // Loading name in default locale
            $result[] = $country->toArray(array('country_id', 'iso2_code', 'iso3_code', 'name'));
        }

        return $result;
    }

}
?>
