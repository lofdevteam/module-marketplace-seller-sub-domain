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

namespace Lofmp\SubDomain\Model\Core;

class Url extends \Magento\Framework\Url
{
	/**
	 * @var string
	 */
    protected $_direct = '';

	/**
	 * @var string
	 */
    protected $_scopeType;
	
	/**
	 * @var \Magento\Framework\Url\RouteParamsPreprocessorInterface
	 */
	protected $routeParamsPreprocessor;

	/**
	 * @var \Magento\Framework\ObjectManagerInterface
	 */
	protected $_objectManager;

    /**
	 * constructor
	 *
     * @param \Magento\Framework\App\Route\ConfigInterface $routeConfig
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Url\SecurityInfoInterface $urlSecurityInfo
     * @param \Magento\Framework\Url\ScopeResolverInterface $scopeResolver
     * @param \Magento\Framework\Session\Generic $session
     * @param \Magento\Framework\Session\SidResolverInterface $sidResolver
     * @param \Magento\Framework\Url\RouteParamsResolverFactory $routeParamsResolverFactory
     * @param \Magento\Framework\Url\QueryParamsResolverInterface $queryParamsResolver
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param string $scopeType
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
	public function __construct(
		\Magento\Framework\App\Route\ConfigInterface $routeConfig,
		\Magento\Framework\App\RequestInterface $request,
		\Magento\Framework\Url\SecurityInfoInterface $urlSecurityInfo,
		\Magento\Framework\Url\ScopeResolverInterface $scopeResolver,
		\Magento\Framework\Session\Generic $session,
		\Magento\Framework\Session\SidResolverInterface $sidResolver,
		\Magento\Framework\Url\RouteParamsResolverFactory $routeParamsResolverFactory,
		\Magento\Framework\Url\QueryParamsResolverInterface $queryParamsResolver,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Url\RouteParamsPreprocessorInterface $routeParamsPreprocessor,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		$scopeType,
		array $data = []
	) {
		$this->_objectManager = $objectManager;
        parent::__construct($routeConfig, $request, $urlSecurityInfo, $scopeResolver, $session, $sidResolver, $routeParamsResolverFactory, $queryParamsResolver, $scopeConfig, $routeParamsPreprocessor, $scopeType, $data);
	}


	/**
     * Build url by requested path and parameters
     *
     * @param   string $routePath
     * @param   array $routeParams
     * @return  string
     */
    public function getUrl($routePath = null, $routeParams = null)
    {
		$routeParams = $this->routeParamsPreprocessor
            ->execute($this->_scopeResolver->getAreaCode(), $routePath, $routeParams);
        
		$route = @explode('/',$routePath);
		if(isset($route[0]) && $route[0] == '*')  $route[0] = $this->_request->getModuleName();
		if(isset($route[1]) && $route[1] == '*')  $route[1] = $this->_request->getControllerName();
		if(isset($route[2]) && $route[2] == '*')  $route[2] = $this->_request->getActionName();
		$route = @implode('/',$route);
		
		/* check for rewrite url if exist */
		$store_id = $this->_objectManager->get(\Lof\MarketPlace\Helper\Data::class)->getStore()->getId();
		$this->_direct = $this->_objectManager->create(\Lofmp\SubDomain\Model\Url::class)->reWriteExist($route,$store_id);
		
		if(@strlen($this->_direct) == 0) {
			$route = '';
			$route = @explode('/',$routePath);
			if(isset($route[0]) && $route[0] == '*')  $route[0] = $this->_request->getModuleName();
			if(isset($route[1]) && $route[1] == '*')  $route[1] = $this->_request->getControllerName();
			if((isset($route[2]) && $route[2] == '*') || (isset($route[2]) && $route[2] == '') || !isset($route[2]))  $route[2] = $this->_request->getActionName();
			$route = @implode('/',$route);
			$this->_direct = $this->_objectManager->create(\Lofmp\SubDomain\Model\Url::class)->reWriteExist($route,$store_id);
			
			if(@strlen($this->_direct) == 0 && $this->_request->getActionName() == 'index') {
				$route = '';
				$route = @explode('/',$routePath);
				if(isset($route[0]) && $route[0] == '*')  $route[0] = $this->_request->getModuleName();
				if(isset($route[1]) && $route[1] == '*')  $route[1] = $this->_request->getControllerName();
				$route = @implode('/',$route);
				$this->_direct = $this->_objectManager->create('Lofmp\SubDomain\Model\Url')->reWriteExist($route,$store_id);
			}
		}
		
		$route = @explode('/',$routePath);
	
		for ($i=3, $l= sizeof($route); $i<$l; $i+=2) {
            $routeParams[$route[$i]] = isset($route[$i+1]) ? urldecode($route[$i+1]) : '';
        }
		
		if ($this->_direct != '' && @substr($this->_direct, -1, 1) !== '/') {
			$this->_direct.= '/';
		}
		
		if(!isset($routeParams['_direct']) && strlen($this->_direct) > 0 && !isset($routeParams['_direct'])) {	
			$routeParams['_direct'] = $this->_direct;			
		}		
		
		/* genrate the url */
		$url = parent::getUrl($routePath, $routeParams);
		
		/* Add Params if direct url set */
		if ($routeParams && strlen($this->_direct) > 0) {
			foreach ($routeParams as $key=>$value) {
				if (is_null($value) || false===$value || ''===$value || !is_scalar($value) || $key == '_direct') {
					continue;
				}
				if(parse_url($url, PHP_URL_QUERY)) {
					$url .= '&'.$key.'='.$value;
				} else {
					$url .= '?'.$key.'='.$value;
				}
			}
		}
		$this->_direct = '';
		
        return $url;
    }
	
}
