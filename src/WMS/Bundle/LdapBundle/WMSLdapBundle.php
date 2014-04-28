<?php
namespace WMS\Bundle\LdapBundle;

use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use WMS\Bundle\LdapBundle\DependencyInjection\CompilerPass\RegisterListenersPass;
use WMS\Bundle\LdapBundle\DependencyInjection\WMSLdapExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * LDAP Bundle: Simple LDAP bindings for Symfony2's Security Component
 *
 * @author Andrew Moore <me@andrewmoore.ca>
 */
class WMSLdapBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterListenersPass());

        /** @var SecurityExtension $securityExtension */
        $securityExtension = $container->getExtension('security');
        $securityExtension->addSecurityListenerFactory(new DependencyInjection\Security\Factory\LdapFormLoginFactory());
        $securityExtension->addSecurityListenerFactory(new DependencyInjection\Security\Factory\LdapHttpBasicFactory());
        $securityExtension->addUserProviderFactory(new DependencyInjection\Security\UserProvider\LdapUserProviderFactory());
    }

    public function getContainerExtension()
    {
        if ($this->extension === null) {
            $this->extension = new WMSLdapExtension();
        }

        return $this->extension;
    }


} 