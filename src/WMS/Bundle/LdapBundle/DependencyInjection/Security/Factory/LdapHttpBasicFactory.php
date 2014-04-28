<?php
namespace WMS\Bundle\LdapBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\HttpBasicFactory;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class LdapHttpBasicFactory extends HttpBasicFactory
{
    public function getKey()
    {
        return 'ldap-http-basic';
    }

    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        list($daoProvider, $listenerId, $entryPointId) = parent::create($container, $id, $config, $userProvider, $defaultEntryPoint);

        $container->removeDefinition($daoProvider);

        $ldapProvider = 'wms_ldap.security.authentication.provider.' . $id;
        $container
            ->setDefinition($ldapProvider, new DefinitionDecorator('wms_ldap.security.authentication.provider'))
            ->replaceArgument(1, new Reference($userProvider))
            ->replaceArgument(3, $id);

        return array($ldapProvider, $listenerId, $entryPointId);
    }
} 