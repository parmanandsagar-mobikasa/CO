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

$installer->run("
-- DROP TABLE IF EXISTS {$installer->getTable('customoptions/option_block_title')};
CREATE TABLE IF NOT EXISTS `{$installer->getTable('customoptions/option_block_title')}` (
  `option_block_title_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `option_id` int(10) unsigned NOT NULL DEFAULT '0',
  `store_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `block_title` text,
  PRIMARY KEY (`option_block_title_id`),
  KEY `MAGEWORX_CUSTOM_OPTIONS_BLOCK_TITLE_OPTION` (`option_id`),
  KEY `MAGEWORX_CUSTOM_OPTIONS_BLOCK_TITLE_STORE` (`store_id`),
  CONSTRAINT `FK_MAGEWORX_CUSTOM_OPTIONS_BLOCK_TITLE_OPTION` FOREIGN KEY (`option_id`) REFERENCES `catalog_product_option` (`option_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_MAGEWORX_CUSTOM_OPTIONS_BLOCK_TITLE_STORE` FOREIGN KEY (`store_id`) REFERENCES `core_store` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
$installer->endSetup();
