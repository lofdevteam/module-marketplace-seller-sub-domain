<?php
/**
 * Landofcoder
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Landofcoder.com license that is
 * available through the world-wide-web at this URL:
 * https://landofcoder.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Landofcoder
 * @package    Lofmp_SubDomain
 * @copyright  Copyright (c) 2017 Landofcoder (https://landofcoder.com/)
 * @license    https://landofcoder.com/LICENSE-1.0.html
 */
namespace Lofmp\SubDomain\Model;


/**
 * Seller Model
 */
class Seller extends \Lof\MarketPlace\Model\Seller
{	

    /**
     * get url
     * 
     * @return string
     */
    public function getUrl()
    {
        if(!$this->_sellerHelper->getConfig('general_settings/enable_subdomain')) {
            return;
        }
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance ();
        $seller_url = $objectManager->create('Lofmp\SubDomain\Model\Url');
        $subdomain = $seller_url->load($this->getUrlKey(),'url_key');
         
        $url = $this->_storeManager->getStore()->getBaseUrl();
        //$route = $this->_sellerHelper->getConfig('general_settings/route');
        $url_prefix = $this->_sellerHelper->getConfig('general_settings/url_prefix');
        $urlPrefix = '';
        if($url_prefix){
            $urlPrefix = $url_prefix.'/';
        }
        $url_suffix = $this->_sellerHelper->getConfig('general_settings/url_suffix');
      
        if($subdomain->getData()) {
            return $subdomain->getRequestPath();
        }
        return $url.$urlPrefix.$this->getUrlKey().$url_suffix;
    }
}