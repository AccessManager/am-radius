<?php

namespace AccessManager\Radius\Crons;
use Illuminate\Database\Capsule\Manager as DB;
use AccessManager\Radius\Helpers\Database;
use DateTime;

class FrinternetCron {

	public function resetQuotaBalance()
	{
		$accounts = DB::table('free_balance as b')
						->select('b.plan_type','b.limit_type','b.time_limit','b.time_unit','b.time_balance'
							,'b.data_limit','b.data_unit','b.data_balance','b.reset_every','b.reset_unit','b.last_reset_on')
						->get();
		foreach( $accounts as $account )
		{
			$next_reset_on = DateTime::createFromFormat('Y-m-d H:i:s', $account->last_reset_on); //strtotime($account->last_reset_on);
			$next_reset_on->modify("+{$account->reset_every} {$account->reset_unit}");
			if( $next_reset_on->getTimestamp() <= time() ) {
				$this->_resetData($account);
			}
		}
	}

	private function _resetData($account)
	{
		$new['last_reset_on'] = date("Y-m-d H:i:s");
		if( $account->plan_type == LIMITED ) {

			if( $account->limit_type == TIME_LIMIT || $account->limit_type == BOTH_LIMITS )
				$new['time_balance'] = $account->time_limit * constant( $account->time_unit );
			if( $account->limit_type == DATA_LIMIT || $account->limit_type == BOTH_LIMITS )
				$new['data_balance'] = $account->data_limit * constant( $account->data_unit );
			if( $account->aq_access-> ALLOWED )
				$new['aq_invocked'] = 0;
		}
		DB::table('free_balance')
					->where('user_id', $account->user_id)
					->update(($new);
	}

	public function __construct()
	{
		Database::connect();
	}
}
//end of file FrinternetCron.php