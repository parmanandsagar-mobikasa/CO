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
 * @copyright  Copyright (c) 2011 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Advanced Product Options extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @author     MageWorx Dev Team
 */
class MageWorx_CustomOptions_Model_Catalog_Product_Type_Price extends Mage_Catalog_Model_Product_Type_Price {

    const QTY_OPTION_CODE = 'size';

    /**
     * get qty option code
     *
     * @return string
     */
    protected function _getQtyOptionCode()
    {
        return self::QTY_OPTION_CODE;
    }

    /**
     * Apply options price
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int $qty
     * @param double $finalPrice
     * @return double
     */         
     
    protected function _applyOptionsPrice($product, $qty, $finalPrice)
    {
        if ($optionIds = $product->getCustomOption('option_ids')) {
            $basePrice = $finalPrice;
            $finalPrice = 0;
            $options = array();
            $qtyRelatedOption = null;
            foreach (explode(',', $optionIds->getValue()) as $optionId) {
                if ($option = $product->getOptionById($optionId)) {
                    $options[] = $option;
                    if ($option->getOptionCode() == $this->_getQtyOptionCode()) {
                        $qtyRelatedOption = $option;
                    }
                }
            }
            foreach ($options as $option) {
                $optionQty = null;
                $quoteItemOptionInfoBuyRequest = unserialize($product->getCustomOption('info_buyRequest')->getValue());
                switch ($option->getType()) {
                    case 'checkbox':
                        if (isset($quoteItemOptionInfoBuyRequest['options'][$optionId])) {
                            $optionValues = array();
                            $optionQtyArr = array();
                            foreach ($option->getValues() as $key=>$itemV) {
                                if (isset($quoteItemOptionInfoBuyRequest['options_'.$optionId.'_'.$itemV->getOptionTypeId().'_qty'])) $optionQty = intval($quoteItemOptionInfoBuyRequest['options_'.$optionId.'_'.$itemV->getOptionTypeId().'_qty']); else $optionQty = 1;
                                $optionQtyArr[$itemV->getOptionTypeId()] = $optionQty;
                            }
                            $optionQty = $optionQtyArr;
                            break;
                        }
                        break;
                    case 'drop_down':
                    case 'radio':
                        if (isset($quoteItemOptionInfoBuyRequest['options_'.$optionId.'_qty'])) $optionQty = intval($quoteItemOptionInfoBuyRequest['options_'.$optionId.'_qty']); else $optionQty = 1;
                    case 'multiple':
                        if (!isset($optionQty)) $optionQty = 1;
                        break;
                    case 'field':
                    case 'area':
                    case 'file':
                    case 'date':
                    case 'date_time':
                    case 'time':
                        break;
                    default: //multiple
                        $optionQty = 1;
                }
                $option->setOptionQty($optionQty);
            }

            $customQty = 0;
            if ($qtyRelatedOption) {
                foreach ($qtyRelatedOption->getValues() as $_value) {
                    if (isset($quoteItemOptionInfoBuyRequest['options_'.$qtyRelatedOption->getOptionId().'_'.$_value->getOptionTypeId().'_qty'])) {
                        $customQty += intval($quoteItemOptionInfoBuyRequest['options_'.$qtyRelatedOption->getOptionId().'_'.$_value->getOptionTypeId().'_qty']);
                    }
                }

                if ($customQty) {
                    $basePrice = $product->getTierPrice($customQty);
                }
            }

            foreach ($options as $option) {
                switch ($option->getType()) {
                    case 'field':
                    case 'area':
                    case 'file':
                    case 'date':
                    case 'date_time':
                    case 'time':
                        $finalPrice += $this->_getCustomOptionsChargableOptionPrice($option->getPrice(), $option->getPriceType() == 'percent', $basePrice, $qty, $option->getCustomoptionsIsOnetime());
                        break;
                    case 'drop_down':
                    case 'radio':
                    case 'checkbox':
                        $quoteItemOption = $product->getCustomOption('option_' . $option->getId());
                        $group = $option->groupFactory($option->getType())->setOption($option)->setQuoteItemOption($quoteItemOption);
                        $optionPrice = $group->getOptionPrice($quoteItemOption->getValue(), $basePrice, ($option != $qtyRelatedOption) ? $customQty : $qty,  $option->getOptionQty());
                        if ($option != $qtyRelatedOption && $customQty) {
                            $optionPrice *= $customQty;
                        }
                        $finalPrice += $optionPrice;
                }
            }

            if (!Mage::helper('customoptions')->isAbsolutePricesEnabled() || $finalPrice==0) $finalPrice += ($customQty) ? $customQty * $basePrice : $basePrice;
        }        
        return $finalPrice;
    }

    protected function _getCustomOptionsChargableOptionPrice($price, $isPercent, $basePrice, $qty = 1, $customoptionsIsOnetime = 0) {
        $sub = 1;
        if ($customoptionsIsOnetime) {
            $sub = $qty;
        }
        if ($isPercent) {
            return ($basePrice * $price / 100) / $sub;
        } else {
            return $price / $sub;
        }
    }

}