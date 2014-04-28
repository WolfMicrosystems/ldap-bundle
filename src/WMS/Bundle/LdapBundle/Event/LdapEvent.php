<?php
namespace WMS\Bundle\LdapBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use WMS\Library\Ldap\Connection;

class LdapEvent extends Event
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return \WMS\Library\Ldap\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
} 