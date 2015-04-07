<?php
namespace AccessManager\Radius\Crons;
use Illuminate\Database\Capsule\Manager as DB;
use AccessManager\Radius\Helpers\Database;
use Carbon\Carbon;

class APCron {

	public function resetQuotaBalance()
	{

		$accounts = DB::table('ap_active_plans as p')
					->leftJoin('ap_limits as l','l.id','=','p.limit_id')
					->join('billing_cycles as c','c.user_id','=','p.user_id')
					->where( 'plan_type', LIMITED )
					->select('l.limit_type','p.time_balance','p.data_balance','l.time_limit','l.time_unit','l.aq_access',
						'l.aq_access','l.data_limit','l.data_unit','p.validity','p.validity_unit','p.last_reset_on',
						'p.user_id')
					->get();

		foreach( $accounts as $account ) {
			$next_reset_on = ( new Carbon( $account->last_reset_on ) )
								->modify("+{$account->validity} {$account->validity_unit}");
			$now = new Carbon;
			if( $next_reset_on < $now ){
				$this->_resetData( $account );
			}
		}
	}

	private function _resetData( $account )
	{
		if( $account->limit_type == TIME_LIMIT || $account->limit_type == BOTH_LIMITS )
			$new['time_balance']	=	$account->time_limit * constant( $account->time_unit );
		if( $account->limit_type == DATA_LIMIT || $account->limit_TYPE == BOTH_LIMITS )
			$new['data_balance']	=	$account->data_limit * constant( $account->data_unit );
		if( $account->aq_access == ALLOWED )
			$new['aq_invocked']	= 0;

		$new['last_reset_on'] = date('Y-m-d H:i:s');

		DB::table('ap_active_plans')
			->where('user_id', $account->user_id )
			->update($new);
	}

	public function __construct()
	{
		Database::connect();
	}
}
//end of file APCron.php