<?php
namespace WMS\LdapBundle\DependencyInjection\Factory;

use WMS\Ldap\Configuration;
use WMS\Ldap\Enum as Enum;
use Psr\Log\LoggerInterface;
use Zend\Ldap\Dn;

class ConfigurationFactory
{
    public function createConfiguration(array $connectionOptions, LoggerInterface $logger = null)
    {
        $configuration = new Configuration();

        $configuration->setHost($connectionOptions['host']);
        $configuration->setPort($connectionOptions['port']);
        $configuration->setBaseDn($connectionOptions['base_dn']);
        $configuration->setDomainName($connectionOptions['domain_name']);
        $configuration->setDomainNameShort($connectionOptions['domain_name_short']);
        $configuration->setUser($connectionOptions['user']);
        $configuration->setPassword($connectionOptions['password']);
        $configuration->setTimeout($connectionOptions['timeout']);
        $configuration->setUseSsl($connectionOptions['use_ssl']);
        $configuration->setUseStartTls($connectionOptions['use_start_tls']);
        $configuration->setTryUsernameSplit($connectionOptions['try_username_split']);
        $configuration->setFollowReferals($connectionOptions['follow_referrals']);

        $configuration->setBindRequiresDn($connectionOptions['parameters']['bind_requires_dn']);
        $configuration->setAccountCanonicalForm(
            $this->getCanonicalFormFromString($connectionOptions['parameters']['account_canonical_form'])
        );
        $configuration->setAllowEmptyPassword($connectionOptions['parameters']['allow_empty_password']);

        $configuration->setAccountSearchDn(
            $this->getChildDn(
                $connectionOptions['parameters']['schema']['account']['additional_dn'],
                $configuration->getBaseDn()
            )
        );
        $configuration->setAccountObjectClass($connectionOptions['parameters']['schema']['account']['object_class']);
        $configuration->setAccountObjectFilter($connectionOptions['parameters']['schema']['account']['object_filter']);
        $configuration->setAccountUsernameAttribute(
            $connectionOptions['parameters']['schema']['account']['username_attribute']
        );
        $configuration->setAccountUniqueIdAttribute(
            $connectionOptions['parameters']['schema']['account']['unique_id_attribute']
        );
        $configuration->setAccountFirstNameAttribute(
            $connectionOptions['parameters']['schema']['account']['first_name_attribute']
        );
        $configuration->setAccountLastNameAttribute(
            $connectionOptions['parameters']['schema']['account']['last_name_attribute']
        );
        $configuration->setAccountDisplayNameAttribute(
            $connectionOptions['parameters']['schema']['account']['display_name_attribute']
        );
        $configuration->setAccountEmailAttribute(
            $connectionOptions['parameters']['schema']['account']['email_attribute']
        );
        $configuration->setAccountPictureAttribute(
            $connectionOptions['parameters']['schema']['account']['picture_attribute']
        );

        $configuration->setGroupSearchDn(
            $this->getChildDn(
                $connectionOptions['parameters']['schema']['group']['additional_dn'],
                $configuration->getBaseDn()
            )
        );
        $configuration->setGroupObjectClass($connectionOptions['parameters']['schema']['group']['object_class']);
        $configuration->setGroupObjectFilter($connectionOptions['parameters']['schema']['group']['object_filter']);
        $configuration->setGroupNameAttribute($connectionOptions['parameters']['schema']['group']['name_attribute']);
        $configuration->setGroupDescriptionAttribute(
            $connectionOptions['parameters']['schema']['group']['description_attribute']
        );

        $configuration->setGroupMembersAttribute(
            $connectionOptions['parameters']['schema']['membership']['group_members_attribute']
        );
        $configuration->setGroupMembersAttributeMappingType(
            $this->getGroupMembersAttributeMappingTypeFromString(
                $connectionOptions['parameters']['schema']['membership']['group_members_attribute_mapping_type']
            )
        );
        $configuration->setAccountMembershipAttribute(
            $connectionOptions['parameters']['schema']['membership']['account_membership_attribute']
        );
        $configuration->setAccountMembershipAttributeMappingType(
            $this->getAccountMembershipAttributeMappingTypeFromString(
                $connectionOptions['parameters']['schema']['membership']['group_members_attribute_mapping_type']
            )
        );
        $configuration->setMembershipUseAttributeFromGroup(
            $connectionOptions['parameters']['schema']['membership']['use_attribute_from_group']
        );
        $configuration->setMembershipUseAttributeFromUser(
            $connectionOptions['parameters']['schema']['membership']['use_attribute_from_account']
        );

        $configuration->setLogger($logger);
        return $configuration;
    }

    private function getCanonicalFormFromString($string)
    {
        switch ($string) {
            case 'dn':
                return Enum\CanonicalAccountNameForm::DN;
            case 'username':
                return Enum\CanonicalAccountNameForm::USERNAME;
            case 'principal':
                return Enum\CanonicalAccountNameForm::PRINCIPAL;
            case 'backslash':
                return Enum\CanonicalAccountNameForm::BACKSLASH;
        }

        return null;
    }

    private function getGroupMembersAttributeMappingTypeFromString($string)
    {
        switch ($string) {
            case 'dn':
                return Enum\GroupMembersMappingType::DN;
            case 'username':
                return Enum\GroupMembersMappingType::USERNAME;
            case 'unique_id':
                return Enum\GroupMembersMappingType::UNIQUE_ID;
        }

        return null;
    }

    private function getAccountMembershipAttributeMappingTypeFromString($string)
    {
        switch ($string) {
            case 'dn':
                return Enum\AccountMembershipMappingType::DN;
            case 'name':
                return Enum\AccountMembershipMappingType::NAME;
        }

        return null;
    }

    private function getChildDn($childDn, $parentDn)
    {
        if ($childDn === null || $childDn === '') {
            return null;
        }

        if (Dn::isChildOf($childDn, $parentDn)) {
            return $childDn;
        }

        return $childDn . ',' . $parentDn;
    }
} 