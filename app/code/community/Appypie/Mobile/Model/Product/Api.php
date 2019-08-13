<?php
/*
 * Company : ONS Interactive Pvt. Ltd
 * Version : 1.1
 * Author : Ruchi Kapil
 *
 */
class Appypie_Mobile_Model_Product_Api extends Mage_Catalog_Model_Api_Resource
{
	/**
     * @param string $productId
     * @return String
    */
    public function productdetail($productData)
    {
		$productData = json_decode($productData,true );
		$productId = $productData['product_id'];
		//return $productId;
		$product = Mage::getModel('catalog/product')->load($productId);
		//return $product;
		$wishlist = Mage::getModel('wishlist/item')->load($productId,'product_id');
		$productId = $productData['product_id'];
		$product = Mage::getModel('catalog/product')->load($productId);
		$wishlist = Mage::getModel('wishlist/item')->load($productId,'product_id');
		$arr = array();
		$query = "SELECT * from db_affiliate_advertisers WHERE advertiser_id = " . $product->getAdvertiserId();
		$read = Mage::getSingleton( 'core/resource' )->getConnection( 'core_read' );
		//return $query;
			$advertiserResult = $read->fetchAll( $query  );

$imageUrl = '';
$advertisername= '';
foreach( $advertiserResult as $advertiserValue )
{
	$imageUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). $advertiserValue['advertiser_logo'];
	$advertisername =  $advertiserValue['advertiser_name'];
}

$arr['advertiser_logo'] = $imageUrl;
$arr['advertiser_name'] = $advertisername;
		$arr['product_id'] = $product->getId();
		$arr['name'] = $product->getName();
		$arr['sku'] = $product->getSku();
		$arr['price'] = $product->getPrice();
		$arr['special_price'] = $product->getSpecialPrice();
		$arr[]['special_price_form_date'] = $product->getSpecialFromDate();
		$arr[]['special_price_to_date'] = $product->getSpecialToDate();
		$arr['thumbnail'] = (string)Mage::helper('catalog/image')->init($product, 'image')->constrainOnly(true)->keepAspectRatio(true)->keepFrame(false)->resize(150);
		$imageArray = array();

		if(count($product->getMediaGalleryImages()) > 0)
		{
			foreach($product->getMediaGalleryImages() as $_image)
			{
				$imageArray[] = $_image->url;
			}
			$imageArray[] =  Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). 'catalog' . DS . 'product'.$product->getImage();
		}
		else
		{
		 $imageArray[] =  Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). 'catalog' . DS . 'product'.$product->getImage();
		}
		$arr['image'] = implode(",",$imageArray);
		$arr['description'] = $product->getDescription();
		$arr['manage_stock'] = $product->getStockItem()->getManageStock();
		$arr['qty'] = (int) $product->getStockItem()->getQty();
		$arr['min_qty'] = $product->getStockItem()->getMinQty();
		$arr['min_sale_qty'] = $product->getStockItem()->getMinSaleQty();
		$arr['is_in_stock'] = $product->getStockItem()->getIsInStock();
		$arr['is_salable'] = $product->getIsSalable();
		$arr['type_id'] = $product->getTypeId();
		$arr['created_date'] = $product->getCreatedAt();
		$arr['created_day'] =  $product->getAttributeText('product_created_day');
		$arr['updated_date'] = $product->getUpdatedAt();
		$arr['price_update_day'] = $product->getAttributeText('price_update_day');
		$arr['price_update_date'] =  $product->getAttributeText('price_update_date');
		 $collection = Mage::getModel('brands/brands')->getCollection()->addFieldToFilter('brands_attribute_id',$product->getBrands());
		$brandData=$collection->getData();
		if( $brandData[0]['filename'] != '' )
		{
		    $arr['brand_image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). $brandData[0]['filename'];
		}
		else
		{
			$arr['brand_image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). 'brands/brand.jpg';
		}
		$arr['brand_name'] = $brandData[0]['title'];
		$wishList = Mage::getSingleton('wishlist/wishlist')->loadByCustomer($productData['customer_id']);
		$wishListItemCollection = $wishList->getItemCollection()->addFieldToFilter('product_id',$productData['product_id']);
		$select = '';
		$select = $select . $wishListItemCollection->getSelect() .'';

		if( count( $wishListItemCollection ) )
		{
			foreach( $wishListItemCollection as $wishValue )
			{
			  $arr['wishlist']['status'] =  1;
			  $arr['wishlist']['item_id'] =  $wishValue->getWishlistItemId();
		    }
		}
		else
		{
			$arr['wishlist']['status'] =  0;
		}
		$attributes = $this->items($product->getAttributeSetId());
		foreach($attributes as $_attribute){
			if($_attribute['used_in_mobile_listing']==1)
			{
				$arx = explode("_",$_attribute['code']);
				$code = "get";
				foreach($arx as $attrCode)
				{
					$code .= ucfirst($attrCode);
				}
				if($_attribute['type']=='select')
				{
					// Get product attribute options from attribute code if attribute type is select//
					$attribute = Mage::getModel('eav/config')->getAttribute('catalog_product',$_attribute['code']);
					foreach($attribute->getSource()->getAllOptions(true,true) as $option){
						// Get product attribute option label from product attribute code //
						if($option['value'] == $product->$code())
						{
							$arr['addinfo'][$_attribute['code']] = $option['label'];
						}
					}
				}else{

					// Get product attribute label from product attribute code if attribute type is text/textarea //
					$arr['addinfo'][$_attribute['code']] = $product->$code();
				}
			}
		}
		if($product->getTypeId()=="configurable")
		{
			$childProducts = Mage::getModel('catalog/product_type_configurable')
                    ->getUsedProducts(null,$product);
			$productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
			$attributeOptions = array();
			foreach ($productAttributeOptions as $productAttribute) {
				$arr['dropdown'][$productAttribute['attribute_id']]['label'] = $productAttribute['label'];
				$arr['dropdown'][$productAttribute['attribute_id']]['code'] = $productAttribute['attribute_code'];
				foreach ($productAttribute['values'] as $attribute) {
					$arr['dropdown'][$productAttribute['attribute_id']][$attribute['value_index']] = $attribute['store_label']."###".$attribute['is_percent']."###".$attribute['pricing_value'];
					//$arr['dropdown'][$productAttribute['attribute_id']][$attribute['value_index']] = $attribute;

				}
			}

			foreach($childProducts as $cproduct)
			{
				foreach ($productAttributeOptions as $productAttribute) {
					$acode = 'get'.ucfirst($productAttribute['attribute_code']);
					$arr['dp'][$cproduct->getSku()][$cproduct->$acode()]=$cproduct->$acode();

				}
				$arr['dp'][$cproduct->getSku()]['qty']=$cproduct->getStockItem()->getQty();
			}
		}
		$currentDate = strtotime(date('m/d/Y'));
		if( ( $product->getCouponStartDate() != '' && $product->getCouponEndDate() != '' ) && ( $product->getCouponStartDate() != null && $product->getCouponEndDate() != null ))
		{
		$startDate = strtotime($product->getCouponStartDate());
		$endDate = strtotime($product->getCouponEndDate());
		}
		else
		{
			$startDate = 0 ;
			$endDate = 0;
		}
		if( ($currentDate >= $startDate) && ( $currentDate <= $endDate )  )
		{
			$arr['coupon_name'] = $product->getCouponCode();
			$arr['coupon_description'] = $product->getCouponDescription();
			$arr['coupon_start_date'] = $product->getCouponStartDate();
			$arr['coupon_end_date'] = $product->getCouponEndDate();
		}
		else
		{
			$arr['coupon_name'] = '';
			$arr['coupon_description'] = '';
			$arr['coupon_start_date'] = '';
			$arr['coupon_end_date'] = '';
		}
		if( ( $product->getSiteOfferStartDate() != '' && $product->getSiteOfferEndDate() != '' ) && ( $product->getSiteOfferStartDate() != null && $product->getSiteOfferEndDate() != null ))
		{
			$startDate = strtotime($product->getSiteOfferStartDate());
			$endDate = strtotime($product->getSiteOfferEndDate());
		}
		else
		{
			$startDate = 0 ;
			$endDate = 0;
		}
		if(($currentDate>$startDate) && ( $currentDate < $endDate )  )
		{
			$arr['site_offer_description'] = $product->getSiteOfferDescription();
			$arr['site_offer_end_date'] = $product->getSiteOfferEndDate();
			$arr['site_offer_start_date'] = $product->getSiteOfferStartDate();
		}
		else
		{
			$arr['site_offer_description'] = '';
			$arr['site_offer_end_date'] ='';
			$arr['site_offer_start_date'] = '';
		}

		$arr['advertiser_buy_link'] =  $product->getAdvertiserBuyLink();
		$productId = $product->getId();
		$reviews = Mage::getModel('review/review')
			->getResourceCollection()
			->addStoreFilter(Mage::app()->getStore()->getId())
			->addEntityFilter('product', $productId)
			->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED)
			->setDateOrder()
			->addRateVotes();
			$avg = 0;
			$ratings = array();
			$i=0;
			if (count($reviews) > 0) {
			foreach ($reviews->getItems() as $review) {

				$arr['review'][$i]['title'] = $review->getTitle();
				$arr['review'][$i]['detail'] = $review->getDetail();
				$arr['review'][$i]['nickname'] = $review->getNickname();
			$j=0;
			foreach( $review->getRatingVotes() as $vote ) {
			$arr['review'][$i]['vote'][$j]['rating_code'] =$vote->getRatingCode();
			$arr['review'][$i]['vote'][$j]['value'] =  $vote->getValue();
			$j++;
			}
			$i++;
			}
		}
		else
		{
			$arr['review'] = array();
		}

		return json_encode( $arr );
	}
	 /**
     * Retrieve attributes from specified attribute set
     *
     * @param int $setId
     * @return array
     */
    public function items($setId)
    {
        $attributes = Mage::getModel('catalog/product')->getResource()
                ->loadAllAttributes()
                ->getSortedAttributes($setId);
        $result = array();
        foreach ($attributes as $attribute) {
            /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
            if ((!$attribute->getId() || $attribute->isInSet($setId))
                    && $this->_isAllowedAttribute($attribute)) {
                if (!$attribute->getId() || $attribute->isScopeGlobal()) {
                    $scope = 'global';
                } elseif ($attribute->isScopeWebsite()) {
                    $scope = 'website';
                } else {
                    $scope = 'store';
                }
                $result[] = array(
                    'attribute_id' => $attribute->getId(),
                    'code' => $attribute->getAttributeCode(),
                    'type' => $attribute->getFrontendInput(),
                    'required' => $attribute->getIsRequired(),
                    'scope' => $scope,
                    'used_in_mobile_listing' => $attribute->getUsedInMobileListing(),
                );
            }
        }
        return $result;
    }
    /**
     * Retrieve list of products with basic info (id, sku, type, set, name)
     *
     * @param null|object|array $filters
     * @param string|int $store
     * @return array
     */
    public function productlist($filters = null, $store = null)
    {
		$filters = json_decode($filters,true);
		$filter = explode(" ",$filters['name']);
		$str = array('name' => array('like'=>'%'.$filters['name'].'%'));
		$sort = $filters['sort'];
		$sortype = $filters['sort_type'];
		$minprice = $filters['min_price'];
		$maxprice = $filters['max_price'];
		$page = $filters['page'];
		//==== Get product ids filter by keyword ====//
		$result = $this->prodfilterlist($str,$store);
		 //sku
		$str = array('sku' => array('like'=>'%'.$filters['name'].'%'));
		$result = array_merge($result,$this->prodfilterlist($str,$store));

		if(count($filter) > 0)
		{
			foreach($filter as $filtermore)
			{
				$str = array('name' => array('like'=>'%'.$filtermore.'%'));
				//==== Get product ids filter by keyword ====//
				$result = array_merge($result,$this->prodfilterlist($str,$store));

				 //sku
				$str = array('sku' => array('like'=>'%'.$filtermore.'%'));
				$result = array_merge($result,$this->prodfilterlist($str,$store));
			}
		}
		//==== Remove duplicate product id if any ====//
		array_unique($result);
		//==== Get product data of product ids filter and sort by given parameter ====//
		$results = $this->prodlist($result, $store,$sort,$sortype,$minprice,$maxprice,$page);
		return $results;
	}
	/**
     * Retrieve list of products with basic info (id, sku, name, description, image, price)
     *
     * @param null|object|array $filters
     * @param string|int $store
     * @return array
     */
    public function prodfilterlist($filters = null, $store = null)
    {
		$collection = Mage::getModel('catalog/product')->getCollection()
            ->addStoreFilter($this->_getStoreId($store));
        try {
            foreach ($filters as $field => $value) {
                $collection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }
        $collection->addAttributeToFilter('status', 1);
		$collection->addAttributeToFilter('visibility', 4);
        $result = array();
        foreach ($collection as $product) {
            $result[] =  $product->getId();
        }
        return $result;
	}
	/**
     * Retrieve list of products with basic info (id, sku, name, description, image, price)
     *
     * @param null|object|array $filters
     * @param string|int $store
     * @return array
     */
    public function prodlist($filters = null, $store = null,$sort = null,$sortype = null,$minprice = null,$maxprice = null, $page = 1)
    {
		//==== Get record per page show ====//
		$configPageValue = Mage::getStoreConfig('mobile/general/productperpage');
		$page = empty($page) ? 1 : $page;
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addStoreFilter($this->_getStoreId($store))

            ->addAttributeToSelect('name')
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('description')
		->addAttributeToSelect('short_description')
            ->addAttributeToSelect('price')
            ->addAttributeToSelect('special_price')
            ->addAttributeToSelect('small_image')
           ->joinField('inventory_in_stock', 'cataloginventory_stock_item', 'is_in_stock', 'product_id=entity_id','is_in_stock>=1', 'inner');
        try {
			//==== Filter by product id get from search keyword ====//
			$collection->addAttributeToFilter('entity_id', array('in'=>$filters));
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }
        //==== Set Price range filter ====//
        if($maxprice!="")
        {
			$collection->addAttributeToFilter('price',array('gteq' => $minprice));
			$collection->addAttributeToFilter('price',array('lteq' => $maxprice));
		}else{
			$collection->addAttributeToFilter('price',array('gt' => 0));
		}
		if($sort!="")
        {
			if($sortype==""){ $sortype = 'asc';	}
			$collection->addAttributeToSort($sort, $sortype);
		}
		//==== Set page number and record per page ====//
		$collection->setPageSize($configPageValue);
		$collection->setCurPage($page);
		//==== Count total record ====//
		$collectionCount = Mage::getModel('catalog/product')->getCollection()
			->addStoreFilter($this->_getStoreId($store))
            ->addAttributeToSelect('name');
        $collectionCount->addAttributeToFilter('entity_id', array('in'=>$filters));
         //==== Set Price range filter ====//
        if($maxprice!="")
        {
			$collectionCount->addAttributeToFilter('price',array('gteq' => $minprice));
			$collectionCount->addAttributeToFilter('price',array('lteq' => $maxprice));
		}else{
			$collectionCount->addAttributeToFilter('price',array('gt' => 0));
		}
        $totalCount = count($collectionCount);
		$result = array();
		$result['total'] = $totalCount;
        foreach ($collection as $product) {
            $result[] = array(
                'product_id' => $product->getId(),
                'sku'        => $product->getSku(),
                'name'       => $product->getName(),
                'price'       => $product->getPrice(),
                'special_price'       => $product->getSpecialPrice(),
                'description'       => $product->getDescription(),
		'short_description'       => $product->getShortDescription(),
		'currency'	=> Mage::app()->getStore()->getCurrentCurrencyCode(),
                'thumbnail' => (string)Mage::helper('catalog/image')->init($product, 'small_image')->resize(135),

            );
        }
        return $result;
    }
    public function newproductlist($page = 1)
    {
		$configPageValue = Mage::getStoreConfig('mobile/general/productperpage');
		$page = empty($page) ? 1 : $page;
		$todayStartOfDayDate  = Mage::app()->getLocale()->date()
            ->setTime('00:00:00')
            ->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

        $todayEndOfDayDate  = Mage::app()->getLocale()->date()
            ->setTime('23:59:59')
            ->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addStoreFilter($this->_getStoreId($store))

            ->addAttributeToFilter('news_from_date', array('or'=> array(
                0 => array('date' => true, 'to' => $todayEndOfDayDate),
                1 => array('is' => new Zend_Db_Expr('null')))
            ), 'left')
            ->addAttributeToFilter('news_to_date', array('or'=> array(
                0 => array('date' => true, 'from' => $todayStartOfDayDate),
                1 => array('is' => new Zend_Db_Expr('null')))
            ), 'left')
            ->addAttributeToFilter(
                array(
                    array('attribute' => 'news_from_date', 'is'=>new Zend_Db_Expr('not null')),
                    array('attribute' => 'news_to_date', 'is'=>new Zend_Db_Expr('not null'))
                    )
              )
              ->joinField('inventory_in_stock', 'cataloginventory_stock_item', 'is_in_stock', 'product_id=entity_id','is_in_stock>=1', 'inner')
             ->addAttributeToSelect('name')
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('description')
            ->addAttributeToSelect('price')
            ->addAttributeToSelect('special_price')
            ->addAttributeToSelect('small_image')
            ->addAttributeToSort('news_from_date', 'desc')
            ->setPageSize($configPageValue)
			->setCurPage($page);

         $collectionCount = Mage::getModel('catalog/product')->getCollection()
			 ->addStoreFilter($this->_getStoreId($store))
			 ->joinField('inventory_in_stock', 'cataloginventory_stock_item', 'is_in_stock', 'product_id=entity_id','is_in_stock>=1', 'inner')
            ->addAttributeToSelect('name');
         $collectionCount->addAttributeToFilter('news_from_date', array('or'=> array(
                0 => array('date' => true, 'to' => $todayEndOfDayDate),
                1 => array('is' => new Zend_Db_Expr('null')))
            ), 'left')
            ->addAttributeToFilter('news_to_date', array('or'=> array(
                0 => array('date' => true, 'from' => $todayStartOfDayDate),
                1 => array('is' => new Zend_Db_Expr('null')))
            ), 'left')
            ->addAttributeToFilter(
                array(
                    array('attribute' => 'news_from_date', 'is'=>new Zend_Db_Expr('not null')),
                    array('attribute' => 'news_to_date', 'is'=>new Zend_Db_Expr('not null'))
                    )
              );
        $totalCount = count($collectionCount);
		$result = array();
		$result['total'] = $totalCount;
		foreach ($collection as $product) {
            $result[] = array(
                'product_id' => $product->getId(),
                'sku'        => $product->getSku(),
                'name'       => $product->getName(),
                'price'       => $product->getPrice(),
                'special_price'       => $product->getSpecialPrice(),
                'description'       => $product->getDescription(),
                'thumbnail' => (string)Mage::helper('catalog/image')->init($product, 'small_image')->resize(135),

            );
        }
        return $result;
	}

	public function getCategoryName($categoryId)
	{
		$categories = Mage::getModel('catalog/category')->load($categoryId);
		return $categories->getName(). '('.$categories->getProductCount().')';

	}
	/**
     * @return array
     */
	public function getcurrency()
	{
		$arr['currency_symbol'] = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
		$arr['currency_code'] = Mage::app()->getStore()->getCurrentCurrencyCode();
		return $arr;
	}


/* function for geting brands list added by Ruchi Kapil*/

	public function brandslist($brandsData)
	{

		$brandsData = json_decode($brandsData,true);
		$brandsCustomer = array();
		if(isset($brandsData['customer']))
		{
		$collection = Mage::getModel('brands/brandscustomer')->getCollection()->addFieldToFilter('customer_id',trim($brandsData['customer']))->addFieldToFilter('status',1);
			foreach( $collection as $key => $value )
			{
				$brandsCustomer[] = $value->getBrandsId();
			}
		}

		$brandsArray = array();
		$i = 0;
		$k =0;
		 $_categories = Mage::getModel('catalog/category')
        ->getCollection()
        ->addAttributeToSelect('*')
        ->addLevelFilter(2)
        ->addIsActiveFilter();
		$categoryArray = array(''=>'Select Category');
		foreach ($_categories as $_category) {
		$categoryArray[$_category->getId()] = $_category->getName();
		}

		$brandsCollection = Mage::getModel('brands/brands')->getCollection()->addFieldToFilter('status',1)->setOrder('title', 'ASC');
		foreach($brandsCollection as $value )
		{
			if( $categoryArray[$value->getCategory()] == 'Male')
			{
			$brandsArray['Male'][$i]['title'] = $value->getTitle();
			$brandsArray['Male'][$i]['brands_id'] = $value->getBrandsId();
			$brandsArray['Male'][$i]['filename'] = $value->getFilename();
			$brandsArray['Male'][$i]['brands_attribute_id'] = $value->getBrandsAttributeId();
			$brandsArray['Male'][$i]['category'] = $value->getCategory();
			if( in_array($value->getBrandsId(),$brandsCustomer) )
			{
				$brandsArray['Male'][$i]['brand_selected'] = '1';
			}
			else
			{
				$brandsArray['Male'][$i]['brand_selected'] = '0';
			}
			$i++;
			}
			elseif( $categoryArray[$value->getCategory()] == 'Female')
			{
			$brandsArray['Female'][$k]['title'] = $value->getTitle();
			$brandsArray['Female'][$k]['brands_id'] = $value->getBrandsId();
			$brandsArray['Female'][$k]['filename'] = $value->getFilename();
			$brandsArray['Female'][$k]['brands_attribute_id'] = $value->getBrandsAttributeId();
			$brandsArray['Female'][$k]['category'] = $value->getCategory();
				if( in_array($value->getBrandsId(),$brandsCustomer) )
				{
					$brandsArray['Female'][$k]['brand_selected'] = '1';
				}
				else
				{
					$brandsArray['Female'][$k][$i]['brand_selected'] = '0';
				}
			 $k++;
			}
		}
		return json_encode($brandsArray);
	}


/* function for save brands of customer  added by Ruchi Kapil*/

	public function savebrandscustomer( $brandsData )
	{
		$brandsArray = json_decode($brandsData,true);
		if( isset($brandsArray['customer']))
		{
		$collection = Mage::getModel('brands/brandscustomer')->getCollection()->addFieldToFilter('customer_id',trim($brandsArray['customer']))->addFieldToFilter('brands_gender',trim($brandsArray['gender']));
		$brandsExist = array();
		$returnvariable = '';
		foreach( $collection as $key => $values )
		{
			$brandsExist[$values->getBrandsId()] = $values->getBrandsCustomerId();
			$model = Mage::getModel('brands/brandscustomer');
			$data['status'] = 0;
			$model->setData($data)
				  ->setId($values->getBrandsCustomerId());
			try {
				if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
				$model->setCreatedTime(now())
					->setUpdateTime(now());
			} else {
				$model->setUpdateTime(now());
			}
			$model->save();
		}
		catch(Exception $e)
			{}
		}

		$returnArray = array();
		$i = 0;
		foreach( $brandsArray['brands'] as $key => $value )
		{
			$data = array();
			$model = Mage::getModel('brands/brandscustomer');
			$data['customer_id'] = $brandsArray['customer'];
			$data['brands_id'] = $value;
			$data['status'] = 1;
			$data['brands_gender'] = $brandsArray['gender'];
			$model->setData($data);
			if(array_key_exists( $value, $brandsExist ) )
			{
				$model->setId($brandsExist[$value]);
			}

			try {
				if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
					$model->setCreatedTime(now())
						->setUpdateTime(now());
				} else {
					$model->setUpdateTime(now());
				}
			}
			catch(Exception $e)
			{}
		      $model->save();

		$returnArray[$i] =$model->getBrandsCustomerId();

		$i++;
		}
//return $returnArray;
		return json_encode($returnArray);
		}
		else
		{
			return "Please login first";
		}

	}

/* function for geting product list according to brands added by Ruchi Kapil*/

    public function brandsproductlist($brandsData)
	{
		$read = Mage::getSingleton( 'core/resource' )->getConnection( 'core_read' );
		$brandsData = json_decode($brandsData,true);

		Mage::app()->setCurrentStore($brandsData['website_id']);

		if( isset( $brandsData['limit'] ) )
		{
			$limit = $brandsData['limit'];
		}
		else
		{
			$limit = Mage::getStoreConfig('mobile/general/productperpage');
		}

		$category = Mage::getResourceModel('catalog/category_collection')->addFieldToFilter('name', $brandsData['gender']);
		$cat_det=$category->getData();
		$categoryArray =array( $cat_det[0]['entity_id'] );
		$brandsAttributeArray = array();
		if( isset( $brandsData['brands_id'] ) && (count( $brandsData['brands_id'] )   ) )
		{

			$sql = "Select br.brands_id,br.brands_attribute_id,br.title,br.filename from db_brands br where br.status = 1 AND br.brands_id in (".implode(',',$brandsData['brands_id']).")  AND br.category=".$categoryArray[0] . " AND br.status = 1 ";
					$resultSql = $read->query( $sql);
					$i=0;
					while ( $sqldata = $resultSql->fetch() ) {
						$brandsAttributeArray[] = $sqldata['brands_attribute_id'] ;
						$brandsNameArray[$sqldata['brands_attribute_id']]['title'] = $sqldata['title'];
						if( $sqldata['filename'] != '' )
						{
						$brandsNameArray[$sqldata['brands_attribute_id']]['filename'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). $sqldata['filename'];
						}
						else
						{
							$brandsNameArray[$sqldata['brands_attribute_id']]['filename'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). 'brands/brand.jpg';
						}
					   }
		}
		else
		{
				if(  ( !isset($brandsData['customer_id'] ) ) || $brandsData['customer_id'] == ''   )
				{
						$brandsCollection = Mage::getModel('brands/brands')->getCollection()->addFieldToFilter('category',$cat_det[0]['entity_id'])->addFieldToFilter('status',1);
						$brandsString = '';
						foreach( $brandsCollection as $value )
						{
							$brandsAttributeArray[] = $value->getBrandsAttributeId();
							$brandsNameArray[ $value->getBrandsAttributeId()]['title'] =  $value->getTitle();
							if( $value->getFilename() != '' )
							{
							$brandsNameArray[$sqldata['brands_attribute_id']]['filename'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). $value->getFilename();
							}
							else
							{
								$brandsNameArray[$sqldata['brands_attribute_id']]['filename'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). 'brands/brand.jpg';
							}
						}
				}
				else
				{

					$sql = "Select br.brands_id,br.brands_attribute_id,br.title,br.filename FROM db_brands br Inner Join ( Select brands_id from db_brandscustomer where customer_id  =  " .$brandsData['customer_id'] ." AND status = 1   ) cus ON br.brands_id = cus.brands_id where br.status = 1  AND br.category=".$categoryArray[0];

					$resultSql = $read->query( $sql);
					$i=0;
					$categoryArray = array();
					while ( $sqldata = $resultSql->fetch() ) {
						$brandsAttributeArray[] = $sqldata['brands_attribute_id'] ;
						$brandsNameArray[$sqldata['brands_attribute_id']]['title'] = $sqldata['title'];
						if( $sqldata['filename'] != '' )
						{
						$brandsNameArray[$sqldata['brands_attribute_id']]['filename'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). $sqldata['filename'];
						}
						else
						{
							$brandsNameArray[$sqldata['brands_attribute_id']]['filename'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). 'brands/brand.jpg';
						}
					   }
				}
		}

		if( isset( $brandsData['category_ids'] ) && (count($brandsData['category_ids']) ) )
		{
			$categoryArray[0] =$brandsData['category_ids'];
		}
		$wishList = Mage::getSingleton('wishlist/wishlist')->loadByCustomer($brandsData['customer_id']);
		$wishListItemCollection = $wishList->getItemCollection();
		$select = '';
		//return $select = $select . $wishListItemCollection->getSelect() .$brandsData['customer_id'];
		$wishListArray = array();
	    foreach(  $wishListItemCollection as $values )
	    {
			 if( !array_key_exists($values->getProductId(),$wishListArray) )
			 {
				$wishListArray[$values->getProductId()] =$values->getWishlistItemId();
			 }
		}
//return $wishListArray;
		$productCollection = Mage::getResourceModel('catalog/product_collection')->addAttributeToSelect('*')
							->addAttributeToFilter('status', '1')
							->addAttributeToFilter('visibility', 4)
							->addAttributeToFilter('canonical_id', array('neq' => 'NULL' ))
							 ->joinField('category_id',
							'catalog/category_product',
							'category_id',
							'product_id=entity_id',
							null,
							'left')
						->addAttributeToFilter('category_id', array('in' => $categoryArray));
		$productCollection->getSelect()->group('e.entity_id');
	//	return '' . $productCollection->getSelect();
		if( count($brandsAttributeArray) )
		{
			$productCollection->addAttributeToFilter('brands',  array('in' => $brandsAttributeArray ));

		}
		$start = 0;
		if( $brandsData['page'] == 1 )
		{
			$start = 0;
			$limit = $limit;
		}
		else
		{
			$start = ( ( $brandsData['page']-1 ) * $limit );
			$limit = $limit;
		}

		$productCollection->getSelect()->limit($limit,$start);
		Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($productCollection);
		Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($productCollection);
		if( isset($brandsData['filter']) && ( $brandsData['filter'] == 'latestprice' ) )
		{
			$productCollection->addFinalPrice()->getSelect()->where('price_index.final_price < price_index.price');
		}
		if( isset($brandsData['filter']) && ( $brandsData['filter'] == 'latestprice' ) )
		{
			$productCollection->addAttributeToSort('price_update_day', 'asc');
		}
		else
		{
			$productCollection->addAttributeToSort('product_created_day','asc');
		}
		$productCollection->setOrder('entity_id','DESC');
		//return ''.$productCollection->getSelect();


		$i=0;
		$query = '';
		$query = ''.$productCollection->getSelect();
		$productArray = array();
		$i;
		foreach( $productCollection as $values)
		{
			$product =  $product = Mage::getModel('catalog/product')->load($values->getEntityId());
			$productArray[$i]['product_id'] = $product->getId();
			$productArray[$i]['price'] = $product->getPrice();
			$productArray[$i]['special_price'] = $product->getSpecialPrice();
			$productArray[$i]['special_price_form_date'] = $product->getSpecialFromDate();
			$productArray[$i]['special_price_to_date'] = $product->getSpecialToDate();
			$productArray[$i]['product_name'] = $product->getName();
			$productArray[$i]['brand_name'] = $brandsNameArray[$product->getBrands()]['title'];
			$productArray[$i]['brand_image'] = $brandsNameArray[$product->getBrands()]['filename'];
			//$productArray[$i]['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA). 'catalog' . DS . 'product'.$product->getImage();
			//$productArray[$i]['image'] = (string)Mage::helper('catalog/image')->init($product, 'image')->constrainOnly(true)->keepAspectRatio(true)->keepFrame(false)->resize(210,210);
			$productArray[$i]['image'] =(string)Mage::helper('catalog/image')->init($product, 'small_image')->resize(210);

			//return (string)Mage::helper('catalog/image')->init($product, 'image')->resize(500,500);
			$productArray[$i]['created_date'] = $product->getCreatedAt();
			//$productArray[$i]['created_day'] =  $product->getProductCreatedDay();
			$productArray[$i]['created_day'] = $product->getAttributeText('product_created_day');
			$productArray[$i]['updated_date'] = $product->getUpdatedAt();
			//$productArray[$i]['price_update_day'] = $product->getPriceUpdateDay();
			$productArray[$i]['price_update_day'] = $product->getAttributeText('price_update_day');
			$productArray[$i]['price_update_date'] =  $product->getPriceUpdateDate();
			$productId = $product->getId();
			$reviews = Mage::getModel('review/review')
			->getResourceCollection()
			->addStoreFilter(Mage::app()->getStore()->getId())
			->addEntityFilter('product', $productId)
			->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED)
			->setDateOrder()
			->addRateVotes();
			/**
			* Getting average of ratings/reviews
			*/
			$avg = 0;
			$ratings = array();
			if (count($reviews) > 0) {
			foreach ($reviews->getItems() as $review) {
			foreach( $review->getRatingVotes() as $vote ) {
			$ratings[] = $vote->getPercent();
			}
			}
			$avg = array_sum($ratings)/count($ratings);
			$avg = round(  ( $avg * 5 ) / 100 );
			}
			$productArray[$i]['rating_count'] = $avg;
			if( array_key_exists($product->getId(),$wishListArray) )
			{
			 	$productArray[$i]['wishlist']['status'] =  1;
			 	$productArray[$i]['wishlist']['item_id'] =  $wishListArray[$product->getId()];
			}
			else
			{
				$productArray[$i]['wishlist']['status'] = 0;
			}

			$currentDate = strtotime(date('m/d/Y'));
			if( ( $product->getCouponStartDate() != '' && $product->getCouponEndDate() != '' ) && ( $product->getCouponStartDate() != null && $product->getCouponEndDate() != null ))
			{
			$startDate = strtotime($product->getCouponStartDate());
			$endDate = strtotime($product->getCouponEndDate());
			}
			else
			{
				$startDate = 0 ;
				$endDate = 0;
			}
			if( ($currentDate >= $startDate) && ( $currentDate <= $endDate )  )
			{
				$productArray[$i]['coupon_name'] = $product->getCouponCode();
				$productArray[$i]['coupon_description'] = $product->getCouponDescription();
				$productArray[$i]['coupon_start_date'] = $product->getCouponStartDate();
				$productArray[$i]['coupon_end_date'] = $product->getCouponEndDate();
			}
			else
			{
				$productArray[$i]['coupon_name'] = '';
				$productArray[$i]['coupon_description'] = '';
				$productArray[$i]['coupon_start_date'] = '';
				$productArray[$i]['coupon_end_date'] = '';
			}
			if( ( $product->getSiteOfferStartDate() != '' && $product->getSiteOfferEndDate() != '' ) && ( $product->getSiteOfferStartDate() != null && $product->getSiteOfferEndDate() != null ))
			{
				$startDate = strtotime($product->getSiteOfferStartDate());
				$endDate = strtotime($product->getSiteOfferEndDate());
			}
			else
			{
				$startDate = 0 ;
				$endDate = 0;
			}
			if(($currentDate>$startDate) && ( $currentDate < $endDate )  )
			{
				$productArray[$i]['site_offer_description'] = $product->getSiteOfferDescription();
				$productArray[$i]['site_offer_end_date'] = $product->getSiteOfferEndDate();
				$productArray[$i]['site_offer_start_date'] = $product->getSiteOfferStartDate();
			}
			else
			{
				$productArray[$i]['site_offer_description'] = '';
				$productArray[$i]['site_offer_end_date'] ='';
				$productArray[$i]['site_offer_start_date'] = '';
			}

			$productArray[$i]['advertiser_buy_link'] =  $product->getAdvertiserBuyLink();
			$i++;
		}
		//$query = 'aaaa'. $productCollection->getSelect();
		return json_encode( $productArray );
		//$productCollection = Mage::get
	}

public function brandscategory( $brandsData )
{
	$read = Mage::getSingleton( 'core/resource' )->getConnection( 'core_read' );
	$brandsData = json_decode($brandsData,true);
	$category = Mage::getResourceModel('catalog/category_collection')->addFieldToFilter('name', $brandsData['gender']);
	$cat_det=$category->getData();
	$categoryArray[] =$cat_det[0]['entity_id'];
	if(  ( !isset($brandsData['customer_id'] ) ) || $brandsData['customer_id'] == ''   )
	{
			$brandsCollection = Mage::getModel('brands/brands')->getCollection()->addFieldToFilter('brands_id',array('in' => $brandsData['brands']));
			$brandsString = '';
			$brandsAttributeArray = array();
			foreach( $brandsCollection as $value )
			{
				$brandsAttributeArray[] = $value->getBrandsAttributeId();
			}
	}
	else
	{
		$sql = "Select br.brands_id,br.brands_attribute_id from db_brands br Inner Join ( Select brands_id from db_brandscustomer where customer_id  =  " .$brandsData['customer_id'] ." AND status = 1 ) cus ON br.brands_id = cus.brands_id where br.status = 1 AND br.category=".$cat_det[0]['entity_id'];
		$resultSql = $read->query( $sql);
	$i=0;
	$categoryArray = array();
	while ( $sqldata = $resultSql->fetch() ) {
		$brandsAttributeArray[] = $sqldata['brands_attribute_id'] ;
	   }
	}
	//return $brandsAttributeArray;
	$brandsString = implode(',',$brandsAttributeArray);

	$brandString = implode(',',$brandsAttributeArray);
	$query ="SELECT DISTINCT cat.category_id from db_catalog_category_product_index cat JOIN (SELECT `e`.entity_id FROM `db_catalog_product_entity` AS `e` INNER JOIN `db_catalog_product_entity_int` AS `at_brands_default` ON (`at_brands_default`.`entity_id` = `e`.`entity_id`) AND (`at_brands_default`.`attribute_id` = '133') AND `at_brands_default`.`store_id` = 0 LEFT JOIN `db_catalog_product_entity_int` AS `at_brands` ON (`at_brands`.`entity_id` = `e`.`entity_id`) AND (`at_brands`.`attribute_id` = '133') AND (`at_brands`.`store_id` = 1) INNER JOIN `db_catalog_category_product_index` AS `cat_index` ON cat_index.product_id=e.entity_id AND cat_index.store_id=1 AND cat_index.category_id = '".$categoryArray[0]."' WHERE (IF(at_brands.value_id > 0, at_brands.value, at_brands_default.value) IN(".$brandString ."))) product ON product.entity_id = cat.product_id WHERE cat.is_parent != 0 ORDER BY cat.category_id";
	//return $query;
	$result = $read->query( $query);
	$i=0;
	$categoryArray = array();
	while ( $row = $result->fetch() ) {
		 $_child = Mage::getModel( 'catalog/category' )->load( $row['category_id'] );
		 $categoryArray[$i]['category_id'] = $row['category_id'];
		 $categoryName = $_child->getName();
		 if($categoryName == 'Male' || $categoryName == 'Female')
		 {
			  $categoryArray[$i]['category_name'] = 'All';
		 }
		 else
		 {
			 $categoryArray[$i]['category_name'] = $_child->getName();
		}

		 $i++;
	}
	return (json_encode($categoryArray));
}


public function addwishlist( $productData  )
{
	$productData = json_decode($productData ,true);

	$storeId = $productData['website_id'];
	Mage::app()->setCurrentStore($storeId);
	$model = Mage::getModel('catalog/product');
	$_product = $model->load($productData['product_id']);
	$params = array('product' => $data['productId'],
                'qty' => 1,
                'options' => $productData['options'],
					);

      $request = new Varien_Object();
      $request->setData($params);
      $wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer($productData['customer_id'], true);
      $result = $wishlist->addNewItem($_product, $request);
     // $resultData = $result->getData();
      $resultData['wishlist_id'] = $result->getWishlistId();
      $resultData['item_id'] = $result->getWishlistItemId();
      return json_encode($resultData);

}


public function removewishlist($requestData)
{
	$requestData = json_decode($requestData,true);
	Mage::app()->setCurrentStore($requestData['website_id']);
	$id = (int) $requestData['item_id'];
				$item = Mage::getModel('wishlist/item')->load($id);
				if (!$item->getId()) {
					return $this->norouteAction();
				}
 $wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer($requestData['customer_id'], true);
				if (!$wishlist) {
					return $this->norouteAction();
				}
				try {
					$item->delete();
					$wishlist->save();
					return $wishlist->getId();

				} catch (Mage_Core_Exception $e) {

				} catch (Exception $e) {

				}
	}


public function spotlight( $requestData )
{
		$productArray = array();
		$params = json_decode($requestData,true);
		$wishList = Mage::getSingleton('wishlist/wishlist')->loadByCustomer($params['customer_id']);
		$wishListItemCollection = $wishList->getItemCollection();
		$select = '';
		$select = $select . $wishListItemCollection->getSelect();
		$wishListArray = array();
	    foreach(  $wishListItemCollection as $values )
	    {
			 if( !array_key_exists($values->getProductId(),$wishListArray) )
			 {
				$wishListArray[$values->getProductId()] =$values->getWishlistItemId();
			 }
		}
		if( isset( $params['limit'] ) )
		{
			$limit1 = $params['limit'];
		}
		else
		{
			$limit1 = Mage::getStoreConfig('mobile/general/productperpage');
		}							;
		if( $params['page'] == 1 || (!isset($params['page'])) || $params['page'] == '' || $params['page'] == 0 )
		{
			$start = 0;
			$limit = $limit1;
		}
		else
		{
			$start = ( ( $params['page']-1 ) * $limit1 );
			$limit = $limit1;
		}
		$attributeModel = Mage::getSingleton('eav/config')
				->getAttribute('catalog_product', 'brands');
			$brandsId =  $attributeModel->getAttributeId();
		$attributeModel = Mage::getSingleton('eav/config')
				->getAttribute('catalog_product', 'name');
			$nameId =  $attributeModel->getAttributeId();
		$attributeModel = Mage::getSingleton('eav/config')
				->getAttribute('catalog_product', 'description');
			$descriptionId =  $attributeModel->getAttributeId();
		$attributeModel = Mage::getSingleton('eav/config')
				->getAttribute('catalog_product', 'short_description');
		$short_descriptionId =  $attributeModel->getAttributeId();
		$read = Mage::getSingleton( 'core/resource' )->getConnection( 'core_read' );
		$query ="SELECT DISTINCT(e.entity_id),`wish`.added_at FROM `db_catalog_product_entity` AS `e` INNER JOIN `db_catalog_product_entity_int` AS `at_status_default` ON (`at_status_default`.`entity_id` = `e`.`entity_id`) AND (`at_status_default`.`attribute_id` = '96') AND `at_status_default`.`store_id` = 0 LEFT JOIN `db_catalog_product_entity_int` AS `at_status` ON (`at_status`.`entity_id` = `e`.`entity_id`) AND (`at_status`.`attribute_id` = '96') AND (`at_status`.`store_id` = 1) INNER JOIN `db_catalog_product_entity_int` AS `at_visibility_default` ON (`at_visibility_default`.`entity_id` = `e`.`entity_id`) AND (`at_visibility_default`.`attribute_id` = '102') AND `at_visibility_default`.`store_id` = 0 LEFT JOIN `db_catalog_product_entity_int` AS `at_visibility` ON (`at_visibility`.`entity_id` = `e`.`entity_id`) AND (`at_visibility`.`attribute_id` = '102') AND (`at_visibility`.`store_id` = 1)
		INNER JOIN (".$select." ) wish ON (`wish`.`product_id` = `e`.`entity_id`)
		 INNER JOIN `db_catalog_category_product_index` AS `cat_index` ON cat_index.product_id=e.entity_id AND cat_index.store_id=1";
		$query2 = '';

		if( isset($params['brands']) && count($params['brands'])  )
		{
			$brandAttribute = implode(',',$params['brands']);
			/*$brandsQuery = "SELECT brands.category,cust.brands_id,brands.brands_attribute_id FROM `db_brandscustomer` cust INNER JOIN db_brands brands ON brands.brands_id = cust.brands_id WHERE brands_customer_id IN (".$brandAttribute.")";*/
			$brandsQuery = "SELECT brands.category,brands.brands_attribute_id FROM db_brands brands WHERE brands_id IN (".$brandAttribute.")  ";
			$brandsResult = $read->fetchAll( $brandsQuery );
			$category  =0;
			$brandsArray = '';
			if(count($brandsResult))
			{
				foreach( $brandsResult as $brandsValue )
				{
					$brandsArray[] = $brandsValue['brands_attribute_id'];
					$category = 		$brandsValue['category'];
				}
			}

			$brandsString = implode(',',$brandsArray);

			if( ( isset($params['gender']) && $params['gender'] != '' ) && ( isset($params['category_ids']) && count($params['category_ids'])>0 ) )
			{
				$query .=" AND cat_index.category_id  IN(".implode(',',$params['category_ids']).")" ;
			}
			else if( ( isset($params['gender']) && $params['gender'] != '' )  )
			{

				$category = Mage::getResourceModel('catalog/category_collection')->addFieldToFilter('name', $params['gender']);
				$cat_det=$category->getData();
				$categoryArray[] =$cat_det[0]['entity_id'];
				$query .=" AND cat_index.category_id = '".$categoryArray[0]."'  " ;
			}
			else
			{
				$query .=" AND cat_index.category_id = '".$category."'  " ;
			}

			$query2 .= "AND (IF(at_brands.value_id > 0, at_brands.value, at_brands_default.value) IN(".$brandsString."))  ";

		}
		else if( ( isset($params['gender']) && $params['gender'] != '' ) && ( isset($params['category_ids']) && count($params['category_ids'])>0 ) )
		{
			$query .=" AND cat_index.category_id  IN(".implode(',',$params['category_ids']).")" ;
		}
		else if( ( isset($params['gender']) && $params['gender'] != '' )  )
		{
			$category = Mage::getResourceModel('catalog/category_collection')->addFieldToFilter('name', $params['gender']);
			$cat_det=$category->getData();
		    $categoryArray[] =$cat_det[0]['entity_id'];
			$query .=" AND cat_index.category_id = '".$categoryArray[0]."'  " ;
		}
		 $query .=" INNER JOIN `db_catalog_product_entity_int` AS `at_brands_default` ON (`at_brands_default`.`entity_id` = `e`.`entity_id`) AND (`at_brands_default`.`attribute_id` = '133') AND `at_brands_default`.`store_id` = 0 LEFT JOIN `db_catalog_product_entity_int` AS `at_brands` ON (`at_brands`.`entity_id` = `e`.`entity_id`) AND (`at_brands`.`attribute_id` = '".$brandsId."') AND (`at_brands`.`store_id` = 1) ";

		$query .="  INNER JOIN `db_catalog_product_entity_varchar` AS `at_name_default` ON (`at_name_default`.`entity_id` = `e`.`entity_id`) AND (`at_name_default`.`attribute_id` = '71') AND `at_name_default`.`store_id` = 0 LEFT JOIN `db_catalog_product_entity_varchar` AS `at_name` ON (`at_name`.`entity_id` = `e`.`entity_id`) AND (`at_name`.`attribute_id` = '".$nameId."') AND (`at_name`.`store_id` = 1) INNER JOIN `db_catalog_product_entity_text` AS `at_description_default` ON (`at_description_default`.`entity_id` = `e`.`entity_id`) AND (`at_description_default`.`attribute_id` = '72') AND `at_description_default`.`store_id` = 0 LEFT JOIN `db_catalog_product_entity_text` AS `at_description` ON (`at_description`.`entity_id` = `e`.`entity_id`) AND (`at_description`.`attribute_id` = '".$descriptionId."') AND (`at_description`.`store_id` = 1) INNER JOIN `db_catalog_product_entity_text` AS `at_short_description_default` ON (`at_short_description_default`.`entity_id` = `e`.`entity_id`) AND (`at_short_description_default`.`attribute_id` = '".$short_descriptionId."') AND `at_short_description_default`.`store_id` = 0 LEFT JOIN `db_catalog_product_entity_text` AS `at_short_description` ON (`at_short_description`.`entity_id` = `e`.`entity_id`) AND (`at_short_description`.`attribute_id` = '73') AND (`at_short_description`.`store_id` = 1)  ";

		$query .=" INNER JOIN `db_cataloginventory_stock_item` AS `at_inventory_in_stock` ON (at_inventory_in_stock.`product_id`=e.entity_id) AND ((at_inventory_in_stock.use_config_manage_stock = 0 AND at_inventory_in_stock.manage_stock=1 AND at_inventory_in_stock.is_in_stock=1) OR (at_inventory_in_stock.use_config_manage_stock = 0 AND at_inventory_in_stock.manage_stock=0) OR (at_inventory_in_stock.use_config_manage_stock = 1 AND at_inventory_in_stock.is_in_stock=1)) WHERE  (IF(at_status.value_id > 0, at_status.value, at_status_default.value) = '1') AND (IF(at_visibility.value_id > 0, at_visibility.value, at_visibility_default.value) = '4') ";
		$brandAttribute = '';
		$brandsCollection = Mage::getModel('brands/brands')->getCollection();
		$brandsNameArray = array();
		foreach( $brandsCollection as $keys => $values )
		{
			if( !array_key_exists($values->getBrandsAttributeId(),$brandsNameArray) )
			{
				$brandsNameArray[$values->getBrandsAttributeId()]['title'] = $values->getTitle();
				$brandsNameArray[$values->getBrandsAttributeId()]['filename'] = $values->getFilename();
			}
		}

		$query = $query.$query2;

		$query .=" ORDER BY `wish`.`added_at` desc LIMIT " . $limit ." OFFSET " . $start;
		//return $query;
		//die;
		$spotResult = $read->fetchAll($query);
		$i =0;
		foreach($spotResult as $spotValue ) {
			$product = Mage::getModel('catalog/product')->load($spotValue['entity_id']);
			$daysArray = array('0'=>'Today','1'=>'Yesterday','2'=>'2 Days Ago','3'=>'3 Days Ago','4'=>'4 Days Ago','5'=>'5 Days Ago','6'=>'6 Days Ago','7'=>'Last Week','14'=>'2 Weeks Ago','21'=>'3 Weeks Ago','28' =>'1 Month Ago','60'=>'2 Months Ago','90' => '3 - 6 Months Ago','210'=>'7 + Months Ago');
					 $now  =	Mage::getModel('core/date')->timestamp(time());
					 $updateDate = $spotValue['added_at'];
					 $your_date = Mage::getModel('core/date')->timestamp(strtotime($updateDate));
					 $datediff = $now - $your_date;
					 $datediffday = floor($datediff/(60*60*24));
						if(  $datediffday <= 0 )
						{
							$difference = $daysArray[0];
						}
						elseif( $datediffday == 1 )
						{
							$difference = $daysArray[1];
						}
						elseif( $datediffday == 2 )
						{
							$difference = $daysArray[2];
						}
						elseif( $datediffday == 3 )
						{
							$difference = $daysArray[3];
						}
						elseif( $datediffday == 4 )
						{
							$difference = $daysArray[4];
						}
						elseif( $datediffday == 5 )
						{
							$difference = $daysArray[5];
						}
						elseif( $datediffday == 6 )
						{
							$difference = $daysArray[6];
						}
						elseif( $datediffday > 6 && $datediffday <= 14 )
						{
							$difference = $daysArray[7];
						}
						elseif( $datediffday > 14 && $datediffday <= 21 )
						{
							$difference = $daysArray[14];
						}
						elseif( $datediffday > 21 && $datediffday <= 60 )
						{
							$difference = $daysArray[28];
						}
						elseif( $datediffday > 60 && $datediffday <= 90 )
						{
							$difference = $daysArray[60];
						}
						elseif( $datediffday > 90 && $datediffday <= 210 )
						{
							$difference = $daysArray[90];
						}
						elseif( $datediffday > 210  )
						{
							$difference = $daysArray[210];
						}
			$productArray[$i]['days'] = $difference;
			$productArray[$i]['product_id'] = $product->getId();
			$productArray[$i]['price'] = $product->getPrice();
			$productArray[$i]['special_price'] = $product->getSpecialPrice();
			$productArray[$i]['special_price_form_date'] = $product->getSpecialFromDate();
			$productArray[$i]['special_price_to_date'] = $product->getSpecialToDate();
			$productArray[$i]['product_name'] = $product->getName();
			$productArray[$i]['brand_name'] = $brandsNameArray[$product->getBrands()];
			$productArray[$i]['image'] = $product->getImageUrl();
			$productArray[$i]['coupon_name'] = '';
			$productArray[$i]['created_date'] = $product->getCreatedAt();
			//$productArray[$i]['created_day'] =  $product->getProductCreatedDay();
			$productArray[$i]['created_day'] =  $product->getAttributeText('product_created_day');
			$productArray[$i]['updated_date'] = $product->getUpdatedAt();
			//$productArray[$i]['price_update_day'] = $product->getPriceUpdateDay();
			$productArray[$i]['price_update_day'] = $product->getAttributeText('price_update_day');
			$productArray[$i]['price_update_date'] =  $product->getPriceUpdateDate();
			$reviews = Mage::getModel('review/review')
			->getResourceCollection()
			->addStoreFilter(Mage::app()->getStore()->getId())
			->addEntityFilter('product', $product->getId())
			->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED)
			->setDateOrder()
			->addRateVotes();
			/**
			* Getting average of ratings/reviews
			*/
			$avg = 0;
			$ratings = array();
			if (count($reviews) > 0) {
			foreach ($reviews->getItems() as $review) {
			foreach( $review->getRatingVotes() as $vote ) {
			$ratings[] = $vote->getPercent();
			}
			}
			$avg = array_sum($ratings)/count($ratings);
			$avg = round(  ( $avg * 5 ) / 100 );
			}
			$productArray[$i]['rating_count'] = $avg;
			if( array_key_exists($product->getId(),$wishListArray) )
			{
			 	$productArray[$i]['wishlist']['status'] =  1;
			 	$productArray[$i]['wishlist']['item_id'] =  $wishListArray[$product->getId()];
			}
			else
			{
				$productArray[$i]['wishlist']['status'] = 0;
			}
			$i++;

		 }
		 return (json_encode($productArray));
}



public function addreview($requestData )
{
		$requestData = json_decode($requestData,true);
		$sql =" SELECT rating.rating_id,rating.rating_code,ratingopt.option_id,ratingopt.code,ratingopt.value
				FROM `db_rating` AS rating
				LEFT JOIN db_rating_option ratingopt ON ratingopt.rating_id = rating.rating_id
				WHERE rating.entity_id =1 ";

		$read = Mage::getSingleton('core/resource')->getConnection('core/read');
		$result = $read->fetchAll($sql);
		$rating_options = array();
		$i = 0;
		foreach($result as $values )
		{

			$rating_options[$values['rating_code']][$i]['rating_id']=$values['rating_id'];
			$rating_options[$values['rating_code']][$i]['option_id']=$values['option_id'];
			$rating_options[$values['rating_code']][$i]['code']=$values['code'];
			$rating_options[$values['rating_code']][$i]['value']=$values['value'];
			$i++;
		}
		Mage::app()->setCurrentStore($requestData['website_id']); //desired store id
		$review = Mage::getModel('review/review');
		$review->setEntityPkValue($requestData['product_id']);//product id
		$review->setStatusId(1);
		$review->setTitle($requestData['title']);
		$review->setDetail($requestData['detail']);
		$review->setEntityId(1);
		$review->setStoreId(1);      //storeview
		$review->setStatusId(2); //approved
		$review->setCustomerId($requestData['customer_id']);
		$review->setNickname($requestData['nickname']);
		$review->setReviewId($review->getId());
		$review->setStores(array(Mage::app()->getStore()->getId()));
		$review->save();
		$review->aggregate();
		//return $rating_options;
		foreach( $requestData['rating'] as $key => $ratingdata )
		{
			//return $ratingdata;
			//return $rating_options[$key];
			//return $key;
			//die;
			foreach($rating_options[$key] as $ratingValue )
			{
				if($ratingValue['value'] == $ratingdata )
				{
					//return $ratingValue;
					//return $ratingValue['rating_id'];
					Mage::getModel('rating/rating')
					->setRatingId($ratingValue['rating_id'])
					->setReviewId($review->getId())
					->setCustomerId($requestData['customer_id'])
					->addOptionVote($ratingValue['option_id'],$requestData['product_id']);
					break;
				}
			}
		}
		if( $review->getId() )
		{
			return "review added";
		}
		else
		{
			return "review not added";
		}


}



public function tracking($requestData)
{
		$requestData = json_decode($requestData,true);
		$data['product_id'] = $requestData['product_id'];
		$product = Mage::getModel('catalog/product')->load($data['product_id']);
		$data['customer_id'] = $requestData['customer_id'];
		$data['product_link'] =$product->getAdvertiserBuyLink();
		$data['product_name'] = $product->getName();
		$trackingColl = Mage::getModel('tracking/tracking')->getCollection()->addFieldToFilter('customer_id', $data['customer_id'])->addFieldToFilter('product_id', $data['product_id']);
		$trackingModel = Mage::getModel('tracking/tracking');
		$trackingModel->setData($data);
		if( count($trackingColl) )
		{
			$trackingData =  $trackingColl->getData();
			$data['total_count']= $trackingData[0]['total_count'] + 1;
			$trackingModel->setTotalCount($data['total_count']);
			$trackingModel->setId($trackingData[0]['tracking_id']);
			$trackingModel->setCreatedTime($trackingData[0]['created_time']);
			$trackingModel->setUpdateTime($trackingData[0]['update_time']);
			$data['tracking_id'] = $trackingData[0]['tracking_id'];

		}
		else
		{
			$trackingModel->setTotalCount(1);
		}
		try {

				if ($trackingModel->getCreatedTime() == NULL || $trackingModel->getUpdateTime() == NULL) {
					$trackingModel->setCreatedTime(now())
						->setUpdateTime(now());
				} else {
					$trackingModel->setUpdateTime(now());
				}

				$trackid = $trackingModel->save();
				return 'success';
				//return  $trackingModel->getId()
			}
		catch (Exception $e) {
			return 'error';
			print_r($e->getMessage());
		}
}
}
?>
