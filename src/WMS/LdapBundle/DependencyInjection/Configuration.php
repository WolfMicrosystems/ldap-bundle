<?php
namespace WMS\LdapBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author Andrew Moore <me@andrewmoore.ca>
 */
class Configuration implements ConfigurationInterface {
    private $debug;

    /**
     * Constructor
     *
     * @param Boolean $debug Whether to use the debug mode
     */
    public function  __construct($debug)
    {
        $this->debug = (Boolean) $debug;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('wms_ldap');

        $rootNode
            ->beforeNormalization()
                ->ifTrue(function ($v) { return is_array($v) && !array_key_exists('connections', $v) && !array_key_exists('connection', $v); })
                ->then(function ($v) {
                    // Key that should not be rewritten to the connection config
                    $excludedKeys = array('default_connection' => true);
                    $connection = array();
                    foreach ($v as $key => $value) {
                        if (isset($excludedKeys[$key])) {
                            continue;
                        }
                        $connection[$key] = $v[$key];
                        unset($v[$key]);
                    }
                    $v['default_connection'] = isset($v['default_connection']) ? (string) $v['default_connection'] : 'default';
                    $v['connections'] = array($v['default_connection'] => $connection);

                    return $v;
                })
            ->end()
            ->children()
                ->scalarNode('default_connection')->end()
            ->end()
            ->fixXmlConfig('connection')
            ->append($this->getConnectionsNode())
            ->end();

        return $treeBuilder;
    }

    private function getConnectionsNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('connections');

        /** @var $connectionNode ArrayNodeDefinition */
        $connectionNode = $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array')
        ;

        $vendorDefaults = $this->getSupportedVendorsDefaults();

        $connectionNode
            ->beforeNormalization()
                ->ifTrue(function ($v) use ($vendorDefaults) { return is_array($v) && array_key_exists('vendor', $v) && array_key_exists($v['vendor'], $vendorDefaults); })
                ->then(function ($v) use ($vendorDefaults) {
                        $base = array();

                        if(array_key_exists('parameters', $v)) {
                            $base = $v['parameters'];
                        }

                        $mergeFunction = null;
                        $mergeFunction = function (&$array1, &$array2) use (&$mergeFunction) {
                            /** @var \Closure $mergeFunction */
                            $merged = $array1;

                            foreach ($array2 as $key => &$value) {
                                if (is_array($value) && isset ($merged [$key]) && is_array($merged [$key])) {
                                    $merged [$key] = $mergeFunction($merged [$key], $value);
                                } else {
                                    $merged [$key] = $value;
                                }
                            }

                            return $merged;
                        };

                        $specificVendorDefaults = $vendorDefaults[$v['vendor']];

                        $v['parameters'] = $mergeFunction($specificVendorDefaults, $base);

                        return $v;
                    })
            ->end()
            ->children()
                ->scalarNode('host')->defaultValue('localhost')->isRequired()->cannotBeEmpty()
                    ->info('Hostname or IP of the LDAP server to connect to')
                ->end()
                ->integerNode('port')->defaultValue(389)->min(0)->max(65535)->isRequired()->cannotBeEmpty()
                    ->info('Port number of the LDAP server to connect to')
                ->end()
                ->booleanNode('use_start_tls')->defaultFalse()
                    ->info('Whether or not the LDAP client should use TLS (aka SSLv2) encrypted transport.')
                ->end()
                ->booleanNode('use_ssl')->defaultFalse()
                    ->info('Whether or not the LDAP client should use SSL encrypted transport. The use_ssl and use_start_tls options are mutually exclusive.')
                ->end()
                ->scalarNode('user')->defaultNull()
                    ->info('The default credentials username. Set if you want to query the LDAP server without any user authenticated first.')
                ->end()
                ->scalarNode('password')->defaultNull()
                    ->info('The default credentials password (used only with username above).')
                ->end()
                ->integerNode('timeout')->defaultNull()->min(0)
                    ->info('Network Timeout')
                ->end()
                ->scalarNode('base_dn')->defaultNull()->isRequired()->cannotBeEmpty()
                    ->info('The default base DN used for searching (e.g., for accounts).')
                ->end()
                ->scalarNode('domain_name')->defaultNull()->isRequired()->cannotBeEmpty()
                    ->info('The FQDN domain for which the target LDAP server is an authority (e.g., example.com).')
                ->end()
                ->scalarNode('domain_name_short')->defaultNull()
                    ->info('The \'short\' domain for which the target LDAP server is an authority. This is usually used to specify the NetBIOS domain name for Windows networks but may also be used by non-AD servers.')
                ->end()
                ->booleanNode('try_username_split')->defaultTrue()
                    ->info('f set to FALSE, this option indicates that the given username should not be split at the first @ or \ character to separate the username from the domain during the binding-procedure.')
                ->end()
                ->booleanNode('follow_referrals')->defaultFalse()
                    ->info('If set to TRUE, this option indicates to the LDAP client that referrals should be followed.')
                ->end()
                ->booleanNode('logging')->defaultValue($this->debug)->end()
                ->enumNode('vendor')->values(array_merge(array_keys($vendorDefaults), array(null)))->defaultNull()
                    ->info('Set to quickly and easily configure vendor parameters according to a specific LDAP vendor.')
                ->end()
                ->append($this->getVendorParametersNode())
            ->end();

        return $node;
    }

    private function getVendorParametersNode()
    {
        $builder = new TreeBuilder();
        $paramsNode = $builder->root('parameters');

        $paramsNode
            ->info('Vendor-specific parameters (automatically configured if you set the vendor parameter above)')
            ->children()
                ->booleanNode('bind_requires_dn')->defaultFalse()
                    ->info('If TRUE, this instructs LdapBundle to retrieve the DN for the account used to bind if the username is not already in DN form.')
                ->end()
                ->enumNode('account_canonical_form')->values(array('dn', 'username', 'backslash', 'principal'))->defaultValue('username')->isRequired()->cannotBeEmpty()
                    ->info('Indicates the form to which account names should be canonicalized.')
                ->end()
                ->booleanNode('allow_empty_password')->defaultFalse()
                    ->info('Some LDAP servers can be configured to accept an empty string password as an anonymous bind. This doesn\'t affect the security provider.')
                ->end()
                ->append($this->getSchemaNode())
            ->end();

        return $paramsNode;
    }

    private function getSchemaNode()
    {
        $builder = new TreeBuilder();
        $schemaNode = $builder->root('schema');

        $schemaNode
            ->children()
                ->append($this->getAccountSchemaNode())
                ->append($this->getGroupSchemaNode())
                ->append($this->getMembershipSchemaNode())
            ->end();

        return $schemaNode;
    }

    private function getAccountSchemaNode()
    {
        $builder = new TreeBuilder();
        $userSchemaNode = $builder->root('account');

        $userSchemaNode
            ->children()
                ->scalarNode('additional_dn')->defaultNull()
                    ->info('Prepended to base_dn to limit the scope when searching for users.')
                ->end()
                ->scalarNode('object_class')->isRequired()->cannotBeEmpty()
                    ->info('The LDAP user object class type to query when loading users.')
                ->end()
                ->scalarNode('object_filter')->isRequired()->cannotBeEmpty()
                    ->info('The filter to use when searching user objects.')
                ->end()
                ->scalarNode('username_attribute')->isRequired()->cannotBeEmpty()
                    ->info('The attribute field to use when binding the username to the user.')
                ->end()
                ->scalarNode('unique_id_attribute')->defaultNull()
                    ->info('The attribute field to use for keeping track of a user across renames.')
                ->end()
                ->scalarNode('first_name_attribute')->defaultNull()
                    ->info('The attribute field to use when binding the first name to the user.')
                ->end()
                ->scalarNode('last_name_attribute')->defaultNull()
                    ->info('The attribute field to use when binding the last name to the user.')
                ->end()
                ->scalarNode('display_name_attribute')->defaultNull()
                    ->info('The attribute field to use when binding the display name to the user.')
                ->end()
                ->scalarNode('email_attribute')->defaultNull()
                    ->info('The attribute field to use when binding the email to the user.')
                ->end()
                ->scalarNode('picture_attribute')->defaultNull()
                    ->info('The attribute field to use when binding the account picture to the user.')
                ->end()
            ->end();

        return $userSchemaNode;
    }

    private function getGroupSchemaNode()
    {
        $builder = new TreeBuilder();
        $groupSchemaNode = $builder->root('group');

        $groupSchemaNode
            ->children()
                ->scalarNode('additional_dn')->defaultNull()
                    ->info('Prepended to base_dn to limit the scope when searching for groups.')
                ->end()
                ->scalarNode('object_class')->isRequired()->cannotBeEmpty()
                    ->info('The LDAP group object class type to query when loading groups.')
                ->end()
                ->scalarNode('object_filter')->isRequired()->cannotBeEmpty()
                    ->info('The filter to use when searching group objects.')
                ->end()
                ->scalarNode('name_attribute')->isRequired()->cannotBeEmpty()
                    ->info('The attribute field to use when binding the name to the group.')
                ->end()
                ->scalarNode('description_attribute')->defaultNull()
                    ->info('The attribute field to use when binding the description to the group.')
                ->end()
            ->end();

        return $groupSchemaNode;
    }

    private function getMembershipSchemaNode()
    {
        $builder = new TreeBuilder();
        $membershipSchemaNode = $builder->root('membership');

        $membershipSchemaNode
            ->children()
                ->scalarNode('group_members_attribute')->isRequired()->cannotBeEmpty()
                    ->info('The attribute field to use when reading a group members from a group object.')
                ->end()
                ->enumNode('group_members_attribute_mapping_type')->values(array('dn', 'username', 'unique_id'))->defaultValue('dn')->isRequired()->cannotBeEmpty()
                    ->info('Defines whether to use the user\'s DN, username or unique_id to map a group to its users.')
                ->end()
                ->scalarNode('account_membership_attribute')->isRequired()->cannotBeEmpty()
                    ->info('The attribute field to use when reading an account\'s groups.')
                ->end()
                ->enumNode('account_membership_attribute_mapping_type')->values(array('dn', 'name'))->defaultValue('dn')->isRequired()->cannotBeEmpty()
                    ->info('Defines whether to use the group\'s DN, or name to map a user to its groups.')
                ->end()
                ->booleanNode('use_attribute_from_group')->isRequired()
                    ->info('Indicates if the attribute defined in group_members_attribute will be used instead of a search during discovery.')
                ->end()
                ->booleanNode('use_attribute_from_account')->isRequired()
                    ->info('Indicates if the attribute defined in account_membership_attribute will be used instead of a search during discovery.')
                ->end()
            ->end();

        return $membershipSchemaNode;
    }

    private function getSupportedVendorsDefaults()
    {
        return array(
            'activeDirectory'    => array(
                'bind_requires_dn'       => false,
                'account_canonical_form' => 'backslash',
                'allow_empty_password'   => false,
                'schema'                 => array(
                    'account'    => array(
                        'additional_dn'          => null,
                        'object_class'           => 'user',
                        'object_filter'          => '(&(objectCategory=Person)(sAMAccountName=*))',
                        'username_attribute'     => 'sAMAccountName',
                        'unique_id_attribute'    => 'objectGUID',
                        'first_name_attribute'   => 'givenName',
                        'last_name_attribute'    => 'sn',
                        'display_name_attribute' => 'displayName',
                        'email_attribute'        => 'mail',
                        'picture_attribute'      => 'thumbnailPhoto'
                    ),
                    'group'      => array(
                        'additional_dn'         => null,
                        'object_class'          => 'group',
                        'object_filter'         => '(objectCategory=Group)',
                        'name_attribute'        => 'cn',
                        'description_attribute' => 'description'
                    ),
                    'membership' => array(
                        'group_members_attribute'                   => 'member',
                        'group_members_attribute_mapping_type'      => 'dn',
                        'account_membership_attribute'              => 'memberOf',
                        'account_membership_attribute_mapping_type' => 'dn',
                        'use_attribute_from_group'                  => false,
                        'use_attribute_from_account'                => true
                    )
                )
            ),
            'apacheDS'           => array(
                'bind_requires_dn'       => true,
                'account_canonical_form' => 'dn',
                'allow_empty_password'   => false,
                'schema'                 => array(
                    'account'    => array(
                        'additional_dn'          => null,
                        'object_class'           => 'inetorgperson',
                        'object_filter'          => '(objectclass=inetorgperson)',
                        'username_attribute'     => 'cn',
                        'unique_id_attribute'    => 'entryUUID',
                        'first_name_attribute'   => 'givenName',
                        'last_name_attribute'    => 'sn',
                        'display_name_attribute' => 'displayName',
                        'email_attribute'        => 'mail',
                        'picture_attribute'      => 'jpegPhoto'
                    ),
                    'group'      => array(
                        'additional_dn'         => null,
                        'object_class'          => 'groupOfUniqueNames',
                        'object_filter'         => '(objectclass=groupOfUniqueNames)',
                        'name_attribute'        => 'cn',
                        'description_attribute' => 'description'
                    ),
                    'membership' => array(
                        'group_members_attribute'                   => 'uniqueMember',
                        'group_members_attribute_mapping_type'      => 'dn',
                        'account_membership_attribute'              => 'memberOf',
                        'account_membership_attribute_mapping_type' => 'dn',
                        'use_attribute_from_group'                  => false,
                        'use_attribute_from_account'                => false
                    )
                )
            ),
            'appleOpenDirectory' => array(
                'bind_requires_dn'       => true,
                'account_canonical_form' => 'dn',
                'allow_empty_password'   => false,
                'schema'                 => array(
                    'account'    => array(
                        'additional_dn'          => null,
                        'object_class'           => 'posixAccount',
                        'object_filter'          => '(objectclass=posixAccount)',
                        'username_attribute'     => 'cn',
                        'unique_id_attribute'    => 'entryUUID',
                        'first_name_attribute'   => 'givenName',
                        'last_name_attribute'    => 'sn',
                        'display_name_attribute' => 'displayName',
                        'email_attribute'        => 'mail',
                        'picture_attribute'      => 'jpegPhoto'
                    ),
                    'group'      => array(
                        'additional_dn'         => null,
                        'object_class'          => 'posixGroup',
                        'object_filter'         => '(objectclass=posixGroup)',
                        'name_attribute'        => 'cn',
                        'description_attribute' => 'description'
                    ),
                    'membership' => array(
                        'group_members_attribute'                   => 'memberUid',
                        'group_members_attribute_mapping_type'      => 'dn',
                        'account_membership_attribute'              => 'memberOf',
                        'account_membership_attribute_mapping_type' => 'dn',
                        'use_attribute_from_group'                  => false,
                        'use_attribute_from_account'                => false
                    )
                )
            ),
            'eDirectory'         => array(
                'bind_requires_dn'       => false,
                'account_canonical_form' => 'principal',
                'allow_empty_password'   => false,
                'schema'                 => array(
                    'account'    => array(
                        'additional_dn'          => null,
                        'object_class'           => 'inetOrgPerson',
                        'object_filter'          => '(objectclass=inetOrgPerson)',
                        'username_attribute'     => 'cn',
                        'unique_id_attribute'    => 'GUID',
                        'first_name_attribute'   => 'givenName',
                        'last_name_attribute'    => 'sn',
                        'display_name_attribute' => 'displayName',
                        'email_attribute'        => 'mail',
                        'picture_attribute'      => 'jpegPhoto'
                    ),
                    'group'      => array(
                        'additional_dn'         => null,
                        'object_class'          => 'groupOfNames',
                        'object_filter'         => '(objectclass=groupOfNames)',
                        'name_attribute'        => 'cn',
                        'description_attribute' => 'description'
                    ),
                    'membership' => array(
                        'group_members_attribute'                   => 'member',
                        'group_members_attribute_mapping_type'      => 'dn',
                        'account_membership_attribute'              => 'groupMembership',
                        'account_membership_attribute_mapping_type' => 'dn',
                        'use_attribute_from_group'                  => false,
                        'use_attribute_from_account'                => false
                    )
                )
            ),
            'fedoraDS'           => array(
                'bind_requires_dn'       => true,
                'account_canonical_form' => 'dn',
                'allow_empty_password'   => false,
                'schema'                 => array(
                    'account'    => array(
                        'additional_dn'          => null,
                        'object_class'           => 'posixAccount',
                        'object_filter'          => '(objectclass=posixAccount)',
                        'username_attribute'     => 'uid',
                        'unique_id_attribute'    => 'entryUUID',
                        'first_name_attribute'   => 'givenName',
                        'last_name_attribute'    => 'sn',
                        'display_name_attribute' => 'displayName',
                        'email_attribute'        => 'mail',
                        'picture_attribute'      => 'jpegPhoto'
                    ),
                    'group'      => array(
                        'additional_dn'         => null,
                        'object_class'          => 'posixGroup',
                        'object_filter'         => '(objectclass=posixGroup)',
                        'name_attribute'        => 'cn',
                        'description_attribute' => 'description'
                    ),
                    'membership' => array(
                        'group_members_attribute'                   => 'memberUid',
                        'group_members_attribute_mapping_type'      => 'dn',
                        'account_membership_attribute'              => 'memberOf',
                        'account_membership_attribute_mapping_type' => 'dn',
                        'use_attribute_from_group'                  => false,
                        'use_attribute_from_account'                => false
                    )
                )
            ),
            'genericPosix'       => array(
                'bind_requires_dn'       => true,
                'account_canonical_form' => 'dn',
                'allow_empty_password'   => false,
                'schema'                 => array(
                    'account'    => array(
                        'additional_dn'          => null,
                        'object_class'           => 'posixAccount',
                        'object_filter'          => '(objectclass=posixAccount)',
                        'username_attribute'     => 'uid',
                        'unique_id_attribute'    => 'entryUUID',
                        'first_name_attribute'   => 'givenName',
                        'last_name_attribute'    => 'sn',
                        'display_name_attribute' => 'displayName',
                        'email_attribute'        => 'mail',
                        'picture_attribute'      => 'jpegPhoto'
                    ),
                    'group'      => array(
                        'additional_dn'         => null,
                        'object_class'          => 'posixGroup',
                        'object_filter'         => '(objectclass=posixGroup)',
                        'name_attribute'        => 'cn',
                        'description_attribute' => 'description'
                    ),
                    'membership' => array(
                        'group_members_attribute'                   => 'memberUid',
                        'group_members_attribute_mapping_type'      => 'dn',
                        'account_membership_attribute'              => 'memberOf',
                        'account_membership_attribute_mapping_type' => 'dn',
                        'use_attribute_from_group'                  => false,
                        'use_attribute_from_account'                => false
                    )
                )
            ),
            'openDS'             => array(
                'bind_requires_dn'       => true,
                'account_canonical_form' => 'dn',
                'allow_empty_password'   => false,
                'schema'                 => array(
                    'account'    => array(
                        'additional_dn'          => null,
                        'object_class'           => 'intorgperson',
                        'object_filter'          => '(objectclass=intorgperson)',
                        'username_attribute'     => 'uid',
                        'unique_id_attribute'    => 'entryUUID',
                        'first_name_attribute'   => 'givenName',
                        'last_name_attribute'    => 'sn',
                        'display_name_attribute' => 'cn',
                        'email_attribute'        => 'mail',
                        'picture_attribute'      => 'jpegPhoto'
                    ),
                    'group'      => array(
                        'additional_dn'         => null,
                        'object_class'          => 'groupOfUniqueNames',
                        'object_filter'         => '(objectclass=groupOfUniqueNames)',
                        'name_attribute'        => 'cn',
                        'description_attribute' => 'description'
                    ),
                    'membership' => array(
                        'group_members_attribute'                   => 'uniqueMember',
                        'group_members_attribute_mapping_type'      => 'dn',
                        'account_membership_attribute'              => 'memberOf',
                        'account_membership_attribute_mapping_type' => 'dn',
                        'use_attribute_from_group'                  => false,
                        'use_attribute_from_account'                => false
                    )
                )
            ),
            'openLDAP'           => array(
                'bind_requires_dn'       => true,
                'account_canonical_form' => 'dn',
                'allow_empty_password'   => false,
                'schema'                 => array(
                    'account'    => array(
                        'additional_dn'          => null,
                        'object_class'           => 'inetorgperson',
                        'object_filter'          => '(objectclass=inetorgperson)',
                        'username_attribute'     => 'cn',
                        'unique_id_attribute'    => 'entryUUID',
                        'first_name_attribute'   => 'givenName',
                        'last_name_attribute'    => 'sn',
                        'display_name_attribute' => 'displayName',
                        'email_attribute'        => 'mail',
                        'picture_attribute'      => 'jpegPhoto'
                    ),
                    'group'      => array(
                        'additional_dn'         => null,
                        'object_class'          => 'groupOfUniqueNames',
                        'object_filter'         => '(objectclass=groupOfUniqueNames)',
                        'name_attribute'        => 'cn',
                        'description_attribute' => 'description'
                    ),
                    'membership' => array(
                        'group_members_attribute'                   => 'uniqueMember',
                        'group_members_attribute_mapping_type'      => 'dn',
                        'account_membership_attribute'              => 'memberOf',
                        'account_membership_attribute_mapping_type' => 'dn',
                        'use_attribute_from_group'                  => false,
                        'use_attribute_from_account'                => false
                    )
                )
            ),
            'openLDAPPosix'      => array(
                'bind_requires_dn'       => true,
                'account_canonical_form' => 'dn',
                'allow_empty_password'   => false,
                'schema'                 => array(
                    'account'    => array(
                        'additional_dn'          => null,
                        'object_class'           => 'posixAccount',
                        'object_filter'          => '(objectclass=posixAccount)',
                        'username_attribute'     => 'uid',
                        'unique_id_attribute'    => 'entryUUID',
                        'first_name_attribute'   => 'givenName',
                        'last_name_attribute'    => 'sn',
                        'display_name_attribute' => 'displayName',
                        'email_attribute'        => 'mail',
                        'picture_attribute'      => 'jpegPhoto'
                    ),
                    'group'      => array(
                        'additional_dn'         => null,
                        'object_class'          => 'posixGroup',
                        'object_filter'         => '(objectclass=posixGroup)',
                        'name_attribute'        => 'cn',
                        'description_attribute' => 'description'
                    ),
                    'membership' => array(
                        'group_members_attribute'                   => 'memberUid',
                        'group_members_attribute_mapping_type'      => 'dn',
                        'account_membership_attribute'              => 'memberOf',
                        'account_membership_attribute_mapping_type' => 'dn',
                        'use_attribute_from_group'                  => false,
                        'use_attribute_from_account'                => false
                    )
                )
            ),
            'sunDirectoryServer' => array(
                'bind_requires_dn'       => true,
                'account_canonical_form' => 'dn',
                'allow_empty_password'   => false,
                'schema'                 => array(
                    'account'    => array(
                        'additional_dn'          => null,
                        'object_class'           => 'inetorgperson',
                        'object_filter'          => '(objectclass=inetorgperson)',
                        'username_attribute'     => 'cn',
                        'unique_id_attribute'    => 'entryUUID',
                        'first_name_attribute'   => 'givenName',
                        'last_name_attribute'    => 'sn',
                        'display_name_attribute' => 'displayName',
                        'email_attribute'        => 'mail',
                        'picture_attribute'      => 'jpegPhoto'
                    ),
                    'group'      => array(
                        'additional_dn'         => null,
                        'object_class'          => 'groupOfUniqueNames',
                        'object_filter'         => '(objectclass=groupOfUniqueNames)',
                        'name_attribute'        => 'cn',
                        'description_attribute' => 'description'
                    ),
                    'membership' => array(
                        'group_members_attribute'                   => 'uniqueMember',
                        'group_members_attribute_mapping_type'      => 'dn',
                        'account_membership_attribute'              => 'memberOf',
                        'account_membership_attribute_mapping_type' => 'dn',
                        'use_attribute_from_group'                  => false,
                        'use_attribute_from_account'                => false
                    )
                )
            ),
        );
    }
}