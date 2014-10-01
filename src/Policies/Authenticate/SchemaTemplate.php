<?php

namespace AccessManager\Radius\Policies\Authenticate;
use Exception;

class SchemaTemplate {

	private $fromTime;
	private $toTime;
	private $tpl;
	private $params = [];

	//Following methods check for Authentication.
	
	public function isAllowed()
	{
		if( $this->tpl->access == PARTIAL )
			return $this->_checkPartialAllowed();
		return $this->tpl->access;
	}

	private function _checkPartialAllowed()
	{
		if( $this->isInPrimaryTime() && $this->isPrimaryAllowed() )
			return TRUE;
		if( $this->isInSecondaryTime() && $this->isSecondaryAllowed())
			return TRUE;
		return FALSE;
	}

	public function isPrimaryAllowed()
	{
		return $this->tpl->pr_allowed;
	}

	public function isSecondaryAllowed()
	{
		return $this->tpl->sec_allowed;
	}
	
	//following methods help with Authorization
	
	public function haveFullDayAccess()
	{
		return $this->tpl->access == ALLOWED;
	}

	//following methods check for Accounting

	public function isAccountable()
	{
		if( $this->haveFullDayAccess() && $this->tpl->bw_accountable )
			return TRUE;
		if( $this->isInPrimaryTime() && $this->isPrimaryAccountable() )
			return TRUE;
		if( $this->isInSecondaryTime() && $this->isSecondaryAccountable() )
			return TRUE;
		return FALSE;
	}

	public function isPrimaryAccountable()
	{
		return $this->tpl->pr_accountable;
	}

	public function isSecondaryAccountable()
	{
		return $this->tpl->sec_accountable;
	}

	//common functions

	public function isInPrimaryTime()
	{
		$now = time();
		if( $now > $this->fromTime && $now < $this->toTime)
			return TRUE;
		return FALSE;
	}

	public function isInSecondaryTime()
	{
		return ! $this->isInPrimaryTime();
	}

	private function _makeParameters()
	{
		$this->fromTime = strtotime(date('Y-m-d ').$this->tpl->from_time);
		  $this->toTime = strtotime(date('Y-m-d ').$this->tpl->to_time);
	}

	public function __get($name)
	{
		if( property_exists($this->tpl, $name))
			return $this->tpl->$name;
		throw new Exception("Could not find property named: $name");
	}

	public function __construct($template)
	{
		$this->tpl = $template;
		$this->_makeParameters();
	}

}