<?php

namespace AccessManager\Radius\Helpers;

use PDO;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class Database {

	public static function connect()
	{
		$capsule = new Capsule;

		$db = require_once __DIR__."/../../../../../../app/config/database.php";
		$config = array(
			'driver'    => 'mysql',
			'host'      => 'localhost',
			'database'  => 'am-laravel',
			'username'  => 'root',
			'password'  => 'Fj460192dk',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		);
		$capsule->addConnection($config);

		$capsule->setFetchMode(PDO::FETCH_CLASS);
		$capsule->setEventDispatcher( new Dispatcher( new Container ) );
		$capsule->setAsGlobal();
		// return $capsule;
	}
}