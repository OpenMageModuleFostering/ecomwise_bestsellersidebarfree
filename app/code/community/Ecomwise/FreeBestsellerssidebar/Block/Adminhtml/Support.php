<?php
class Ecomwise_FreeBestsellerssidebar_Block_Adminhtml_Support extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
	
	protected $_template = 'freebestsellerssidebar/support.phtml';
	
	public $module = 'Ecomwise_FreeBestsellerssidebar';
	public $supportUrl = 'http://support.ecomwise.com/support/tickets/new';
	public $email = 'feedback@ecomwise.com';
	public $faq = 'http://support.ecomwise.com/support/solutions/folders/111251';
	public $name = 'Bestseller Sidebar Free';
	public $compatibility = 'Magento CE 1.7.x-1.9.x';
	public $manualUrl = 'http://support.ecomwise.com/support/solutions/articles/3000041609-bestseller-sidebar-free';
	
	public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }
}  