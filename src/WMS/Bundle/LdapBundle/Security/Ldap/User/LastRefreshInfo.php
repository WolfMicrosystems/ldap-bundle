<?php
namespace WMS\Bundle\LdapBundle\Security\Ldap\User;

use DateTime;

final class LastRefreshInfo
{
    private $skippedRefreshRequests = 0;
    private $lastRefreshDateTime = null;

    public function __construct()
    {
        $this->lastRefreshDateTime = new DateTime();
    }

    /**
     * @param DateTime $lastRefreshDateTime
     */
    public function setLastRefreshDateTime($lastRefreshDateTime)
    {
        $this->lastRefreshDateTime = $lastRefreshDateTime;
    }

    /**
     * @return DateTime
     */
    public function getLastRefreshDateTime()
    {
        return $this->lastRefreshDateTime;
    }

    /**
     * @param int $skippedRefreshRequests
     */
    public function setSkippedRefreshRequests($skippedRefreshRequests)
    {
        $this->skippedRefreshRequests = $skippedRefreshRequests;
    }

    /**
     * @return int
     */
    public function getSkippedRefreshRequests()
    {
        return $this->skippedRefreshRequests;
    }
}