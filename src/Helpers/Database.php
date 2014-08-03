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

		$config = require_once __DIR__."/../../../../app/config/database.php";
		$capsule->addConnection($config['connections']['mysql']);

		$capsule->setFetchMode(PDO::FETCH_CLASS);
		$capsule->setEventDispatcher( new Dispatcher( new Container ) );
		$capsule->setAsGlobal();
		// return $capsule;
	}
}