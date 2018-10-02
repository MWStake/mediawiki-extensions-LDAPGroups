<?php

namespace MediaWiki\Extension\LDAPGroups\SyncMechanism;

use MediaWiki\Extension\LDAPGroups\ISyncMechanism;

abstract class Base implements ISyncMechanism {

	/**
	 *
	 * @var \User
	 */
	protected $user = null;

	/**
	 *
	 * @var \MediaWiki\Extension\LDAPProvider\GroupList
	 */
	protected $groupList = null;

	/**
	 *
	 * @var \Config
	 */
	protected $config = null;

	/**
	 *
	 * @var \Status
	 */
	protected $status = null;

	/**
	 *
	 * @return ISyncMechanism
	 */
	public static function factory() {
		return new static();
	}

	/**
	 *
	 * @param \User $user
	 * @param \MediaWiki\Extension\LDAPProvider\GroupList $groupList
	 * @param \Config $config
	 * @return \Status
	 */
	public function sync( $user, $groupList, $config ) {
		$this->user = $user;
		$this->groupList = $groupList;
		$this->config = $config;

		$this->status = \Status::newGood();

		$this->doSync();

		return $this->status;
	}

	/**
	 * Do the actual work
	 */
	abstract protected function doSync();

	/**
	 *
	 * @param string $group
	 */
	protected function addGroup( $group ) {
		$success = $this->user->addGroup( $group );
		if ( !$success ) {
			wfDebugLog(
				"LDAPGroups addGroup",
				"Problem adding user '{$this->user}' to the group '$group'."
			);
		}
	}

	/**
	 *
	 * @param string $group
	 */
	protected function removeGroup( $group ) {
		$success = $this->user->removeGroup( $group );
		if ( !$success ) {
			wfDebugLog(
				"LDAPGroups removeGroup",
				"Problem removing user '{$this->user}' from the group '$group'."
			);
		}
	}

}
