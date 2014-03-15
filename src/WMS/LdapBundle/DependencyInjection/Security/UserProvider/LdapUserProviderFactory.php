<?php
namespace WMS\LdapBundle\DependencyInjection\Security\UserProvider;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\UserProvider\UserProviderFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Parameter;
use WMS\Ldap\Enum as LdapEnum;

class LdapUserProviderFactory implements UserProviderFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config)
    {
        $definition = $container->setDefinition($id, new DefinitionDecorator('wms_ldap.security.user.provider'));

        $connections = $config['connections'] === array('__wms_ldap.special.all_connections') ? new Parameter('wms_ldap.connection_names') : $config['connections'];
        $canonicalForm = $this->getCanonicalFormFromString($config['username_form']);

        $definition->replaceArgument(1, $connections);
        $definition->replaceArgument(2, $config['class']);
        $definition->replaceArgument(3, $canonicalForm);
        $definition->replaceArgument(4, (bool)$config['refresh_credentials']);

        $roleResolverDef = $container->setDefinition($id . '.role_resolver', new DefinitionDecorator('wms_ldap.security.user.provider.role_resolver'));
        $roleResolverDef->setPublic(true);
        $roleResolverDef->replaceArgument(0, (bool)$config['groups']['automatic_mapping']);
        $roleResolverDef->replaceArgument(1, $config['groups']['mappings']);
        $roleResolverDef->addTag(
            'wms_ldap.event_listener',
            array(
                'event'  => 'wms_ldap.event.resolving_user_roles',
                'method' => 'resolveRoles'
            )
        );
    }

    public function getKey()
    {
        return 'ldap';
    }

    public function addConfiguration(NodeDefinition $builder)
    {
        $builder
            ->fixXmlConfig('connection')
            ->beforeNormalization()
                ->ifTrue(function ($v) { return is_array($v) && (array_key_exists('connection', $v) && !array_key_exists('connections', $v)); })
                ->then(function ($v)  {
                        $v['connections'] = array($v['connection']);
                        unset($v['connection']);

                        return $v;
                    })
            ->end()
            ->children()
                ->arrayNode('connections')
                    ->performNoDeepMerging()
                    ->defaultValue(array('__wms_ldap.special.all_connections'))
                    ->prototype('scalar')
                        ->cannotBeEmpty()
                    ->end()
                ->end()
                ->scalarNode('class')
                    ->defaultValue('WMS\LdapBundle\Security\Ldap\User\LdapUser')
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(function ($v) { return !is_subclass_of($v, 'WMS\LdapBundle\Security\Ldap\User\LdapUserInterface'); })
                        ->thenInvalid('"%s" does not implement WMS\LdapBundle\Security\Ldap\User\LdapUserInterface')
                    ->end()
                ->end()
                ->enumNode('username_form')
                    ->values(array('dn', 'username', 'backslash', 'principal'))
                    ->defaultValue('username')
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('refresh_credentials')
                    ->defaultTrue()
                ->end()
                ->arrayNode('groups')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('automatic_mapping')
                            ->defaultFalse()
                        ->end()
                        ->arrayNode('mappings')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('group')
                                        ->beforeNormalization()
                                            ->ifTrue(function ($v) { return $v === '*'; })
                                            ->then(function ($v) { return null; })
                                        ->end()
                                        ->defaultValue(null)
                                    ->end()
                                    ->arrayNode('roles')
                                        ->isRequired()
                                        ->beforeNormalization()->ifString()->then(function ($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function getCanonicalFormFromString($string)
    {
        switch ($string) {
            case 'dn':
                return LdapEnum\CanonicalAccountNameForm::DN;
            case 'username':
                return LdapEnum\CanonicalAccountNameForm::USERNAME;
            case 'principal':
                return LdapEnum\CanonicalAccountNameForm::PRINCIPAL;
            case 'backslash':
                return LdapEnum\CanonicalAccountNameForm::BACKSLASH;
        }

        return LdapEnum\CanonicalAccountNameForm::USERNAME;
    }
}