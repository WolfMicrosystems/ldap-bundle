<?php
namespace WMS\Bundle\LdapBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use WMS\Library\Ldap\Connection;
use WMS\Library\Ldap\Entity\AccountNode;
use WMS\Library\Ldap\Entity\GroupNode;
use WMS\Bundle\LdapBundle\Security\Ldap\Role\RoleResolvingHelper;
use WMS\Bundle\LdapBundle\Security\Ldap\User\LdapUserInterface;

class ResolvingUserRolesEvent extends LdapEvent
{
    private $user;
    private $accountNode;
    private $groupNodes;
    /** @var RoleResolvingHelper */
    private $roleResolver;

    /**
     * @return \WMS\Library\Ldap\Entity\AccountNode
     */
    public function getAccountNode()
    {
        return $this->accountNode;
    }

    /**
     * @return GroupNode[]
     */
    public function getGroupNodes()
    {
        return $this->groupNodes;
    }

    /**
     * @return \WMS\Bundle\LdapBundle\Security\Ldap\User\LdapUserInterface
     */
    public function getUser()
    {
        return $this->user;
    }


    public function __construct(Connection $connection, LdapUserInterface $user, AccountNode $accountNode, array $groupNodes)
    {
        parent::__construct($connection);
        $this->user = $user;
        $this->accountNode = $accountNode;
        $this->groupNodes = $groupNodes;
        $this->roleResolver = new RoleResolvingHelper($user);
    }

    /**
     * @return \WMS\Bundle\LdapBundle\Security\Ldap\Role\RoleResolvingHelper
     */
    public function getRoleResolver()
    {
        return $this->roleResolver;
    }
} 