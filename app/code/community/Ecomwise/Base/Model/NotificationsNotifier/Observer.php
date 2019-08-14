<?php
class Ecomwise_Base_Model_NotificationsNotifier_Observer {
	
	const XML_USE_HTTPS_PATH = 'base/rss/use_https';
	const XML_FEED_URL_PATH = 'base/rss/url';
	const XML_FREQUENCY_PATH = 'base/rss/check_frequency';
	const XML_FREQUENCY_ENABLE = 'base/rss/enabled';
	const XML_LAST_UPDATE_PATH = 'base/rss/last_update';
	const XML_FEED_OPTION = 'base/rss/feed';
			
	public function adminUserLoginSuccess(){
		if (Mage::getSingleton('admin/session')->isLoggedIn()) {
			if (!Mage::getStoreConfig(self::XML_FREQUENCY_ENABLE)) {
				return;
			}
			$this->checkUpdate();
		}
	}
	
	private function checkUpdate(){
		$feedData = array();
		$feedModel = Mage::getModel('adminnotification/feed');
		
		if (($this->getFrequency() + $this->getLastUpdate()) > time()) {
			return $this; 
		}
		
		$feed_options = $this->getFeedOptions();
		foreach($feed_options as $feed_option){
			if($feed_option == "GENERAL_INFO"){
				$this->saveFeedData("generalinfo");
				break;
			}else if($feed_option == "MY_EXTENSIONS"){
				$extensions = $this->getInstalledExtensions();
				foreach($extensions as $extension){
					$this->saveFeedData($extension);
				}
			}
		}
		return $this;
	}

	private function saveFeedData($extension){
		$feedModel = Mage::getModel('adminnotification/feed');
		$feedXml = $this->getFeedData($extension);

		if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
			foreach ($feedXml->channel->item as $item) {
				$feedData[] = array(
						'severity'      => (int)$item->source,
						'date_added'    => $feedModel->getDate((string)$item->pubDate),
						'title'         => (string)$item->title,
						'description'   => (string)$item->description,
						'url'           => (string)$item->link,
				);
			}

			if ($feedData) {
				Mage::getModel('adminnotification/inbox')->parse(array_reverse($feedData));
			}

		}
		$this->setLastUpdate();		
	}

    private function getFeedUrl(){
	    $feed_url = (Mage::getStoreConfigFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://') . Mage::getStoreConfig(self::XML_FEED_URL_PATH);
		return $feed_url;
	}
	
	private function getFeedData($extension){
		$curl = new Varien_Http_Adapter_Curl();
		$curl->setConfig(array(
				'timeout'   => 2
		));
		$url = $this->getFeedUrl() . $extension;
		$curl->write(Zend_Http_Client::GET, $this->getFeedUrl() . $extension, '1.0');
		$data = $curl->read();
		if ($data === false) {
			return false;
		}
		$data = preg_split('/^\r?$/m', $data, 2);
		$data = trim($data[1]);
		$curl->close();

		try {
			$xml  = new SimpleXMLElement($data);
		}catch (Exception $e) {
			return false;
		}

		return $xml;
	}

	private function getFeedOptions(){
		$options = array(Mage::getStoreConfig(self::XML_FEED_OPTION));
		$results = Mage::getModel('core/config_data')->getCollection();
		$results->getSelect()->where("path='base/rss/feed'");
	
		if(!empty($results)){
			foreach ($results as $result) {
				$options = explode(",", $result->getValue());
			}
		}
		
		return $options;
	}
	
	private function getInstalledExtensions(){
		$ecomwise_extensions = array();
		$modules = array_keys((array)Mage::getConfig()->getNode('modules'));
		sort($modules);
		foreach ($modules as $extension) {
			$is_enable = Mage::helper('core')->isModuleEnabled($extension);
			if ($is_enable === true && strstr($extension, 'Ecomwise_') !== false) {
				$extensionName = explode("_", $extension);
				$ecomwise_extensions[] = strtolower($extensionName[1]);
			}
		}
		return $ecomwise_extensions;
	}

	private function getFrequency(){
		return Mage::getStoreConfig(self::XML_FREQUENCY_PATH);
	}
	
	private function getLastUpdate(){
		return Mage::app()->loadCache('base_notifications_lastcheck');
	}
	
	private function setLastUpdate(){
		Mage::app()->saveCache(time(), 'base_notifications_lastcheck');
		return $this;
	}
	
}