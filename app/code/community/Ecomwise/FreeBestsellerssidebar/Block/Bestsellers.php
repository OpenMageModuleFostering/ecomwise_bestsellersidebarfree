<?php

class Ecomwise_FreeBestsellerssidebar_Block_Bestsellers extends Mage_Core_Block_Template
{
	public function __construct()
    {
    	parent::__construct();
    	$this->setTemplate('freebestsellerssidebar/freebestsellerssidebar.phtml'); 
    } 
    
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
        		$parent = Mage::getModel('catalog/product')->load($parent_id);
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

    
    /**** STYLE AND JAVA SCRIPT ****/
	public function getStyle()
	{

		$vs_File =Mage::getBaseDir('skin').DS.'freebestsellerssidebar'.DS.'default/style.css';

		try
		{
			$vs_Skin = file_get_contents($vs_File);
			$vs_Skin = str_replace("[[IMAGES_FOLDER]]", Mage::getBaseUrl('skin').'freebestsellerssidebar/default/images', $vs_Skin);

			$vs_Skin = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $vs_Skin);
  			$vs_Skin = str_replace(array("\t","\n","\r"), '', $vs_Skin); 
		}
		catch(Exception $e)
		{
			$vs_Skin = "/*Skin file: $vs_File could not be read*/";
		}
		
		return trim($vs_Skin);
	}
	
	public function getJs()
	{
		return '<script type="text/javascript" src="'.Mage::getBaseUrl('js').'freebestsellerssidebar/freebestsellerssidebar.js"></script>';
	}

}
