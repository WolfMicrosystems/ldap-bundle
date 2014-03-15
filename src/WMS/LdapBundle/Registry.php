<?php

namespace WMS\LdapBundle;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use WMS\Ldap\Connection;

class Registry implements ContainerAwareInterface
{
    /** @var array */
    protected $connections = array();
    /** @var ContainerInterface */
    protected $container;
    /** @var string */
    protected $defaultConnection;

    public function __construct(ContainerInterface $container, array $connections, $defaultConnection)
    {
        $this->setContainer($container);
        $this->connections = $connections;
        $this->defaultConnection = $defaultConnection;
    }

    /**
     * Gets the named connection.
     *
     * @param string|null $name The connection name (null for the default one).
     *
     * @throws \InvalidArgumentException
     * @return Connection
     */
    public function getConnection($name = null)
    {
        if (null === $name) {
            $name = $this->defaultConnection;
        }

        if (!isset($this->connections[$name])) {
            throw new \InvalidArgumentException(sprintf('LDAP Connection named "%s" does not exist.', $name));
        }

        return $this->getService($this->connections[$name]);
    }

    /**
     * Gets all connection names.
     *
     * @return array An array of connection names.
     */
    public function getConnectionNames()
    {
        return array_keys($this->connections);
    }

    /**
     * Gets an array of all registered connections.
     *
     * @return Connection[] An array of Connection instances.
     */
    public function getConnections()
    {
        $connections = array();
        foreach ($this->connections as $name => $id) {
            $connections[$name] = $this->getService($id);
        }

        return $connections;
    }

    /**
     * Gets the named connection's event dispatcher.
     *
     * @param string|null $name The connection name (null for the default one).
     *
     * @throws \InvalidArgumentException
     * @return EventDispatcher
     */
    public function getEventDispatcher($name = null)
    {
        if (null === $name) {
            $name = $this->defaultConnection;
        }

        if (!isset($this->connections[$name])) {
            throw new \InvalidArgumentException(sprintf('LDAP Connection named "%s" does not exist.', $name));
        }

        return $this->getService($this->connections[$name] . '.event_dispatcher');
    }

    /**
     * Gets an array of the event dispatchers of all registered connections.
     *
     * @return EventDispatcher[] An array of EventDispatcher instances.
     */
    public function getEventDispatchers()
    {
        $connections = array();
        foreach ($this->connections as $name => $id) {
            $connections[$name] = $this->getService($id . '.event_dispatcher');
        }

        return $connections;
    }


    /**
     * Gets the default connection name.
     *
     * @return string The default connection name.
     */
    public function getDefaultConnectionName()
    {
        return $this->defaultConnection;
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    protected function getService($name)
    {
        return $this->container->get($name);
    }
}