<?php
/**
 * Landofcoder
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Landofcoder.com license that is
 * available through the world-wide-web at this URL:
 * http://landofcoder.com/license
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Landofcoder
 * @package    Lof_SubDomain
 * @copyright  Copyright (c) 2017 Landofcoder (https://landofcoder.com/)
 * @license    https://landofcoder.com/LICENSE-1.0.html
 */

namespace Lofmp\SubDomain\Model;

class Url extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Object Manager
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Initialize resource model
     *
     * @return void
     */
    public function __construct(
    	\Magento\Framework\Model\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectInterface,
    	\Magento\Framework\Registry $registry
    ) {
        $this->_objectManager = $objectInterface;
    	parent::__construct($context,$registry);
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('Lofmp\SubDomain\Model\ResourceModel\Url');
    }

    /**
     * Re-write existing URL
     *
     * @param string $routePath
     * @param int|null $storeId
     * @return string
     */
    public function reWriteExist($routePath, $storeId = null) 
    {  
        
        $collection = $this->_objectManager->create(\Magento\UrlRewrite\Model\UrlRewrite::class)->getCollection();

        $collection->addFieldToFilter('target_path', array('eq'=> $routePath))
                    ->addFieldToFilter('store_id', array('eq'=> $storeId))
                    ->setOrder('url_rewrite_id', 'DESC');

        $routePath = trim($routePath, '/');

        if(count($collection->getData()) == 0) {
            $collection->addFieldToFilter('target_path', array('eq'=> $routePath))
            ->addFieldToFilter('store_id', array('eq'=> $storeId))
            ->setOrder('url_rewrite_id', 'DESC');
        }

        $model = $collection->getFirstItem();
        
        if($model && $model->getId()) {
            return $model->getRequestPath();
        }
        return '';
    }
}