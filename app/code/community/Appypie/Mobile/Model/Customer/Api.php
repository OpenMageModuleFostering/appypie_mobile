<?php
/*
 * Company : ONS Interactive Pvt. Ltd
 * Version : 1.1
 * Author : Ruchi Kapil
 *
 */
class Appypie_Mobile_Model_Customer_Api extends Mage_Customer_Model_Api_Resource
{
	const XML_PATH_REMIND_EMAIL_TEMPLATE = 'customer/password/remind_email_template';
    const XML_PATH_FORGOT_EMAIL_TEMPLATE = 'customer/password/forgot_email_template';
    const XML_PATH_FORGOT_EMAIL_IDENTITY = 'customer/password/forgot_email_identity';

	protected $_mapAttributes = array(
        'customer_id' => 'entity_id'
    );
    protected $_customerSession = null;

    /**
     * Prepare data to insert/update.
     * Creating array for stdClass Object
     *
     * @param stdClass $data
     * @return array
     */
    protected function _prepareData($data)
    {
       foreach ($this->_mapAttributes as $attributeAlias=>$attributeCode) {
            if(isset($data[$attributeAlias]))
            {
                $data[$attributeCode] = $data[$attributeAlias];
                unset($data[$attributeAlias]);
            }
        }
        return $data;
    }
     /**
     * Create new customer
     *
     * @param array $customerData
     * @return int
     */
    public function create($customerData)
    {

        $customerData = json_decode($customerData,true);
        	Mage::app()->setCurrentStore($customerData['website_id']);
//echo   isset($customerData['customer_for']) && $customerData['customer_for'] == 'facebook';
        if( isset($customerData['customer_for']) && $customerData['customer_for'] == 'facebook' )
        {
			if(!isset($customerData['device_id']))
			{
				$customerData['device_id'] = '';
			}
			$customersByFacebookId = Mage::helper('inchoo_socialconnect/facebook')
                ->getCustomersByFacebookId($customerData['facebook_id']);
                $customerContent = $customersByFacebookId->getData();
                //return count($customerContent);
                if(count($customerContent)) {
					//return trim($customerData['email']);
					$customer = Mage::getModel("customer/customer");
					$customer->setWebsiteId(1);
					$customer->loadByEmail(trim($customerData['email']));
				try {
					$gender = '';
					if(ucfirst(trim($customerData['gender'])) == 'Male')
					{
						$gender = 1;
					}
					elseif(ucfirst(trim($customerData['gender'])) == 'Female')
					{
						$gender = 2;
					}
					else
					{
						$gender = 1;
					}

					$customer->setGender($gender);
					$customer->save();

					$collection = Mage::getModel('notification/customer')->getCollection()->addFieldToFilter('device_token',$customerData['device_token']);
					$collectionData = $collection->getData();
					$model = Mage::getModel('notification/customer');

					if($collection->count() ==  0 )
					{
							$model->setDeviceId($customerData['device_id']);
							$model->setDeviceToken($customerData['device_token']);
							$model->setCustomerId($customer->getId());
							$model->setDeviceType($customerData['device_type']);
							$model->setLoginStatus(1);
					}
					else
					{
							$model->setId($collectionData[0]['app_id']);
							$model->setDeviceId($customerData['device_id']);
							$model->setDeviceToken($customerData['device_token']);
							$model->setCustomerId($customer->getId());
							$model->setDeviceType($customerData['device_type']);
							$model->setLoginStatus(1);
					}
					if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
						$model->setCreatedTime(now())
							->setUpdateTime(now());
					} else {
						$model->setUpdateTime(now());
					}
					$model->save();
				}catch (Mage_Core_Exception $e) {
					return $e->getMessage();
					$this->_fault('data_invalid', $e->getMessage());
				}
				return $customerContent[0]['entity_id'];
				exit;
				}
                else
                {
					$customer = Mage::getModel('customer/customer');
					if( isset($customerData['email']) && $customerData['email'] !='' )
					{
						$customer->setEmail($customerData['email']);
					}
					$gender = '';
					if(ucfirst(trim($customerData['gender'])) == 'Male')
					{
						$gender = 1;
					}
					if(ucfirst(trim($customerData['gender'])) == 'Female')
					{
						$gender = 2;
					}

					$customer->setFirstname($customerData['firstname'])
					->setLastname($customerData['lastname'])
					->setGender($gender)
					->setInchooSocialconnectFid($customerData['facebook_id'])
					->setInchooSocialconnectFtoken($customerData['facebook_token'])
					->setPassword($customer->generatePassword(10))
					->save();
					$customer->setConfirmation(null);
					$customer->save();
					$customer->sendNewAccountEmail();
					$collection = Mage::getModel('notification/customer')->getCollection()->addFieldToFilter('device_token',$customerData['device_token']);
					$collectionData = $collection->getData();
					$model = Mage::getModel('notification/customer');
					if($collection->count() ==  0 )
					{
							$model->setDeviceId($customerData['device_id']);
							$model->setDeviceToken($customerData['device_token']);
							$model->setCustomerId($customer->getId());
							$model->setDeviceType($customerData['device_type']);
							$model->setLoginStatus(1);


					}
					else
					{
							$model->setId($collectionData[0]['app_id']);
							$model->setDeviceId($customerData['device_id']);
							$model->setDeviceToken($customerData['device_token']);
							$model->setCustomerId($customer->getId());
							$model->setDeviceType($customerData['device_type']);
							$model->setLoginStatus(1);
					}
					if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
						$model->setCreatedTime(now())
							->setUpdateTime(now());
					} else {
						$model->setUpdateTime(now());
					}
					$model->save();
					return $customer->getId();
					exit;
			}
		}
		else
		{	//return "nnnnn";

			 $customerData = $this->_prepareData($customerData);

			 $customerData['gender'] = ucfirst($customerData['gender']);
			// return $customerData;
			try {
				$gender = '';
					if(ucfirst(trim($customerData['gender'])) == 'Male')
					{
						$gender = 1;
					}
					if(ucfirst(trim($customerData['gender'])) == 'Female')
					{
						$gender = 2;
					}
            $customer = Mage::getModel('customer/customer')
                ->setData($customerData)
                ->setGender($gender)
                ->save();
        } catch (Mage_Core_Exception $e) {
			$returnValue[0]['status'] = 'false';
			$returnValue[0]['message'] = $e->getMessage();
			return json_encode($returnValue);
			//return $e->getMessage();
            $this->_fault('data_invalid', $e->getMessage());
        }
		  $returnValue[0]['status'] =  'true';
		  $returnValue[0]['customer_id'] =  $customer->getId();
          //return $customer->getId();
          return json_encode($returnValue);
          exit;
	   }

    }
    /**
     * Update customer data
     *
     * @param array $customerData
     * @return boolean
     */
    public function update($customerData)
    {
		$customerData = json_decode($customerData,true);
		$email = $customerData['email'];
		$websiteId = $customerData['websiteId'];
		$customerData = $customerData['customerData'];
        $customerData = $this->_prepareData($customerData);

        $customer = Mage::getModel('customer/customer');
        $customer->setWebsiteId($websiteId);
		$customer->loadByEmail($email);

        if (!$customer->getId()) {
            $this->_fault('not_exists');
        }

        foreach ($this->getAllowedAttributes($customer) as $attributeCode=>$attribute) {
            if (isset($customerData[$attributeCode])) {
                $customer->setData($attributeCode, $customerData[$attributeCode]);
            }
        }

        $customer->save();
        return true;
    }
    /**
     * Customer authentication.
     *
     * @param   string  $website Website code of website to authenticate customer against
     * @return  string.
     */
    public function login( $str )
    {
		$str = json_decode($str,true);
        //return $str['email'];
        $customer = Mage::getModel("customer/customer");
		//$customer->setWebsiteId($str['websiteId']);
		//$customer->loadByEmail($str['email']); //load customer by email id
		$customer->setWebsiteId(1);
		$customer->loadByEmail(trim($str['email']));
		$i = 0;
		if($this->validateHash($str['password'],$customer->getPasswordHash()))
        {
			$cust[$i]['id'] = $customer->getId();
			$cust[$i]['email'] = $customer->getEmail();
			$cust[$i]['firstname'] = $customer->getFirstname();
			$cust[$i]['lastname'] = $customer->getLastname();
			$cust[$i]['gender_id'] = $customer->getGender();
			$cust[$i]['gender'] =  Mage::getModel('customer/customer')->getAttribute('gender')->getSource()->getOptionText($customer->getGender()) ;
			$collection = Mage::getModel('notification/customer')->getCollection()->addFieldToFilter('device_token',$str['device_token']);
			$collectionData = $collection->getData();
			$model = Mage::getModel('notification/customer');
			if($collection->count() ==  0 )
			{
					$model->setDeviceId($str['device_id']);
					$model->setDeviceToken($str['device_token']);
					$model->setCustomerId($customer->getId());
					$model->setDeviceType($str['device_type']);
					$model->setLoginStatus(1);


			}
			else
			{
					$model->setId($collectionData[0]['app_id']);
					$model->setDeviceId($str['device_id']);
					$model->setDeviceToken($str['device_token']);
					$model->setCustomerId($customer->getId());
					$model->setDeviceType($str['device_type']);
					$model->setLoginStatus(1);
			}
			if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
				$model->setCreatedTime(now())
					->setUpdateTime(now());
			} else {
				$model->setUpdateTime(now());
			}
			$model->save();

		}else{
			return 'Invalid email id or password';
		}
        // return authentication result
        return json_encode($cust);
    }
    /**
     * Customer authentication.
     *
     * @param   string  $website Website code of website to authenticate customer against
     * @return  string.
     */
    public function changepassword( $str )
    {
		$str = json_decode($str,true);
        //return $str;
        $customer = Mage::getModel("customer/customer");
		$customer->setWebsiteId($str['website_id']);
		$customer->loadByEmail($str['email']); //load customer by email id
		//return $customer->getPasswordHash();
        if($this->validateHash($str['password'],$customer->getPasswordHash()))
        {
			$cust['id'] = $customer->getId();
			$password = $str['newpassword'];
			$hash = $this->getRandomString(2);
			$customer->setPassword($password);
			$customer->save();
			return 'Success';
		}else{
			return "Invalid Old password";
		}
        // return authentication result
        return $cust;
    }
     /**
     * Customer authentication.
     *
     * @param   string  $website Website code of website to authenticate customer against
     * @return  string.
     */
    public function forgotpassword( $str )
    {
		$str = json_decode($str,true);
        //return $str;
        $customer = Mage::getModel("customer/customer");
		$customer->setWebsiteId($str['websiteId']);
		$customer->loadByEmail($str['email']); //load customer by email id
		//return $customer->getPasswordHash();
        if($customer)
        {
			$cust['id'] = $customer->getId();
			$password = $this->getRandomString(6);
			$cust['password'] = $password;
			$customer->setPassword($password);
			$customer->save();
			$customer->sendPasswordReminderEmail();
		}else{
			return "Invalid Old password";
		}
        // return authentication result
        return $cust;
    }

    public function getRandomString($len=2, $chars = null)
    {
        if (is_null($chars)) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
        mt_srand(10000000*(double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }
    /**
     * Hash a string
     *
     * @param string $data
     * @return string
     */
    public function hash($data)
    {
        return md5($data);
    }

    /**
     * Validate hash against hashing method (with or without salt)
     *
     * @param string $password
     * @param string $hash
     * @return bool
     * @throws Exception
     */
    public function validateHash($password, $hash)
    {
        $hashArr = explode(':', $hash);
        switch (count($hashArr)) {
            case 1:
                return $this->hash($password) === $hash;
            case 2:
                return $this->hash($hashArr[1] . $password) === $hashArr[0];

        }
        Mage::throwException('Invalid hash.');
    }

     /**
     * Retrieve customer data
     *
     * @param string $data
     * @param array $attributes
     * @return array
     */
    public function info($data, $attributes = null)
    {
		$data = json_decode($data,true);
		$email = $data['email'];
		$websiteId = $data['websiteId'];
        //$customer = Mage::getModel('customer/customer')->load($customerId);
        $customer = Mage::getModel("customer/customer");
		$customer->setWebsiteId($websiteId);
		$customer->loadByEmail($email); //load customer by email id

        if (!$customer->getId()) {
            $this->_fault('not_exists');
        }

        if (!is_null($attributes) && !is_array($attributes)) {
            $attributes = array($attributes);
        }

        $result = array();

        foreach ($this->_mapAttributes as $attributeAlias=>$attributeCode) {
            $result[$attributeAlias] = $customer->getData($attributeCode);
        }

        foreach ($this->getAllowedAttributes($customer, $attributes) as $attributeCode=>$attribute) {
            $result[$attributeCode] = $customer->getData($attributeCode);
        }

        return $result;
    }



	/* added by ruchi for feedback */
	public function feedback($feedback)
	{
		$feedback = json_decode($feedback,true );
		$data['email'] = $feedback['email'];
		$data['name'] = $feedback['name'];
		$data['comment'] = $feedback['comment'];
		$model = Mage::getModel('feedback/feedback');

			$model = Mage::getModel('feedback/feedback');
			$model->setData($data);
			try {
				if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
					$model->setCreatedTime(now())
						->setUpdateTime(now());
				} else {
					$model->setUpdateTime(now());
				}
				$model->save();

				// code for confirmation of client feedback
				$template = Mage::getModel('core/email_template')->loadByCode('feedback_customer');
				$templateData = $template->getData();
				$templateId =$templateData['template_id'];
			// Set sender information
			$senderName = Mage::getStoreConfig('trans_email/ident_support/name');
			$senderEmail = Mage::getStoreConfig('trans_email/ident_support/email');
			$sender = array('name' => $senderName,
					'email' => $senderEmail);
			// Set recepient information
			$recepientEmail = $data['email'];
			$recepientName  =$data['name'];
			// Get Store ID
			Mage::app()->setCurrentStore($feedback['website_id']);
			$store = Mage::app()->getStore()->getId();

			// Set variables that can be used in email template
			$vars = array('customer_name' =>$data['name'],
				 );

			$translate  = Mage::getSingleton('core/translate');

			// Send Transactional Email
			Mage::getModel('core/email_template')
			->sendTransactional($templateId, $sender, $recepientEmail, $recepientName, $vars, $storeId);

			$translate->setTranslateInline(true);

			// code for send email to admin


			$template = Mage::getModel('core/email_template')->loadByCode('feedback_admin');
			$templateData = $template->getData();
			$templateId =$templateData['template_id'];
			// Set sender information
			$senderName = Mage::getStoreConfig('trans_email/ident_support/name');
			$senderEmail = Mage::getStoreConfig('trans_email/ident_support/email');
			$sender = array('name' => $senderName,
					'email' => $senderEmail);

			// Set recepient information
			$recepientEmail =  $senderEmail;
			$recepientName = $senderName;

			// Get Store ID
			$store = Mage::app()->getStore()->getId();

			// Set variables that can be used in email template
			$vars = array('customerName' =>$data['name'],
				  'customerEmail' => $data['email'],'customerComment'=>$data['comment']);

			$translate  = Mage::getSingleton('core/translate');

			// Send Transactional Email
			Mage::getModel('core/email_template')
			->sendTransactional($templateId, $sender, $recepientEmail, $recepientName, $vars, $storeId);

			$translate->setTranslateInline(true);


			}
			catch(Exception $e)
			{
				return $e->getMessage();
			}



	}

	public function deviceapp($data)
	{
		$data = json_decode($data,true);

	$collection = Mage::getModel('notification/customer')->getCollection()->addFieldToFilter('device_token',$data['device_token']);
	if($collection->count() ==  0 )
	{
			$model = Mage::getModel('notification/customer');
			$model->setDeviceId($data['device_id']);
			$model->setDeviceToken($data['device_token']);
			$model->setCustomerId($data['customer_id']);
			$model->setPhoneType($data['phone_type']);
			$model->save();
			return "inserted";
	}
	else
	{
			return 'Already there';

	}
}


public function logout( $data )
{
	$str = json_decode($data,true);
	$collection = Mage::getModel('notification/customer')->getCollection()->addFieldToFilter('device_token',$str['device_token']);
			$collectionData = $collection->getData();
			$model = Mage::getModel('notification/customer');
			if($collection->count() ==  0 )
			{
					$model->setDeviceId($str['device_id']);
					$model->setDeviceToken($str['device_token']);
					$model->setCustomerId($str['customer_id']);
					$model->setDeviceType($str['device_type']);
					$model->setLoginStatus(0);
			}
			else
			{
					$model->setId($collectionData[0]['app_id']);
					$model->setDeviceToken($str['device_token']);
					$model->setCustomerId($str['customer_id']);
					$model->setDeviceType($str['device_type']);
					$model->setLoginStatus('0');
			}


			if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
				$model->setCreatedTime(now())
					->setUpdateTime(now());
			} else {
				$model->setUpdateTime(now());
			}
			$model->save();
			$returnValue[0]['status'] = 0;
			return json_encode($returnValue);
			//return '1';

}

}


?>
