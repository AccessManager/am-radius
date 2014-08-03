<?php
namespace AccessManager\Radius\Helpers;
use AccessManager\Radius\User;

trait UserProfile {

	private $user;

	public function __construct(User $user)
	{
		$this->user = $user;
		Database::connect();
	}
}

//end of file Helpers