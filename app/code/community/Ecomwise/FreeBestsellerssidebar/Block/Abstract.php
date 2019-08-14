<?php
class Ecomwise_FreeBestsellerssidebar_Block_Abstract extends Mage_Core_Block_Template
{    
    public function getBestsellers()
    {
    	$storeId = Mage::app()->getStore()->getStoreId();
		$productsCollection = $this->getProductsCollection();	
		return $this->filterConfigurables($productsCollection);
    }
    
    private function filterConfigurables($productsCollection)
    {
    	
    	$products = array();
    	$products_return = array();
    	$products_ids = array();
    	
    	foreach($productsCollection as $product)
    	{
    		if ($parent_id = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId()))
        	{        		
        		if(!is_array($parent_id)){
        			$parent_id = array($parent_id);
        		}
        		foreach($parent_id as $par_id){
        			$parent = Mage::getModel('catalog/product')->load($par_id);
        			if(in_array($parent->getId(), $products_ids))
        			{
        				$products[$parent->getId()]['qty'] += $this->intQty($product->getOrderedQty());
        			}
        			else
        			{
        				$products_ids[] = $parent->getId();
        				$products[$parent->getId()]['qty'] = $this->intQty($product->getOrderedQty());
        				$products[$parent->getId()]['product'] = $parent;
        			}
        		}        		
        	}
        	else
        	{
        		$products[$product->getId()]['qty'] = $this->intQty($product->getOrderedQty());
        		$products[$product->getId()] ['product']= Mage::getModel('catalog/product')->load($product->getId());;
        	}
        }
        foreach($products as $product)
        {
        	$products_return[] =  array($product['qty'],$product['product']); 
        }
        arsort($products_return);
        
        return $products_return;
    }
    
    private function getProductsCollection()
    {
    	$productCollection = Mage::getResourceModel('reports/product_collection')
			->addOrderedQty()
			->addAttributeToSelect(array('entity_id', 'entity_type_id', 'attribute_set_id', 'type_id', 'sku', 'has_options', 'required_options', 'created_at', 'updated_at'))
			->setStoreId(Mage::app()->getStore()->getId())
            ->addStoreFilter(Mage::app()->getStore()->getId())
            ->setOrder('ordered_qty', 'desc')
            ->setPageSize(3);

    	return $productCollection;
    }
    
    public function title(){
    	return Mage::getStoreConfig('freebestsellerssidebar/parameters/block_title');
    }
    
	private function intQty($float)
    {
    	return intval($float);
    }
	
	protected function _prepareLayout()
	{
		$head = $this->getLayout()->getBlock('head');
		$head->addItem('skin_css', 'freebestsellerssidebar/css/style.css');
		return parent::_prepareLayout();
	}
	
	protected function _toHtml() {
		if (!$this->getTemplate() || Mage::getStoreConfig('freebestsellerssidebar/parameters/enabled') == 0 ) {
			return '';
		}
		$html = $this->renderView();
		return $html;
	}
}
