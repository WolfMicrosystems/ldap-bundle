<?php
namespace WMS\LdapBundle\Security\Ldap\User;

use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\UserInterface;
use Zend\Ldap\Dn;

interface LdapUserInterface extends UserInterface
{
    /**
     * Sets the roles granted to the user.
     *
     * @param Role[] $roles The user roles
     */
    public function setRoles(array $roles);

    /**
     * Sets the username used to authenticate the user.
     *
     * @param string $username The username
     */
    public function setUsername($username);

    public function getFirstName();
    public function setFirstName($firstName);

    public function getLastName();
    public function setLastName($lastName);

    public function getDisplayName();
    public function setDisplayName($displayName);

    public function getEmail();
    public function setEmail($email);

    public function getPictureBlob();
    public function setPictureBlob($pictureBlob);

    public function getUniqueId();
    public function setUniqueId($uniqueId);

    public function getDn();
    public function setDn(Dn $dn);
}