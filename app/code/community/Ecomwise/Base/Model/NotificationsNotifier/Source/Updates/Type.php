<?php

class Ecomwise_Base_Model_NotificationsNotifier_Source_Updates_Type extends Mage_Eav_Model_Entity_Attribute_Source_Abstract{

    const TYPE_GENERAL_INFO = 'GENERAL_INFO';
    const TYPE_MY_EXTENSIONS = 'MY_EXTENSIONS';


    public function toOptionArray(){
        return array(
            array('value' => self::TYPE_GENERAL_INFO, 'label' => Mage::helper('base')->__('General Info')),
            array('value' => self::TYPE_MY_EXTENSIONS, 'label' => Mage::helper('base')->__('My extensions')),
        );
    }

    public function getAllOptions(){
        return $this->toOptionArray();
    }


    public function getLabel($value){
        $options = $this->toOptionArray();
        foreach ($options as $v) {
            if ($v['value'] == $value) {
                return $v['label'];
            }
        }
        return '';
    }

    public function getGridOptions(){
        $items = $this->getAllOptions();
        $out = array();
        foreach ($items as $item) {
            $out[$item['value']] = $item['label'];
        }
        return $out;
    }
}
