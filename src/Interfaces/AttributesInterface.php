<?php

namespace AccessManager\Radius\Interfaces;

Interface AttributesInterface {

	public function makeTimeLimit($sessionTime);

	public function makeDataLimit($sessionData);

	public function makeBWPolicy();

	public function getCheckAttributes();

	public function getReplyAttributes();
}

//end of file AttributesInterface.php