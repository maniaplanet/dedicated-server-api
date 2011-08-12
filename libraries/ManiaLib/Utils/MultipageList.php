<?php
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Utils;

/**
 * This class helps to create multipage lists. Maybe difficult to use at
 * first... Doc should be written about it...
 */
class MultipageList
{
	/**#@+
	 * @ignore
	 */
	protected $size;
	protected $urlParamName = 'page';
	protected $urlPageName = null;
	protected $currentPage;
	protected $defaultPage=1;
	protected $perPage;
	protected $pageNumber;
	protected $hasMorePages;
	/**#@-*/
	
	/**
	 * @var \ManiaLib\Gui\Cards\PageNavigator
	 */
	public $pageNavigator;

	function __construct($perPage = 8)
	{
		$this->pageNavigator = new \ManiaLib\Gui\Cards\PageNavigator;
		$this->perPage = $perPage;
	}

	function setSize($size)
	{
		$this->size = $size;
		if($this->getCurrentPage() > $this->getPageNumber())
			$this->currentPage = $this->getPageNumber();
	}
	
	function setPerPage($perPage)
	{
		$this->perPage = $perPage;
	}
	
	function setCurrentPage($page)
	{
		$this->currentPage = $page;
	}
	
	function setDefaultPage($page)
	{
		$this->defaultPage = $page;
	}
	
	function setUrlParamName($name)
	{
		$this->urlParamName = $name;
	}
	
	function setUrlPageName($file)
	{
		$this->urlPageName = $file;
	}

	function goToLastPage()
	{
		$this->currentPage = $this->getPageNumber();
	}
	
	function getCurrentPage()
	{
		if($this->currentPage === null)
		{
			$request = \ManiaLib\Application\Request::getInstance();
			$this->currentPage = (int) $request->get($this->urlParamName, $this->defaultPage);
		}
		if( $this->currentPage < 1)
		{
			$this->currentPage = 1;
		}
		return $this->currentPage;
		
	}
		
	function getPageNumber()
	{
		if(!$this->pageNumber && $this->perPage)
		{
			$this->pageNumber = ceil($this->size/$this->perPage);
		}
		return $this->pageNumber;
	}
	
	/**
	 * @return array[int] offset, length
	 */
	function getLimit()
	{
		$offset = ($this->getCurrentPage()-1)*$this->perPage;
		$length = $this->perPage;
		return array($offset, $length); 
	}
	
	function setHasMorePages($hasMorePages)
	{
		$this->hasMorePages = $hasMorePages;
	}
	
	function checkArrayForMorePages(&$array)
	{
		list($offset, $length) = $this->getLimit();
		$hasMorePages = (count($array) == $length + 1);
		if($hasMorePages)
		{
			array_pop($array);
		} 
		$this->hasMorePages = $hasMorePages;	
	}
	
	 function addPlayerId()
	 {
		$this->pageNavigator->arrowNext->addPlayerId();
		$this->pageNavigator->arrowPrev->addPlayerId();
		$this->pageNavigator->arrowFastNext->addPlayerId();
		$this->pageNavigator->arrowFastPrev->addPlayerId();
		$this->pageNavigator->arrowLast->addPlayerId();
		$this->pageNavigator->arrowFirst->addPlayerId();
	 }
	
	function savePageNavigator()
	{
		$request = \ManiaLib\Application\Request::getInstance();
		
		if($this->hasMorePages !== null)
		{
			if($this->hasMorePages)
			{
				$this->setSize($this->getCurrentPage()*$this->perPage + 1);
			}
			else
			{
				$this->setSize($this->getCurrentPage()*$this->perPage);
			}
		}
		
		if($this->getPageNumber() > 1)
		{
			$ui = $this->pageNavigator;		
			$ui->setPageNumber($this->getPageNumber());
			$ui->setCurrentPage($this->getCurrentPage());

			if($ui->isLastShown())
			{
				$request->set($this->urlParamName, 1);
				$ui->arrowFirst->setManialink($request->createLink($this->urlPageName));
				
				$request->set($this->urlParamName, $this->getPageNumber());
				$ui->arrowLast->setManialink($request->createLink($this->urlPageName));
			}
			
			if($ui->isFastNextShown())
			{
				$request->set($this->urlParamName, $this->currentPage+5);
				$ui->arrowFastNext->setManialink($request->createLink($this->urlPageName));
				
				$request->set($this->urlParamName, $this->currentPage-5);
				$ui->arrowFastPrev->setManialink($request->createLink($this->urlPageName));
			}
				
			
			if($this->currentPage < $this->pageNumber)
			{
				$request->set($this->urlParamName, $this->currentPage+1);
				$ui->arrowNext->setManialink($request->createLink($this->urlPageName));
			}
			
			if($this->currentPage > 1)
			{
				$request->set($this->urlParamName, $this->currentPage-1);
				$ui->arrowPrev->setManialink($request->createLink($this->urlPageName));
			}
			
			$request->set($this->urlParamName, $this->currentPage);
			
			$ui->save();
		}
	}
}

 
?>