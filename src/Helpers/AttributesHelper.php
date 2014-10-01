<?php

namespace AccessManager\Radius\Helpers;

trait AttributesHelper {

	private $reply = [];
	private $check = [];


	public function getCheckAttributes()
	{
		return $this->check;
	}

	public function getReplyAttributes()
	{
		return $this->reply;
	}

	private function _addTimeLimit($sessionTime = 0)
	{

		if( $this->plan->haveTimeLimit() && ! $this->plan->haveAQAccess() )
			return $this->_addReply([
				'Session-Timeout'	=>	$this->plan->time_limit + $sessionTime
				]);
			return $this->_unlimitedTime();
		
	}

	private function _addDataLimit($sessionData = 0)
	{
		if( $this->plan->haveDataLimit() && ! $this->plan->haveAQAccess() ) {
			$limit = $this->plan->data_limit + $sessionData;
			if( $limit >= FOUR_GB ) {
				$this->_addReply([
					'Mikrotik-Total-Limit-Gigawords'	=> intval($limit / FOUR_GB),
					]);
				$limit = bcmod($limit, FOUR_GB);
			}
			return $this->_addReply([
					'Mikrotik-Total-Limit'	=>	$limit,
					]);
		}
		return $this->_unlimitedData();
	}

	private function _addCheck(Array $arr)
	{
		foreach ($arr as $k => $v) {
			$this->check[] = [
						 'username'	=> 	$this->plan->user->uname,
							   'op'	=>	':=',
						'attribute' =>	$k,
							'value' =>	$v,
						];
		}
	}

	private function _addReply(Array $arr)
	{
		foreach ($arr as $k => $v) {
			// echo " <br/> Adding Attribute $k -> $v <br/>";
			$this->reply[] = [
						 'username'	=> 	$this->plan->user->uname,
							   'op'	=>	':=',
						'attribute' =>	$k,
							'value' =>	$v,
						];
		}
	}

	private function _unlimitedTime()
	{
		// echo "Adding Unlimited Time";
		$this->_addReply(['Session-Timeout'=>0]);
	}

	private function _unlimitedData()
	{
		$this->_addReply(['Mikrotik-Total-Limit'=>0]);
	}

}

//end of file AttributesHelper.ph