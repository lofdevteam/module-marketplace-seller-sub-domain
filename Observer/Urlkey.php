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

namespace Lofmp\SubDomain\Observer;

use Magento\Framework\Event\ObserverInterface;

class Urlkey implements ObserverInterface
{
	/**
     * @var helper
     */
    protected $helper;

    /**
     * @var customerSession
     */
    private $customerSession;

    /**
     * @var \Lof\MarketPlace\Model\Seller
     */
    protected $seller;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $_file;

    /**
     * @var \Lofmp\SubDomain\Model\Url
     */
    protected $url;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Lof\MarketPlace\Helper\Data $helper
     * @param \Lofmp\SubDomain\Model\Url $url
     * @param \Lof\MarketPlace\Model\Seller $seller
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     */
	public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\ResourceConnection $resource,
        \Lof\MarketPlace\Helper\Data $helper,
        \Lof\MarketPlace\Model\Seller $seller,
        \Lofmp\SubDomain\Model\Url $url,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    ) {
        $this->url = $url;
        $this->seller = $seller;
        $this->_directoryList = $directoryList;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->_resource = $resource;
    }

     /**
     * Upgrade customer password hash when customer has logged in
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
    	if(!$this->helper->getConfig('general_settings/enable_subdomain')) {
            return;
        }
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $baseUrl = $storeManager->getStore()->getBaseUrl();
        $data = $observer->getData('data');
        $urlkey = isset($data['url'])?$data['url']:(isset($data['url_key'])?$data['url_key']:"");

        if(!$urlkey) return;
            
        if(strpos($baseUrl,'http://')!==false) {
            $data['request_path'] = str_replace('http://','http://'.$urlkey.'.',$baseUrl);
        } elseif(strpos($baseUrl,'https://')!==false) {
            $data['request_path'] = str_replace('http://','http://'.$urlkey.'.',$baseUrl);
        }
        
        if(isset($data['seller_id'])) {
            $seller_id = $data['seller_id'];
            $url = $this->url->load($seller_id,'seller_id');
        } else {
            $url = $this->url;
        }

        $filePath = "/lofmp/subdomain";
        $pdfPath = $this->_directoryList->getPath('media').$filePath;
        if (!is_dir($pdfPath)) {
            @mkdir($pdfPath,0775,true);
            //$ioAdapter = $this->_file;
            //$ioAdapter->mkdir($pdfPath, 0775);
        }
        $htaccess = file_get_contents($pdfPath.'/.htaccess');
        $content = '';
        //$route = $this->helper->getConfig('general_settings/route');
        $url_prefix = $this->helper->getConfig('general_settings/url_prefix');
        $urlPrefix = '';
        if($url_prefix){
            $urlPrefix = $url_prefix.'/';
        }
        $url_suffix = $this->helper->getConfig('general_settings/url_suffix');
        $data['target_path'] = $baseUrl.$urlPrefix.$data['url_key'].$url_suffix;
        if(empty($url->getId())) {
           
            $url->setName($data['name'])->setRequestPath($data['request_path'])->setTargetPath($data['target_path'])->setSellerId($data['seller_id'])->setUrlKey($data['url_key']);
            $url->save(); 
  
            foreach ($this->url->getCollection() as $key => $_url) {
                $content .= '
    RewriteCond %{HTTP_HOST} ^'.$_url->getData('request_path').'
    RewriteRule ^(.*) '.$_url->getData('target_path');
            }

            $htaccess = str_replace('RewriteCond %{REQUEST_METHOD} ^TRAC[EK]
    RewriteRule .* - [L,R=405]','RewriteCond %{REQUEST_METHOD} ^TRAC[EK]
    RewriteRule .* - [L,R=405]

    '.$content,$htaccess);
           
            @file_put_contents($this->_directoryList->getRoot().'/.htaccess', $htaccess );
        } else {
            $seller = $this->seller->load($data['seller_id'],'seller_id');
            //$data['target_path'] = $seller->getUrl();
            if($url->getData('url_key') != $urlkey) {
     
                $url->setName($data['name'])->setRequestPath($data['request_path'])->setTargetPath($data['target_path'])->setSellerId($data['seller_id'])->setUrlKey($data['url_key']);
                $url->save(); 
                foreach ($this->url->getCollection() as $key => $_url) {
                    $content .= '
    RewriteCond %{HTTP_HOST} ^'.$_url->getData('request_path').'
    RewriteRule ^(.*) '.$_url->getData('target_path');
                }

                $htaccess = str_replace('RewriteCond %{REQUEST_METHOD} ^TRAC[EK]
    RewriteRule .* - [L,R=405]','RewriteCond %{REQUEST_METHOD} ^TRAC[EK]
    RewriteRule .* - [L,R=405]

    '.$content,$htaccess);
              
                @file_put_contents($this->_directoryList->getRoot().'/.htaccess', $htaccess );
            }
        }
    }
}