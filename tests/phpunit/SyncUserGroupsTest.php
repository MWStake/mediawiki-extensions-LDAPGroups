<?php

namespace MediaWiki\Extension\LDAPGroups\Tests;

use GlobalVarConfig;
use HashConfig;
use MediaWiki\Extension\LDAPGroups\Hook\UserLoggedIn\SyncUserGroups;
use MediaWikiTestCase;
use RequestContext;
use User;

class SyncUserGroupsTest extends MediaWikiTestCase {

	public function testGroupsUnderManagement() {
		$user = User::newFromName( "einstein" );
		$syncUser = new SyncUserGroups(
			RequestContext::getMain(),
			new GlobalVarConfig(),
			$user
		);
		$this->assertArray( $syncUser->getGroupsUnderManagement() );
	}
}
