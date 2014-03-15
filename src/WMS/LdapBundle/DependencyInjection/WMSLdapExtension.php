<?php
namespace WMS\LdapBundle\DependencyInjection;

use WMS\LdapBundle\Ldap\Configuration as LdapConfig;
use WMS\LdapBundle\Ldap\Connection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Zend\Ldap\Dn;
use Zend\Ldap\Ldap;

class WMSLdapExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $this->loadLdapConfiguration($config, $container);
        $this->loadSecurityConfiguration($config, $container);
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return 'wms_ldap';
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    private function loadLdapConfiguration(array $config, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('ldap.xml');

        if (empty($config['default_connection'])) {
            $keys = array_keys($config['connections']);
            $config['default_connection'] = reset($keys);
        }

        $defaultConnection = $config['default_connection'];

        $container->setAlias('ldap_connection', sprintf('wms_ldap.%s_connection', $defaultConnection));

        $connections = array();
        foreach (array_keys($config['connections']) as $name) {
            $connections[$name] = sprintf('wms_ldap.%s_connection', $name);
        }
        $container->setParameter('wms_ldap.connections', $connections);
        $container->setParameter('wms_ldap.connection_names', array_keys($connections));
        $container->setParameter('wms_ldap.default_connection', $defaultConnection);

        foreach ($config['connections'] as $name => $connection) {
            $this->loadLdapConnection($name, $connection, $container);
        }
    }

    private function loadLdapConnection($name, $connection, ContainerBuilder $container)
    {
        $configurationServiceName = sprintf('wms_ldap.%s_connection.configuration', $name);

        $configurationDefinition = $container
            ->setDefinition(
                $configurationServiceName,
                new DefinitionDecorator('wms_ldap.connection.configuration')
            );

        $configurationDefinition->addArgument($connection);

        if ($connection['logging']) {
            $configurationDefinition->addTag(
                'monolog.logger',
                array(
                    'channel' => 'ldap.' . $name
                )
            );
            $configurationDefinition->addArgument(
                new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE)
            );
        }

        $container
            ->setDefinition(
                sprintf('wms_ldap.%s_connection', $name),
                new DefinitionDecorator('wms_ldap.connection')
            )
            ->setArguments(array(new Reference($configurationServiceName)));

        $container
            ->setDefinition(
                sprintf('wms_ldap.%s_connection.event_dispatcher', $name),
                new DefinitionDecorator('wms_ldap.connection.event_dispatcher')
            );
    }

    private function loadSecurityConfiguration(array $config, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('security.xml');
    }
}