<?php
namespace WMS\Bundle\LdapBundle\Security\Ldap\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use WMS\Bundle\LdapBundle\Registry;
use WMS\Bundle\LdapBundle\Enum as Enum;
use WMS\Bundle\LdapBundle\Security\Ldap\User\LdapUserInterface;
use Zend\Ldap\Dn;
use Zend\Ldap\Exception\LdapException;

class LdapAuthenticationProvider extends DaoAuthenticationProvider
{
    /** @var Registry */
    protected $registry;

    public function __construct(Registry $registry, UserProviderInterface $userProvider, UserCheckerInterface $userChecker, $providerKey, EncoderFactoryInterface $encoderFactory, $hideUserNotFoundExceptions = true)
    {
        parent::__construct($userProvider, $userChecker, $providerKey, $encoderFactory, $hideUserNotFoundExceptions);

        $this->registry = $registry;
    }

    /**
     * Does additional checks on the user and token (like validating the
     * credentials).
     *
     * @param UserInterface         $user  The retrieved UserInterface instance
     * @param UsernamePasswordToken $token The UsernamePasswordToken token to be authenticated
     *
     * @throws AuthenticationException if the credentials could not be validated
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        if (!$user instanceof LdapUserInterface) {
            parent::checkAuthentication($user, $token);
            return;
        }

        $currentUser = $token->getUser();
        if ($currentUser instanceof LdapUserInterface) {
            if ($currentUser->getDn() !== $user->getDn()) {
                throw new BadCredentialsException('A mapping issue occurred when loading the session.');
            }
        } elseif ($currentUser instanceof UserInterface) {
            parent::checkAuthentication($user, $token);
            return;
        } else {
            if ("" === ($presentedPassword = $token->getCredentials())) {
                throw new BadCredentialsException('The presented password cannot be empty.');
            }

            $connection = $this->findLdapConnectionForUser($user);

            try {
                $connection->bind($user->getUsername(), $presentedPassword);
            } catch (LdapException $ldapException) {
                $connection->disconnect();
                throw new BadCredentialsException('The presented password is invalid.', 0, $ldapException);
            }
            $connection->disconnect();
        }
    }

    protected function findLdapConnectionForUser(LdapUserInterface $user)
    {
        foreach ($this->registry->getConnections() as $connection) {
            $accountSearchDn = $connection->getConfiguration()->getAccountSearchDn() ? : $connection->getConfiguration()->getBaseDn();

            if (Dn::isChildOf($user->getDn(), $accountSearchDn)) {
                return $connection;
            }
        }

        throw new BadCredentialsException('Unable to map user to LDAP connection.');
    }
}