<?php
/*
 * Copyright (C) 2018  NicheWork, LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark A. Hershberger <mah@nichework.com>
 * @author Robert Vogel <vogel@hallowelt.biz>
 */

namespace MediaWiki\Extension\LDAPGroups\Hook\UserLoggedIn;

use MediaWiki\Extension\LDAPGroups\Config;
use MediaWiki\Extension\LDAPProvider\DomainConfigFactory;
use MediaWiki\Extension\LDAPProvider\Hook\UserLoggedIn;
use MWException;

class SyncUserGroups extends UserLoggedIn {

	private $map;
	private $groupMap;

	/**
	 * This is called by parent::process(). We use the connection
	 * established by the parent class that is specific to the domain
	 * for this user.
	 */
	protected function doProcess() {
		$currentLDAPGroups = $this->filterNonLDAPGroups( $this->user->getGroups() );
		$ldapGroupMembership = $this->mapGroupsFromLDAP( $this->user->getName() );

		$groupsToRemove = array_diff( $currentLDAPGroups, $ldapGroupMembership );
		$groupsToAdd = array_diff( $ldapGroupMembership, $currentLDAPGroups );

		$this->addManagedGroups( $groupsToAdd );
		$this->removeManagedGroups( $groupsToRemove );
	}

	/**
	 * Get the list of Groups that are mananaged by LDAPGroups
	 * @param string $domain
	 * @return array
	 */
	public function getGroupConfig() {
		if ( !$this->domain && !$this->findDomainForUser() ) {
			throw new MWException( "No Domain found" );
		}

		if ( !isset( $this->map[$this->domain] ) ) {
			$groupMap = Config::newInstance()->get( "GroupRegistry" );
			if ( !isset( $groupMap[$this->domain] ) ) {
				$this->map[$this->domain]
					= DomainConfigFactory::getInstance()->factory(
					$this->domain,
					$this->getDomainConfigSection()
				);
			} else {
				$this->map[$this->domain] = $groupMap[$this->domain];
			}
		}

		return $this->map[$this->domain];
	}

	/**
	 * Given a list of groups return those that are managed in LDAP
	 *
	 * @param array $groups MediaWiki Groups
	 * @return array
	 */
	public function filterNonLDAPGroups( array $groups ) {
		$mapping = $this->getGroupConfig()->get( Config::MAPPING );
		$ret = [];
		foreach ( $groups as $group ) {
			if ( isset( $mapping[$group] ) ) {
				$ret[] = $group;
			}
		}
		return $ret;
	}

	public function mapGroupsFromLDAP( $username ) {
		$mapping = array_flip( $this->getGroupConfig()->get( Config::MAPPING ) );
		$groupList = $this->ldapClient->getUserGroups( $this->getGroupConfig(), $username );
		return array_map(
			function( $dn ) use ($mapping) {
				if ( isset( $mapping[$dn] ) ) {
					return $mapping[$dn];
				}
			}, $groupList->getFullDNs()
		);
	}

	/**
	 * Tell us if a memberOf attribute matches a MW group
	 * @param string $group LDAP group
	 * @return string
	 */
	public function getGroupForLDAPdn( $group ) {
		return $group;
	}

	/**
	 * Get the dn that they should be a memberOf entry
	 * @param string $group MediaWiki group
	 * @return string
	 */
	public function getLDAPdnForGroup( $group ) {
		return $group;
	}

	private function addManagedGroups( array $groups ) {
		foreach ( $groups as $group ) {
			if ( !$this->user->addGroup( $group ) ) {
				wfDebugLog(
					"LDAPGroups addGroup",
					"Problem adding user '{$this->user}' to the group '$group'."
				);
			}
		}
	}

	private function removeManagedGroups( array $groups ) {
		foreach ( $groups as $group ) {
			if ( !$this->user->removeGroup( $group ) ) {
				wfDebugLog(
					"LDAPGroups removeGroup",
					"Problem removing user '{$this->user}' from the group "
					. "'$group'."
				);
			}
		}
	}

	protected function getDomainConfigSection() {
		return Config::DOMAINCONFIG_SECTION;
	}

	public static function getVersion() {
		return "1.0.0-alpha";
	}
}
