<?php
/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @copyright  Copyright (c) 2012 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Advanced Product Options extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @author     MageWorx Dev Team
 */

class MageWorx_CustomOptions_Model_Observer {

    public function cancelOrderItem($observer) {        
        if (!Mage::helper('customoptions')->isInventoryEnabled()) return $this;
        //$qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();        
        $orderItem = $observer->getEvent()->getItem();
        $productOptions = $orderItem->getProductOptions();
        if (!isset($productOptions['options'])) return $this;
        
        $qty = $orderItem->getQtyOrdered();
        foreach ($productOptions['options'] as $option) {                
            switch ($option['option_type']) {
                case 'drop_down':
                case 'radio':
                case 'checkbox':                        
                case 'multiple':
                    $optionId = $option['option_id'];
                    $customoptionsIsOnetime = Mage::getModel('catalog/product_option')->load($optionId)->getCustomoptionsIsOnetime();
                    $optionTypeIds = explode(',', $option['option_value']);
                    foreach ($optionTypeIds as $optionTypeId) {                    
                        $productOptionValueModel = Mage::getModel('catalog/product_option_value')->load($optionTypeId);
                        $customoptionsQty = $productOptionValueModel->getCustomoptionsQty();
                        $sku = $productOptionValueModel->getSku();
                        if ($customoptionsQty!=='' || $sku!='') {
                            if (isset($productOptions['info_buyRequest']['options_'.$optionId.'_qty'])) {
                                $optionQty = intval($productOptions['info_buyRequest']['options_'.$optionId.'_qty']);
                            } elseif (isset($productOptions['info_buyRequest']['options_'.$optionId.'_'.$optionTypeId.'_qty'])) {
                                $optionQty = intval($productOptions['info_buyRequest']['options_'.$optionId.'_'.$optionTypeId.'_qty']);                            
                            } else {
                                $optionQty = 1;
                            }                                                                        
                            $optionTotalQty = ($customoptionsIsOnetime?$optionQty:$optionQty*$qty);                        
                            
                            if ($customoptionsQty!=='' && substr($customoptionsQty, 0, 1)!='x') {
                                $customoptionsQty = $customoptionsQty + $optionTotalQty;                                
                                // model 'catalog/product_option_value' - do not use!
                                Mage::getSingleton('core/resource')->getConnection('core_write')->update(strval(Mage::getConfig()->getTablePrefix()) . 'catalog_product_option_type_value', array('customoptions_qty'=>$customoptionsQty), 'option_type_id = '.$optionTypeId);                                
                            }
                            
                            if ($sku!=='') {
                                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                                if (isset($product) && $product && $product->getId() > 0) {
                                    $item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);                                    
                                    $item->addQty($optionTotalQty);
                                    $item->save();
                                }
                            }
                        }    
                    }    
            }            

        }        
        
        return $this;
        
    }

    public function creditMemoRefund($observer) {
        if (!Mage::helper('customoptions')->isInventoryEnabled()) return $this;
        
        $orderItems = $observer->getEvent()->getCreditmemo()->getOrder()->getItemsCollection();                
        foreach ($orderItems as $orderItem) {                        
            $productOptions = $orderItem->getProductOptions();
            if (!isset($productOptions['options'])) continue;
            $qty = $orderItem->getQtyOrdered();
            foreach ($productOptions['options'] as $option) {                
                switch ($option['option_type']) {
                    case 'drop_down':
                    case 'radio':
                    case 'checkbox':                        
                    case 'multiple':
                        $optionId = $option['option_id'];
                        $customoptionsIsOnetime = Mage::getModel('catalog/product_option')->load($optionId)->getCustomoptionsIsOnetime();                        
                        $optionTypeIds = explode(',', $option['option_value']);
                        foreach ($optionTypeIds as $optionTypeId) {                        
                            $productOptionValueModel = Mage::getModel('catalog/product_option_value')->load($optionTypeId);
                            $customoptionsQty = $productOptionValueModel->getCustomoptionsQty();
                            $sku = $productOptionValueModel->getSku();
                            if ($customoptionsQty!=='' || $sku!='') {
                                if (isset($productOptions['info_buyRequest']['options_'.$optionId.'_qty'])) {
                                    $optionQty = intval($productOptions['info_buyRequest']['options_'.$optionId.'_qty']);
                                } elseif (isset($productOptions['info_buyRequest']['options_'.$optionId.'_'.$optionTypeId.'_qty'])) {
                                    $optionQty = intval($productOptions['info_buyRequest']['options_'.$optionId.'_'.$optionTypeId.'_qty']);    
                                } else {
                                    $optionQty = 1;
                                }                            
                                $optionTotalQty = ($customoptionsIsOnetime?$optionQty:$optionQty*$qty);
                                
                                if ($customoptionsQty!=='' && substr($customoptionsQty, 0, 1)!='x') {
                                    $customoptionsQty = $customoptionsQty + $optionTotalQty;
                                    // model 'catalog/product_option_value' - do not use!
                                    Mage::getSingleton('core/resource')->getConnection('core_write')->update(strval(Mage::getConfig()->getTablePrefix()) . 'catalog_product_option_type_value', array('customoptions_qty'=>$customoptionsQty), 'option_type_id = '.$optionTypeId);
                                }
                                
                                if ($sku!=='') {
                                    $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                                    if (isset($product) && $product && $product->getId() > 0) {
                                        $item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);                                    
                                        $item->addQty($optionTotalQty);
                                        $item->save();
                                    }
                                }                                
                            }    
                        }     
                }    
                    
            }
        }            
        return $this;                                              
    }

    /*public function createOrderItem($observer) {        
        $item = $observer->getEvent()->getItem();

        $children = $item->getChildrenItems();

        if ($item->getId() && empty($children)) {
            $qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();

            $optionsIdsIterator = Mage::getModel('sales/quote_item_option')
                    ->getCollection()
                    ->addFieldToFilter('item_id', $item->getQuoteItemId())
                    ->addFieldToFilter('code', 'option_ids')
                    ->getIterator();
            $optionIds = current($optionsIdsIterator);
            if ($optionIds) {
                $optionIds = $optionIds->getValue();
                $optionIds = explode(',', $optionIds);

                $quoteItem = Mage::getModel('sales/quote_item')
                        ->load($item->getQuoteItemId());

                foreach ($optionIds as $optionId) {
                    $typeIdIterator = Mage::getModel('sales/quote_item_option')
                            ->getCollection()
                            ->addFieldToFilter('item_id', $item->getQuoteItemId())
                            ->addFieldToFilter('code', 'option_' . $optionId)
                            ->getIterator();
                    $typeId = current($typeIdIterator);
                    $typeId = $typeId->getValue();

                    $typeValue = Mage::getModel('catalog/product_option_value')
                            ->load($typeId);
                    if ($typeValue->getCustomoptionsQty() != '' && is_numeric($typeValue->getCustomoptionsQty()) && $typeValue->getCustomoptionsQty() > 0) {
                        $qty = $typeValue->getCustomoptionsQty() - intval($quoteItem->getQty());
                        if ($qty < 0)
                            $qty = 0;
                        $title = Mage::getResourceSingleton('customoptions/product_option')
                                ->getTitle($typeId, 0);
                        
                        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $typeValue->getSku());
                        if (isset($product) && $product && $product->getId() > 0) {
                            $item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                            if ($item->checkQty($qty)) {
                                $item->subtractQty($quoteItem->getQty());
                                $item->save();
                            }
                        }

                        $typeValue->setCustomoptionsQty($qty);
                        $typeValue->save();
                        Mage::getResourceSingleton('customoptions/product_option')
                                ->setTitle($typeId, 0, $title);
                    }
                }
            }
        }

        return $this;
    }*/

    
    // ckeckout/cart
    public function checkQuoteItemQtyAndCustomerGroup($observer) {

        $quoteItem = $observer->getEvent()->getItem();
        /* @var $quoteItem Mage_Sales_Model_Quote_Item */
        if (!$quoteItem || !$quoteItem->getProductId() || !$quoteItem->getQuote() || $quoteItem->getQuote()->getIsSuperMode()) {
            return $this;
        }

               
        if (!Mage::helper('customoptions')->isInventoryEnabled() && !Mage::helper('customoptions')->isCustomerGroupsEnabled()) return $this;
        // product Qty
        $qty = Mage::app()->getRequest()->getParam('qty', false);
        if (!$qty) $qty = $quoteItem->getQty();
            
        // prepare post data
        $post = Mage::app()->getRequest()->getParams();            
        $options = false;
        if (isset($post['options'])) {
            $options = $post['options'];
        } else {
            $post = $quoteItem->getProduct()->getCustomOption('info_buyRequest')->getValue();
            if ($post) $post = unserialize ($post); else $post = array();
            if (isset($post['options'])) $options = $post['options'];
        }
        if ($options) {

            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if ($customer->getId()) $customerGroupId = $customer->getGroupId(); else $customerGroupId = 0;


            foreach ($options as $optionId => $option) {                     
                $productOption = Mage::getModel('catalog/product_option')->load($optionId);
                //$colorCode = $option->getOptionCode();

                // check Options Customer Group
                if (Mage::helper('customoptions')->isCustomerGroupsEnabled()) {
                    if(!in_array($customerGroupId, explode(',', $productOption->getCustomerGroups()))){
                        $fullMessage = Mage::helper('customoptions')->__('Some options are not available for your customer group. Please, edit product "%s"', $quoteItem->getProduct()->getName());
                        $message = Mage::helper('customoptions')->__('Some options are not available for your customer group');
                        
                        $quoteItem->setHasError(true)->setMessage($message);
                        if ($quoteItem->getParentItem()) {
                            $quoteItem->getParentItem()->setMessage($message);
                        }
                        $quoteItem->getQuote()->setHasError(true)->addMessage($fullMessage, 'options');
                        return $this;
                        break;
                    }
                }
                
                // check Options Inventory
                if (Mage::helper('customoptions')->isInventoryEnabled()) {
                    $optionType = $productOption->getType();                    
                    if ($optionType!='drop_down' && $optionType!='multiple' && $optionType!='radio' && $optionType!='checkbox') continue;                                        
                    if (!is_array($option)) $option = array($option);
                    foreach ($option as $optionTypeId) {
                        if (!$optionTypeId) continue;
                        $row = $productOption->getOptionValue($optionTypeId);                        
                        if (isset($row['customoptions_qty']) && substr($row['customoptions_qty'], 0, 1)!='x' && $row['customoptions_qty']!=='') {
                            switch ($optionType) {
                                case 'checkbox':                            
                                    if (isset($post['options_'.$optionId.'_'.$optionTypeId.'_qty'])) $optionQty = intval($post['options_'.$optionId.'_'.$optionTypeId.'_qty']); else $optionQty = 1;
                                    break;
                                case 'drop_down':
                                case 'radio':                                                    
                                    if (isset($post['options_'.$optionId.'_qty'])) $optionQty = intval($post['options_'.$optionId.'_qty']); else $optionQty = 1;
                                    break;
                                case 'multiple':
                                    $optionQty = 1;                            
                                    break;                       
                            }                        
                            $optionTotalQty = ($productOption->getCustomoptionsIsOnetime()?$optionQty:$optionQty*$qty);                            
                            if (intval($row['customoptions_qty'])<$optionTotalQty) {
                                $message = Mage::helper('cataloginventory')->__('The requested quantity for "%s" is not available.', $quoteItem->getProduct()->getName());

                                $quoteItem->setHasError(true)->setMessage($message);
                                if ($quoteItem->getParentItem()) {
                                    $quoteItem->getParentItem()->setMessage($message);
                                }
                                $quoteItem->getQuote()->setHasError(true)->addMessage($message, 'qty');
                                return $this;
                                break; break;
                            }                            
                        }
                    }
                }    
            }
        }

        return $this;
    }    
    
    // before create order -> setCustomOptionsDetails
    public function convertQuoteItemToOrderItem($observer) {
        $orderItem = $observer->getEvent()->getOrderItem();                
        $item = $observer->getEvent()->getItem();
        
        // multiplier - to order: 3 x Red
        $options = $item->getProductOrderOptions();        
        if (!$options) {
            Mage::helper('customoptions/product_configuration')->setCustomOptionsDetails($item);
            $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());            
            
            // htmlspecialchars_decode titles
            if (isset($options['options']) && is_array($options['options'])) {
                foreach ($options['options'] as $key=>$op) {
                    if (isset($op['label'])) $options['options'][$key]['label'] = htmlspecialchars_decode($op['label']);
                    if (isset($op['value'])) $options['options'][$key]['value'] = htmlspecialchars_decode($op['value']);
                }
            }            
            $orderItem->setProductOptions($options);
        }

        return $this;
    }
    
    // after create order - reduce inventory
    public function quoteSubmitSuccess($observer) {        
        // inventory
        if (Mage::helper('customoptions')->isInventoryEnabled()) {
            $orderItems = $observer->getEvent()->getOrder()->getAllItems();
            foreach ($orderItems as $orderItem) {
                $productOptions = $orderItem->getProductOptions();            
                if (!isset($productOptions['options'])) return $this;

                $qty = $orderItem->getQtyOrdered();
                foreach ($productOptions['options'] as $option) {                
                    switch ($option['option_type']) {
                        case 'drop_down':
                        case 'radio':
                        case 'checkbox':                        
                        case 'multiple':
                            $optionId = $option['option_id'];
                            $customoptionsIsOnetime = Mage::getModel('catalog/product_option')->load($optionId)->getCustomoptionsIsOnetime();                                                
                            $optionTypeIds = explode(',', $option['option_value']);
                            foreach ($optionTypeIds as $optionTypeId) {                        
                                $productOptionValueModel = Mage::getModel('catalog/product_option_value')->load($optionTypeId);
                                $customoptionsQty = $productOptionValueModel->getCustomoptionsQty();
                                $sku = $productOptionValueModel->getSku();
                                if ($customoptionsQty!=='' || $sku!='') {
                                    if (isset($productOptions['info_buyRequest']['options_'.$optionId.'_qty'])) {
                                        $optionQty = intval($productOptions['info_buyRequest']['options_'.$optionId.'_qty']);
                                    } elseif (isset($poductOptions['info_buyRequest']['options_'.$optionId.'_'.$optionTypeId.'_qty'])) {
                                        $optionQty = intval($productOptions['info_buyRequest']['options_'.$optionId.'_'.$optionTypeId.'_qty']);
                                    } else {
                                        $optionQty = 1;
                                    }                            
                                    $optionTotalQty = ($customoptionsIsOnetime?$optionQty:$optionQty*$qty);

                                    if ($customoptionsQty!=='' && substr($customoptionsQty, 0, 1)!='x' && $customoptionsQty>0) {
                                        $customoptionsQty = $customoptionsQty - $optionTotalQty;
                                        if ($customoptionsQty<0) $customoptionsQty = 0;
                                        // model 'catalog/product_option_value' - do not use!
                                        Mage::getSingleton('core/resource')->getConnection('core_write')->update(strval(Mage::getConfig()->getTablePrefix()) . 'catalog_product_option_type_value', array('customoptions_qty'=>$customoptionsQty), 'option_type_id = '.$optionTypeId);
                                    }    

                                    if ($sku!=='') {
                                        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                                        if (isset($product) && $product && $product->getId() > 0) {
                                            $item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);                                        
                                            if ($item->getQty() > 0) {
                                                if ($item->getQty() < $optionTotalQty) $optionTotalQty = intval($item->getQty());
                                                $item->subtractQty($optionTotalQty);
                                                $item->save();
                                            }                                        
                                        }
                                    }    

                                }    
                            }     
                    }    
                }
            }
        }
        return $this;
    }

    /**
     * use constructor layout update
     *
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function useConstructor(Varien_Event_Observer $observer)
    {
        /** @var $layout Mage_Core_Model_Layout */
        $layout = $observer->getEvent()->getLayout();

        $canUseConstructor = false;
        foreach ($layout->getUpdate()->getHandles() as $_handle) {
            if ($_handle == 'catalog_product_view') {
                $canUseConstructor = true;
            }
        }

        if ($canUseConstructor) {
            $product = Mage::registry('current_product');
            $useConstractor = $product->getUseConstructor();
            if ($useConstractor) {
                $layout->getUpdate()->addHandle('custom_catalog_product_view');
            }
        }
    }

    public function saveFormData()
    {
        Mage::getSingleton('checkout/session')->setAddToCartFormData(Mage::app()->getRequest()->getPost());
    }

    public function updateType(Varien_Event_Observer $observer){
        /** @var $quoteItem Mage_Sales_Model_Quote_Item */
        $quoteItem = $observer->getEvent()->getQuoteItem();

        // prepare post data
        $post = Mage::app()->getRequest()->getParams();

        $optionValues = false;
        if (isset($post['options'])) {
            $optionValues = $post['options'];
        } else {
            $post = $quoteItem->getProduct()->getCustomOption('info_buyRequest')->getValue();
            if ($post) $post = unserialize ($post); else $post = array();
            if (isset($post['options'])) $optionValues = $post['options'];
        }



        // get color values
        $options = $quoteItem->getProduct()->getOptions();
        $typeOption = null;
        $colorValues = array();
        foreach ($options as $_option) {

            if ($_option->getOptionCode() == 'type') {
                $typeOption = $_option;
            }


//echo $_option->getOptionCode().' '.strpos($_option->getOptionCode(), 'main-color'); 
//print_r($values);
//echo "<br/><br/>";
            if (strpos($_option->getOptionCode(), 'main-color') === 0) {
                $values = array();
                if (isset($optionValues[$_option->getOptionId()])) {
                    $optionValuesCurrent = !is_array($optionValues[$_option->getOptionId()]) ?
                        array($optionValues[$_option->getOptionId()])
                        : $optionValues[$_option->getOptionId()];
                    foreach ($_option->getValues() as $_value) {
                        if (in_array($_value->getOptionTypeId(), $optionValuesCurrent)) {
                            $values[] = $_value->getCode();
                        }
                    }
                }
                $colorValues[] = $values;
            }
        }
if(is_array($colorValues[0])) { 
$colorValues = $colorValues[0];
}
//print_r($colorValues);

        if ($typeOption) {

            //check if color is the same
            $type = '1';
            if ($colorValues) {
                $color = current ($colorValues);
                foreach ($colorValues as $_color) {
                    if ($color != $_color) {
                        $type = '2';
                        break;
                    }
                }
            }
            $typeValue = null;
            //set type value
$i=1;
            foreach ($typeOption->getValues() as $_value) {
//echo $i;
                if ($i == $type) {
                    $typeValue = $_value->getOptionTypeId();
                    $optionValues[$typeOption->getOptionId()] = $typeValue;
                    $post['options'] = $optionValues;
                }
$i++;
            }
//echo $typeOption->getOptionId().' '.$type.' '.$typeValue; die;
            if ($typeValue) {
                $buyRequest = $quoteItem->getProduct()->getCustomOption('info_buyRequest');
                $buyRequest->setValue(serialize($post));
                $option = $quoteItem->getProduct()->getCustomOption('option_' . $typeOption->getOptionId());
//echo $option->getCode();                
if ($option && $typeValue) {
                    $option->setValue($typeValue);
                }
//echo $option->getValue(); die;
            }

        }
    }
}
