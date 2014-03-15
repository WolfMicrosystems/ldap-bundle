<?php
namespace WMS\LdapBundle\Security\Ldap\User;

use Symfony\Component\Security\Core\Role\Role;
use Zend\Ldap\Dn;

class LdapUser implements LdapUserInterface
{
    /** @var Role[] */
    protected $roles = array();
    protected $username;
    protected $firstName;
    protected $lastName;
    protected $displayName;
    protected $email;
    protected $pictureBlob;
    protected $uniqueId;
    /** @var Dn */
    protected $dn;

    /**
     * Sets the roles granted to the user.
     *
     * @param Role[] $roles The user roles
     */
    public function setRoles(array $roles)
    {
        $this->roles = $roles;
    }

    /**
     * Sets the username used to authenticate the user.
     *
     * @param string $username The username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    public function getDisplayName()
    {
        return $this->displayName;
    }

    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getPictureBlob()
    {
        return $this->pictureBlob;
    }

    public function setPictureBlob($pictureBlob)
    {
        $this->pictureBlob = $pictureBlob;
    }

    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    public function setUniqueId($uniqueId)
    {
        $this->uniqueId = $uniqueId;
    }

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return Role[] The user roles
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
    }

    /**
     * @return \Zend\Ldap\Dn
     */
    public function getDn()
    {
        return $this->dn;
    }

    /**
     * @param \Zend\Ldap\Dn $dn
     */
    public function setDn(Dn $dn)
    {
        $this->dn = $dn;
    }
}