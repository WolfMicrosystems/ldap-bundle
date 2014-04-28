<?php
namespace WMS\Bundle\LdapBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class LdapFormLoginFactory extends FormLoginFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getKey()
    {
        return 'ldap-form-login';
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $ldapProvider = 'wms_ldap.security.authentication.provider.' . $id;
        $container
            ->setDefinition($ldapProvider, new DefinitionDecorator('wms_ldap.security.authentication.provider'))
            ->replaceArgument(1, new Reference($userProviderId))
            ->replaceArgument(3, $id);

        return $ldapProvider;
    }
} 