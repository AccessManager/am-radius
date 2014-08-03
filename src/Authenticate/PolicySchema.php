<?php

namespace AccessManager\Radius\Authenticate;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class PolicySchema {

	private $schema_id;

	public function __call($name, $arguments)
	{
		$days = [
				'Monday'	=>	'mo',
				'Tuesday'	=>	'tu',
				'Wednesday'	=>	'we',
				'Thursday'	=>	'th',
				'Friday'	=>	'fr',
				'Saturday'	=>	'sa',
				'Sunday'	=>	'su',
				];
		if( array_key_exists($name, $days) ) {
			
			return $this->_findSchema($days[$name]);
		}
		throw new Exception("Not a valid Day of Week.");
	}

	private function _findSchema($column)
	{
		Database::connect();
		$tpl = Capsule::table('voucher_policy_schemas as p')
						->select('t.id','t.access','t.bw_policy','t.bw_accountable','t.from_time',
								'to_time','t.pr_allowed','t.pr_policy','t.pr_accountable',
								't.sec_allowed','t.sec_policy','t.sec_accountable')
						->join('voucher_policy_schema_templates as t','t.id','=',"p.{$column}")
						->where('p.id',$this->schema_id)
						->first();

		return new SchemaTemplate($tpl);
	}

	public function __construct($schema_id)
	{
		$this->schema_id = $schema_id;
	}
}

//end of file PolicySchema.php