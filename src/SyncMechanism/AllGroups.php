<?php

namespace MediaWiki\Extension\LDAPGroups\SyncMechanism;

use MediaWiki\Extension\LDAPGroups\Config;

class AllGroups extends Base {

	/**
	 *
	 */
	protected function doSync() {
		$loacallyManagedGroups = $this->config->get( Config::LOCALLY_MANAGED );
		$currentGroups = $this->user->getGroups();
		$ldapGroups = $this->groupList->getShortNames();

		$groupsToAdd = array_diff( $ldapGroups, $currentGroups );
		foreach ( $groupsToAdd as $groupToAdd ) {
			if ( in_array( $groupToAdd, $loacallyManagedGroups ) ) {
				continue;
			}
			$this->addGroup( $groupToAdd );
		}

		$groupsToRemove = array_diff( $currentGroups, $ldapGroups );
		foreach ( $groupsToRemove as $groupToRemove ) {
			if ( in_array( $groupToRemove, $loacallyManagedGroups ) ) {
				continue;
			}
			$this->removeGroup( $groupToRemove );
		}
	}
}
