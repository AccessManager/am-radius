<?php
namespace AccessManager\Radius\Interfaces;

Interface AccountingInterface {

	public function getCountableTime();

	public function getCountableData();

	public function requestCoA();

	public function requestDisconnect();
}