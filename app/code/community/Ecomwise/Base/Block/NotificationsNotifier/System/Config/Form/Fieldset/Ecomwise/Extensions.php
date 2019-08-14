<?php

class Ecomwise_Base_Block_NotificationsNotifier_System_Config_Form_Fieldset_Ecomwise_Extensions extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{

    protected $_dummyElement;
    protected $_fieldRenderer;
    protected $_values;

    public function render(Varien_Data_Form_Element_Abstract $element){
        $html = $this->_getHeaderHtml($element);
        $modules = array_keys((array)Mage::getConfig()->getNode('modules'));
        sort($modules);

        foreach ($modules as $moduleName) {
            if(strstr($moduleName, 'Ecomwise_') !== false){
				$html.= $this->_getFieldHtml($element, $moduleName);
            }
        }
        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    protected function _getFieldRenderer(){
        if (empty($this->_fieldRenderer)){
            $this->_fieldRenderer = Mage::getBlockSingleton('adminhtml/system_config_form_field');
        }
        return $this->_fieldRenderer;
    }

    protected function _getFieldHtml($fieldset, $moduleName){
    	$version = Mage::getConfig()->getNode()->modules->$moduleName->version;
        if ($version) {
            $field = $fieldset->addField($moduleName, 'label', array(
						                                              'name' => $moduleName,
						                                              'label' => $moduleName,
						                                              'value' => 'v' . $version,
																	))->setRenderer($this->_getFieldRenderer());
            return $field->toHtml();
        }        
        return '';
    }
}
