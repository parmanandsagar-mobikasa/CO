<?php
/**
 *  extension for Magento
 *
 * Long description of this file (if any...)
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * 
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade
 * the MageWorx CustomOptions module to newer versions in the future.
 * If you wish to customize the MageWorx CustomOptions module for your needs
 * please refer to http://www.magentocommerce.com for more information.
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @copyright  Copyright (C) 2012 
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @var $this MageWorx_CustomOptions_Model_Mysql4_Setup
 */

$installer = $this;
$installer->startSetup();

if (!$installer->getConnection()->tableColumnExists($installer->getTable('catalog/product_option_type_value'), 'code')) {
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog/product_option_type_value'),
        'code',
        "varchar(255) NOT NULL default ''"
    );
}

$installer->endSetup();
