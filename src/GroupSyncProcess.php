<?php

namespace MediaWiki\Extension\LDAPGroups;

class GroupSyncProcess {

	/**
	 *
	 * @var \User
	 */
	protected $user = null;

	/**
	 *
	 * @var \Config
	 */
	protected $config = null;

	/**
	 *
	 * @var \MediaWiki\Extension\LDAPProvider\Client
	 */
	protected $client = null;

	/**
	 *
	 * @var \Status
	 */
	protected $status = null;

	/**
	 *
	 * @param \User $user
	 * @param \Config $config
	 * @param \MediaWiki\Extension\LDAPProvider\Client $client
	 */
	public function __construct( $user, $config, $client ) {
		$this->user = $user;
		$this->config = $config;
		$this->client = $client;
	}

	/**
	 * @return \Status
	 */
	public function run() {
		$this->status = \Status::newGood();
		try {
			$groups = $this->client->getUserGroups( $this->user->getName() );
			$syncMechanism = $this->makeSyncMechanism();
			$this->status = $syncMechanism->sync( $this->user, $groups, $this->config );
		} catch ( \Exception $ex ) {
			$this->status = \Status::newFatal( $ex->getMessage() );
		}

		return $this->status;
	}

	/**
	 *
	 * @return ISyncMechanism
	 */
	private function makeSyncMechanism() {
		$factoryCallback = $this->config->get( 'mechanism' );
		return $factoryCallback();
	}

}
