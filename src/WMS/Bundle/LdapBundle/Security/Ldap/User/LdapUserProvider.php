<?php
namespace WMS\Bundle\LdapBundle\Security\Ldap\User;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use WMS\Library\Ldap\Repository\AccountRepository;
use WMS\Library\Ldap\Repository\GroupRepository;
use WMS\Bundle\LdapBundle\Event\ResolvingUserRolesEvent;
use WMS\Bundle\LdapBundle\Registry;
use WMS\Bundle\LdapBundle\Enum;

class LdapUserProvider implements UserProviderInterface
{
    protected $registry;
    protected $class;
    protected $connections;
    protected $usernameForm;
    protected $refreshCredentialsEveryXRequests;
    protected $refreshCredentialsEveryXSeconds;

    public function __construct(Registry $registry, array $connections, $class, $usernameForm, $refreshCredentialsEveryXRequests = 1, $refreshCredentialsEveryXSeconds = 0)
    {
        $this->registry = $registry;
        $this->connections = $connections;
        $this->class = $class;
        $this->usernameForm = $usernameForm;
        $this->refreshCredentialsEveryXRequests = $refreshCredentialsEveryXRequests;
        $this->refreshCredentialsEveryXSeconds = $refreshCredentialsEveryXSeconds;
    }

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username The username
     *
     * @return UserInterface
     *
     * @see UsernameNotFoundException
     *
     * @throws UsernameNotFoundException if the user is not found
     *
     */
    public function loadUserByUsername($username)
    {
        foreach ($this->connections as $connectionName) {
            $connection = $this->registry->getConnection($connectionName);

            $accountRepo = new AccountRepository($connection);
            $account = $accountRepo->findByAccountName($username);

            if ($account === null) {
                continue;
            }

            $groupRepo = new GroupRepository($connection);
            $groups = $groupRepo->findGroupsForAccount($account);

            $user = new $this->class();
            /** @var $user LdapUserInterface */
            $user->setDn($account->getDn());
            $user->setUniqueId($account->getUniqueId());
            $user->setUsername($connection->getCanonicalAccountName($account->getUsername(), $this->usernameForm));
            $user->setFirstName($account->getFirstName());
            $user->setLastName($account->getLastName());
            $user->setDisplayName($account->getDisplayName());
            $user->setEmail($account->getEmail());

            $eventDispatcher = $this->registry->getEventDispatcher($connectionName);
            $eventDispatcher->dispatch(Enum\LdapEvents::RESOLVING_USER_ROLES, new ResolvingUserRolesEvent($connection, $user, $account, $groups->toArray()));

            return $user;
        }

        throw new UsernameNotFoundException(sprintf('Could not find username "%s" on ldap connections %s.', $username, implode(', ', $this->connections)));
    }

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     *
     * @param UserInterface $user
     *
     * @return UserInterface
     *
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof $this->class) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        if ($user instanceof LdapUserInterface) {
            $lastRefreshInfo = $user->__getLastRefreshInfo();

            if ($this->refreshCredentialsEveryXRequests > 0) {
                $lastRefreshInfo->setSkippedRefreshRequests($lastRefreshInfo->getSkippedRefreshRequests() + 1);

                if ($this->refreshCredentialsEveryXRequests <= $lastRefreshInfo->getSkippedRefreshRequests()) {
                    return $this->loadUserByUsername($user->getUsername());
                }
            }

            if ($this->refreshCredentialsEveryXSeconds > 0) {
                $needsRefresh = ($lastRefreshInfo->getLastRefreshDateTime()->getTimestamp() + $this->refreshCredentialsEveryXSeconds) <= time();

                if ($needsRefresh) {
                    return $this->loadUserByUsername($user->getUsername());
                }
            }

            return $user;
        }

        /** @var $user UserInterface */ // phpStorm is lost
        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * Whether this provider supports the given user class
     *
     * @param string $class
     *
     * @return Boolean
     */
    public function supportsClass($class)
    {
        return $class === $this->class || is_subclass_of($class, $this->class);
    }
}