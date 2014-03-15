<?php
namespace WMS\LdapBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RegisterListenersPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    protected $connectionsParameter;

    /**
     * @var string
     */
    protected $dispatcherServiceTemplate;

    /**
     * @var string
     */
    protected $listenerTag;

    /**
     * @var string
     */
    protected $subscriberTag;

    /**
     * @var Definition[]
     */
    protected $eventDispatchers = array();

    /**
     * Constructor.
     *
     * @param string $connectionsParameter      The name of the parameter holding the connections names array
     * @param string $dispatcherServiceTemplate Service name of the event dispatcher in processed container with a marker for the connection name
     * @param string $listenerTag               Tag name used for listener
     * @param string $subscriberTag             Tag name used for subscribers
     */
    public function __construct(
        $connectionsParameter = 'wms_ldap.connection_names',
        $dispatcherServiceTemplate = 'wms_ldap.%_connection.event_dispatcher',
        $listenerTag = 'wms_ldap.event_listener',
        $subscriberTag = 'wms_ldap.event_subscriber'
    ) {
        $this->connectionsParameter = $connectionsParameter;
        $this->dispatcherServiceTemplate = $dispatcherServiceTemplate;
        $this->listenerTag = $listenerTag;
        $this->subscriberTag = $subscriberTag;
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter($this->connectionsParameter)) {
            return;
        }

        $this->eventDispatchers = array();

        /** @var array $allConnections */
        $allConnections = $container->getParameter($this->connectionsParameter);

        foreach ($container->findTaggedServiceIds($this->listenerTag) as $id => $events) {
            $def = $container->getDefinition($id);
            if (!$def->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as event listeners are lazy-loaded.', $id));
            }

            if ($def->isAbstract()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must not be abstract as event listeners are lazy-loaded.', $id));
            }

            foreach ($events as $event) {
                $priority = isset($event['priority']) ? $event['priority'] : 0;

                if (!isset($event['event'])) {
                    throw new \InvalidArgumentException(sprintf('Service "%s" must define the "event" attribute on "%s" tags.', $id, $this->listenerTag));
                }

                $connections = isset($event['connection']) ? array($event['connection']) : $allConnections;

                if (!isset($event['method'])) {
                    $event['method'] = 'on' . preg_replace_callback(
                            array(
                                '/(?<=\b)[a-z]/i',
                                '/[^a-z0-9]/i',
                            ),
                            function ($matches) {
                                return strtoupper($matches[0]);
                            },
                            $event['event']
                        );
                    $event['method'] = preg_replace('/[^a-z0-9]/i', '', $event['method']);
                }

                foreach ($connections as $connection) {
                    if (!in_array($connection, $allConnections)) {
                        throw new \RuntimeException(sprintf(
                            'The LDAP connection "%s" referenced in service "%s" does not exist. Available connections names: %s',
                            $connection,
                            $id,
                            implode(', ', $allConnections)
                        ));
                    }

                    $definition = $this->getEventDispatcher($container, $connection);
                    $definition->addMethodCall('addListenerService', array($event['event'], array($id, $event['method']), $priority));
                }
            }
        }

        foreach ($container->findTaggedServiceIds($this->subscriberTag) as $id => $attributes) {
            $def = $container->getDefinition($id);
            if (!$def->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as event subscribers are lazy-loaded.', $id));
            }

            // We must assume that the class value has been correctly filled, even if the service is created by a factory
            $class = $def->getClass();

            $refClass = new \ReflectionClass($class);
            $interface = 'Symfony\Component\EventDispatcher\EventSubscriberInterface';
            if (!$refClass->implementsInterface($interface)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
            }

            foreach ($attributes as $tag) {
                $connections = isset($tag['connection']) ? array($tag['connection']) : $allConnections;

                foreach ($connections as $connection) {
                    if (!in_array($connection, $allConnections)) {
                        throw new \RuntimeException(sprintf(
                            'The LDAP connection "%s" referenced in service "%s" does not exist. Available connections names: %s',
                            $connection,
                            $id,
                            implode(', ', $allConnections)
                        ));
                    }

                    $definition = $this->getEventDispatcher($container, $connection);
                    $definition->addMethodCall('addSubscriberService', array($id, $class));
                }
            }
        }
    }

    protected function getEventDispatcher(ContainerBuilder $container, $name)
    {
        if (!isset($this->eventDispatchers[$name])) {
            $this->eventDispatchers[$name] = $container->findDefinition(sprintf($this->dispatcherServiceTemplate, $name));
        }

        return $this->eventDispatchers[$name];
    }
}