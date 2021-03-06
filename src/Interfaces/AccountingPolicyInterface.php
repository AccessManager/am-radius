<?php
namespace AccessManager\Radius\Interfaces;

Interface AccountingPolicyInterface {

	public function getCountableTime();

	public function getCountableData();

	public function requestCoA();

	public function requestDisconnect();
}