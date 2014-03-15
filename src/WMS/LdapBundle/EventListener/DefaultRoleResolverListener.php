<?php
namespace WMS\LdapBundle\EventListener;

use WMS\LdapBundle\Event\ResolvingUserRolesEvent;

class DefaultRoleResolverListener
{
    protected $automaticMapping = false;
    protected $mappings = array();

    public function __construct($automaticMapping, array $mappings)
    {
        $this->automaticMapping = $automaticMapping;
        $this->mappings = $mappings;
    }

    public function resolveRoles(ResolvingUserRolesEvent $event)
    {
        foreach ($this->mappings as $mapping) {
            if (isset($mapping['group']) && $mapping['group'] !== null) {
                foreach ($event->getGroupNodes() as $group) {
                    if (preg_match('#' . addcslashes($mapping['group'], '\\#') . '#i', $group->getName())) {
                        $event->getRoleResolver()->addRoles($mapping['roles'], $group);
                    }
                }
            } else {
                $event->getRoleResolver()->addRoles($mapping['roles']);
            }
        }

        if ($this->automaticMapping) {
            foreach ($event->getGroupNodes() as $group) {
                if ($event->getRoleResolver()->hasGroupBeenResolved($group) === false) {
                    $event->getRoleResolver()->addGeneratedRole($group);
                }
            }
        }
    }
} 