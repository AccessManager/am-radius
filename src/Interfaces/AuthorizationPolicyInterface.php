<?php

namespace AccessManager\Radius\Interfaces;

Interface AuthorizationPolicyInterface {

	public function makeTimeLimit($sessionTime);

	public function makeDataLimit($sessionData);

	public function makeBWPolicy($primaryPolicy);

	public function getCheckAttributes();

	public function getReplyAttributes();
}

//end of file AttributesInterface.php