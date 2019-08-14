<?php
class Ecomwise_Base_Model_ExtensionsLicencer_Observer {

	const EXTENSION_USE_HTTPS_PATH = 'base/licencer/use_https';
	const EXTENSION_LICENCER_URL_PATH = 'base/licencer/url';
	const EXTENSION_FREQUENCY_PATH = 'base/licencer/check_frequency';

	public function checkExtensionLicence(){
		if(($this->getFrequency() + $this->getLastUpdate()) > time()){
			return $this;
		}

		$domain = parse_url(Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_URL));
		$extensions = $this->getInstalledExtensions();
		foreach($extensions as $key => $extension){
			if($extension != "base"){
				$license_number = Mage::getStoreConfig($extension . "/settings/serial");
				$response = $this->getServerResponse($extension, $domain["host"], $license_number);							
				$this->setExtensionOutput($key, $response);
				$this->setExtensionStatus($extension, $response);
				$this->setExtensionWarning($extension, $response);				
			}
		}
		
		Mage::app()->getStore()->resetConfig();		
		$this->setLastUpdate();

		return $this;
	}

	public function showAdminMessages(){
		$disabled_extensions = array();
		$warned_extensions = array();
		
		$this->checkExtensionLicence();
		$extensions = $this->getInstalledExtensions();
		foreach($extensions as $extension){
			$status = Mage::getStoreConfig($extension . '/status/valid');
			if($status == "disable"){
				$disabled_extensions[] = ucfirst($extension);
			}
			
			$warning = Mage::getStoreConfig($extension . '/status/warning');
			if($warning != "" && $warning != NULL){
				$warned_extensions[ucfirst($extension)] = $warning;
			}
		}

		if(!empty($warned_extensions)){
			if(count($warned_extensions) > 1){
				$text1 = array();
				$text2 = array();				
				if(count($warned_extensions) == 2){
					foreach($warned_extensions as $key => $value){
						$text1[] = $key;
						$text2[] = $key . " extension will be disabled in " . $value;
					}
				  	$text1 = implode(" and ", $text1);
				  	$text2 = implode(" and the ", $text2);
				}else{
					foreach($warned_extensions as $key => $value){
						$text1[] = $key;
						$text2[] = $key . " extension will be disabled in " . $value;
					}
					$last_element = array_pop($text1);
				  	$text1 = implode(", ", $text1) . " and " . $last_element;
				  	$last_element = array_pop($text2);
				  	$text2 = implode(", the ", $text2) . " and the " . $last_element;
				}		
								
				$message = Mage::Helper("base")->__("Please insert the license key for the " . $text1 . " extensions. The " . $text2 . ".");
			}else{
				$text1 = min(array_keys($warned_extensions));
				$text2 = $warned_extensions[$text1];
				$message = Mage::Helper("base")->__("Please insert the license key for the " . $text1 . " extension. The extension will be disabled in " . $text2 . ".");
			}
			
			Mage::getSingleton('adminhtml/session')->addError($message);
		}
		
		if(!empty($disabled_extensions)){
			if(count($disabled_extensions) > 1){
				$text = "";
				if(count($disabled_extensions) == 2){ 
					$text = implode(" and ", $disabled_extensions);
				}else{
					$last_element = array_pop($disabled_extensions);
					$text = implode(", ", $disabled_extensions) . " and " . $last_element;		
				}
				$message = Mage::Helper("base")->__("The " . $text . " extensions are disabled. Please insert the license key.");
			}else{
				$text = $disabled_extensions[0];
				$message = Mage::Helper("base")->__("The " . $text . " extension is disabled. Please insert the license key.");
			}	
			
			Mage::getSingleton('adminhtml/session')->addError($message);
		}
		
		return $this;
	}

	private function getInstalledExtensions(){
		$ecomwise_extensions = array();
		$modules = array_keys((array)Mage::getConfig()->getNode('modules')->children());
		sort($modules);
		foreach($modules as $extension){
			$is_enable = Mage::helper('core')->isModuleEnabled($extension);
			if ($is_enable === true && strstr($extension, 'Ecomwise_') !== false){
				$extensionName = explode("_", $extension);
				$ecomwise_extensions[$extension] = strtolower($extensionName[1]);
			}
		}
		return $ecomwise_extensions;
	}

	private function getLicenserUrl(){
		$licencer_url = (Mage::getStoreConfigFlag(self::EXTENSION_USE_HTTPS_PATH) ? 'https://' : 'http://') . Mage::getStoreConfig(self::EXTENSION_LICENCER_URL_PATH);
		return $licencer_url;
	}

	private function getServerResponse($extension, $domain, $license_number){
		$curl = new Varien_Http_Adapter_Curl();
		$curl->setConfig(array(
				'timeout'   => 2
		));

		$ip_address = $this->getIpAddress();
		$base_version = Mage::getConfig()->getModuleConfig("Ecomwise_Base")->version;
		$parametars =  "extension/$extension/domain/$domain/ip_address/$ip_address/licence_number/$license_number/base_version/$base_version";
		$curl->write(Zend_Http_Client::GET, $this->getLicenserUrl() . $parametars, '1.0');
		$data = $curl->read();
		if ($data === false){
			return "503";
		}
		$data = preg_split('/^\r?$/m', $data, 2);
		$data = str_replace("\n", "", trim($data[1]));
		$curl->close();

		return json_decode($data, true);
	}

	private function setExtensionOutput($extension, $response){
		$response = isset($response["code"]) && $response["code"] == "100" ? "1" : "0";
		Mage::getConfig()->saveConfig('advanced/modules_disable_output/' . $extension, $response);
		return $this;
	}
	
	private function setExtensionStatus($extension, $response){
		$response = isset($response["code"]) && $response["code"] == "100" ? "disable" : "active";
		Mage::getConfig()->saveConfig($extension . '/status/valid', $response);
		return $this;
	}
	
	private function setExtensionWarning($extension, $response){
		$response = isset($response["code"]) && $response["code"] == "500" ? $response["message"] : "";
		Mage::getConfig()->saveConfig($extension . '/status/warning', $response);	
		return $this;	
	}
	
	private function getIpAddress(){
		$ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
		foreach ($ip_keys as $key){
			if (array_key_exists($key, $_SERVER) === true){
				foreach (explode(',', $_SERVER[$key]) as $ip){
					$ip = trim($ip);
					if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
						return $ip;
					}
				}
			}
		}

		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
	}

	private function getFrequency(){
		return Mage::getStoreConfig(self::EXTENSION_FREQUENCY_PATH);
	}

	private function getLastUpdate(){
		return Mage::app()->loadCache('base_extensions_lastcheck');
	}

	private function setLastUpdate(){
		Mage::app()->saveCache(time(), 'base_extensions_lastcheck');
		return $this;
	}
}