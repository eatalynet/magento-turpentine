<?php

/**
 * Nexcess.net Turpentine Extension for Magento
 * Copyright (C) 2012  Nexcess.net L.L.C.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

class Nexcessnet_Turpentine_Helper_Ban extends Mage_Core_Helper_Abstract {
    /**
     * Get the regex for banning a product page from the cache, including
     * any parent products for configurable/group products
     *
     * @param  Mage_Catalog_Model_Product $product
     * @return string
     */
    public function getProductBanRegex($product) {
        $urlPatterns = array();
        foreach ($this->getParentProducts($product) as $parentProduct) {
            if ($parentProduct->getUrlKey()) {
                $urlPatterns[] = $parentProduct->getUrlKey();
            }
        }
        if ($product->getUrlKey()) {
            $urlPatterns[] = $product->getUrlKey();
        }
        if (empty($urlPatterns)) {
            $urlPatterns[] = "##_NEVER_MATCH_##";
        }
        $pattern = sprintf('(?:%s)', implode('|', $urlPatterns));
        return $pattern;
    }

    /**
     * Get parent products of a configurable or group product
     *
     * @param  Mage_Catalog_Model_Product $childProduct
     * @return array
     */
    public function getParentProducts($childProduct) {
        $parentProducts = array();
        foreach (array('configurable', 'grouped') as $pType) {
            foreach (Mage::getModel('catalog/product_type_'.$pType)
                    ->getParentIdsByChild($childProduct->getId()) as $parentId) {
                $parentProducts[] = Mage::getModel('catalog/product')
                    ->load($parentId);
            }
        }
        return $parentProducts;
    }

    /**
     * Get category url key. When saving a category for a particular store view, the saved category object doesn't have the url key, so it should be retrieved from the default config.
     *
     * @param Mage_Catalog_Model_Category $category
     * @return string
     */
    public function getCategoryUrlKey($category)
    {
        if ($urlKey = $category->getUrlKey()) {
            return $urlKey;
        }

        /** @var Mage_Catalog_Model_Resource_Category_Collection $collection */
        $collection = Mage::getResourceModel('catalog/category_collection');
        $collection->addAttributeToSelect('url_key')
            ->addAttributeToFilter('entity_id', $category->getEntityId());

        $defaultCategory = $collection->getFirstItem();

        if ($defaultCategory) {
            return $defaultCategory->getData('url_key') ?: false;
        }

        return false;
    }

    /**
     * @param Mage_Cms_Model_Page $page
     * @return array
     */
    public function getHomePageUrls($page)
    {
        $homeUrls = [];
        $stores = [];
        if (is_array($page->getStoreId())) {
            $stores = $page->getStoreId();
        } else if (is_array($page->getStores())) {
            $stores = $page->getStores();
        } else if ($page->getStoreId() && is_numeric($page->getStoreId())) {
            $stores = array($page->getStoreId());
        }

        foreach ($stores as $storeId) {
            if ($page->getIdentifier() == Mage::getStoreConfig('web/default/cms_home_page', $storeId)) {

                $homeUrls[] = $this->getHomeUrlWithoutDomain($storeId);
            }
        }

        return $homeUrls;
    }

    /**
     * @param $storeId
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getHomeUrlWithoutDomain($storeId)
    {
        return rtrim(str_replace(
            Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
            '',
            Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK)
        ), '/') . '/?';
    }
}
