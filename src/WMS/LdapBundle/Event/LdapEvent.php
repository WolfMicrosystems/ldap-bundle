<?php
namespace WMS\LdapBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use WMS\Ldap\Connection;

class LdapEvent extends Event
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return \WMS\Ldap\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
} 