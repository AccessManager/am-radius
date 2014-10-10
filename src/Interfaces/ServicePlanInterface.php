<?php

namespace AccessManager\Radius\Interfaces;

Interface ServicePlanInterface {

	public function fetchPlanDetails( $acctsessionid, $acctuniqueid);

	public function limitExpired();

	public function haveTimeLimit();

	public function haveDataLimit();

	public function isAllowed();

	public function isActive();

	public function isLimited();

	public function isUnlimited();

	public function haveAQAccess();

	public function getExpiry();

	public function getPolicy();

	public function getAuthorizationPolicy();

	public function getAccountingPolicy($sessionTime, $sessionData);

	public function updateQuotaBalance($countableTime, $countableData);

	public function setAQInvocked();
}

//end of file ServicePlanInterface.php