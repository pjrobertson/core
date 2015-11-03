<?php

namespace Test\Share20;

use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\Files\Folder;
use OC\Share20\DefaultShareProvider;

class DefaultShareProviderTest extends \Test\TestCase {

	/** @var IDBConnection */
	protected $dbConn;

	/** @var IUserManager */
	protected $userManager;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var Folder */
	protected $userFolder;

	/** @var DefaultShareProvider */
	protected $provider;

	public function setUp() {
		$this->dbConn = \OC::$server->getDatabaseConnection();
		$this->userManager = $this->getMock('OCP\IUserManager');
		$this->groupManager = $this->getMock('OCP\IGroupManager');
		$this->userFolder = $this->getMock('OCP\Files\Folder');

		//Empty share table
		$this->dbConn->getQueryBuilder()->delete('share')->execute();

		$this->provider = new DefaultShareProvider(
			$this->dbConn,
			$this->userManager,
			$this->groupManager,
			$this->userFolder
		);
	}

	public function tearDown() {
		$this->dbConn->getQueryBuilder()->delete('share')->execute();
	}

	/**
	 * @expectedException OC\Share20\Exception\ShareNotFound
	 */
	public function testGetShareByIdNotExist() {
		$this->provider->getShareById(1);
	}

	public function testGetShareByIdUserShare() {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('share')
			->values([
				'id' => $qb->expr()->literal(1),
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_USER),
				'share_with' => $qb->expr()->literal('sharedWith'),
				'uid_owner' => $qb->expr()->literal('sharedBy'),
				'file_source' => $qb->expr()->literal(42),
				'permissions' => $qb->expr()->literal(13),
			]);
		$qb->execute();

		$storage = $this->getMock('OC\Files\Storage\Storage');
		$storage
			->expects($this->once())
			->method('getOwner')
			->willReturn('shareOwner');
		$path = $this->getMock('OCP\Files\Node');
		$path
			->expects($this->once())
			->method('getStorage')
			->wilLReturn($storage);
		$this->userFolder
			->expects($this->once())
			->method('getById')
			->with(42)
			->willReturn([$path]);

		$sharedWith = $this->getMock('OCP\IUser');
		$sharedBy = $this->getMock('OCP\IUser');
		$shareOwner = $this->getMock('OCP\IUser');
		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['sharedWith', $sharedWith],
				['sharedBy', $sharedBy],
				['shareOwner', $shareOwner],
			]));

		$share = $this->provider->getShareById(1);

		$this->assertEquals(1, $share->getId());
		$this->assertEquals(\OCP\Share::SHARE_TYPE_USER, $share->getShareType());
		$this->assertEquals($sharedWith, $share->getSharedWith());
		$this->assertEquals($sharedBy, $share->getSharedBy());
		$this->assertEquals($shareOwner, $share->getShareOwner());
		$this->assertEquals($path, $share->getPath());
		$this->assertEquals(13, $share->getPermissions());
		$this->assertEquals(null, $share->getToken());
		$this->assertEquals(null, $share->getExpirationDate());
	}

	public function testGetShareByIdGroupShare() {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('share')
			->values([
				'id' => $qb->expr()->literal(1),
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_GROUP),
				'share_with' => $qb->expr()->literal('sharedWith'),
				'uid_owner' => $qb->expr()->literal('sharedBy'),
				'file_source' => $qb->expr()->literal(42),
				'permissions' => $qb->expr()->literal(13),
			]);
		$qb->execute();

		$storage = $this->getMock('OC\Files\Storage\Storage');
		$storage
			->expects($this->once())
			->method('getOwner')
			->willReturn('shareOwner');
		$path = $this->getMock('OCP\Files\Node');
		$path
			->expects($this->once())
			->method('getStorage')
			->wilLReturn($storage);
		$this->userFolder
			->expects($this->once())
			->method('getById')
			->with(42)
			->willReturn([$path]);

		$sharedWith = $this->getMock('OCP\IGroup');
		$sharedBy = $this->getMock('OCP\IUser');
		$shareOwner = $this->getMock('OCP\IUser');
		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['sharedBy', $sharedBy],
				['shareOwner', $shareOwner],
			]));
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('sharedWith')
			->willReturn($sharedWith);

		$share = $this->provider->getShareById(1);

		$this->assertEquals(1, $share->getId());
		$this->assertEquals(\OCP\Share::SHARE_TYPE_GROUP, $share->getShareType());
		$this->assertEquals($sharedWith, $share->getSharedWith());
		$this->assertEquals($sharedBy, $share->getSharedBy());
		$this->assertEquals($shareOwner, $share->getShareOwner());
		$this->assertEquals($path, $share->getPath());
		$this->assertEquals(13, $share->getPermissions());
		$this->assertEquals(null, $share->getToken());
		$this->assertEquals(null, $share->getExpirationDate());
	}

	public function testGetShareByIdLinkShare() {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('share')
			->values([
				'id' => $qb->expr()->literal(1),
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_LINK),
				'share_with' => $qb->expr()->literal('sharedWith'),
				'uid_owner' => $qb->expr()->literal('sharedBy'),
				'file_source' => $qb->expr()->literal(42),
				'permissions' => $qb->expr()->literal(13),
				'token' => $qb->expr()->literal('token'),
				'expiration' => $qb->expr()->literal('2000-01-02 00:00:00'),
			]);
		$qb->execute();

		$storage = $this->getMock('OC\Files\Storage\Storage');
		$storage
			->expects($this->once())
			->method('getOwner')
			->willReturn('shareOwner');
		$path = $this->getMock('OCP\Files\Node');
		$path
			->expects($this->once())
			->method('getStorage')
			->wilLReturn($storage);
		$this->userFolder
			->expects($this->once())
			->method('getById')
			->with(42)
			->willReturn([$path]);

		$sharedBy = $this->getMock('OCP\IUser');
		$shareOwner = $this->getMock('OCP\IUser');
		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['sharedBy', $sharedBy],
				['shareOwner', $shareOwner],
			]));

		$share = $this->provider->getShareById(1);

		$this->assertEquals(1, $share->getId());
		$this->assertEquals(\OCP\Share::SHARE_TYPE_LINK, $share->getShareType());
		$this->assertEquals('sharedWith', $share->getSharedWith());
		$this->assertEquals($sharedBy, $share->getSharedBy());
		$this->assertEquals($shareOwner, $share->getShareOwner());
		$this->assertEquals($path, $share->getPath());
		$this->assertEquals(13, $share->getPermissions());
		$this->assertEquals('token', $share->getToken());
		$this->assertEquals(\DateTime::createFromFormat('Y-m-d H:i:s', '2000-01-02 00:00:00'), $share->getExpirationDate());
	}

	public function testGetShareByIdRemoteShare() {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('share')
			->values([
				'id' => $qb->expr()->literal(1),
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_REMOTE),
				'share_with' => $qb->expr()->literal('sharedWith'),
				'uid_owner' => $qb->expr()->literal('sharedBy'),
				'file_source' => $qb->expr()->literal(42),
				'permissions' => $qb->expr()->literal(13),
			]);
		$qb->execute();

		$storage = $this->getMock('OC\Files\Storage\Storage');
		$storage
			->expects($this->once())
			->method('getOwner')
			->willReturn('shareOwner');
		$path = $this->getMock('OCP\Files\Node');
		$path
			->expects($this->once())
			->method('getStorage')
			->wilLReturn($storage);
		$this->userFolder
			->expects($this->once())
			->method('getById')
			->with(42)
			->willReturn([$path]);

		$sharedBy = $this->getMock('OCP\IUser');
		$shareOwner = $this->getMock('OCP\IUser');
		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['sharedBy', $sharedBy],
				['shareOwner', $shareOwner],
			]));

		$share = $this->provider->getShareById(1);

		$this->assertEquals(1, $share->getId());
		$this->assertEquals(\OCP\Share::SHARE_TYPE_REMOTE, $share->getShareType());
		$this->assertEquals('sharedWith', $share->getSharedWith());
		$this->assertEquals($sharedBy, $share->getSharedBy());
		$this->assertEquals($shareOwner, $share->getShareOwner());
		$this->assertEquals($path, $share->getPath());
		$this->assertEquals(13, $share->getPermissions());
		$this->assertEquals(null, $share->getToken());
		$this->assertEquals(null, $share->getExpirationDate());
	}

	public function testDeleteSingleShare() {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('share')
			->values([
				'id' => $qb->expr()->literal(1),
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_USER),
				'share_with' => $qb->expr()->literal('sharedWith'),
				'uid_owner' => $qb->expr()->literal('sharedBy'),
				'file_source' => $qb->expr()->literal(42),
				'permissions' => $qb->expr()->literal(13),
			]);
		$qb->execute();

		$storage = $this->getMock('OC\Files\Storage\Storage');
		$storage
			->expects($this->once())
			->method('getOwner')
			->willReturn('shareOwner');
		$path = $this->getMock('OCP\Files\Node');
		$path
			->expects($this->once())
			->method('getStorage')
			->wilLReturn($storage);
		$this->userFolder
			->expects($this->once())
			->method('getById')
			->with(42)
			->willReturn([$path]);

		$sharedWith = $this->getMock('OCP\IUser');
		$sharedBy = $this->getMock('OCP\IUser');
		$shareOwner = $this->getMock('OCP\IUser');
		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['sharedWith', $sharedWith],
				['sharedBy', $sharedBy],
				['shareOwner', $shareOwner],
			]));

		$share = $this->provider->getShareById(1);
		$this->provider->delete($share);

		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('*')
			->from('share');

		$cursor = $qb->execute();
		$result = $cursor->fetchAll();
		$cursor->closeCursor();

		$this->assertEmpty($result);
	}

	public function testDeleteSingleShareKeepOther() {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->insert('share')
			->values([
				'id' => $qb->expr()->literal(1),
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_USER),
				'share_with' => $qb->expr()->literal('sharedWith'),
				'uid_owner' => $qb->expr()->literal('sharedBy'),
				'file_source' => $qb->expr()->literal(42),
				'permissions' => $qb->expr()->literal(13),
			]);
		$qb->execute();

		$qb = $this->dbConn->getQueryBuilder();
		$qb->insert('share')
			->values([
				'id' => $qb->expr()->literal(2),
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_USER),
				'share_with' => $qb->expr()->literal('sharedWith'),
				'uid_owner' => $qb->expr()->literal('sharedBy'),
				'file_source' => $qb->expr()->literal(42),
				'permissions' => $qb->expr()->literal(13),
			]);
		$qb->execute();


		$storage = $this->getMock('OC\Files\Storage\Storage');
		$storage
			->expects($this->once())
			->method('getOwner')
			->willReturn('shareOwner');
		$path = $this->getMock('OCP\Files\Node');
		$path
			->expects($this->once())
			->method('getStorage')
			->wilLReturn($storage);
		$this->userFolder
			->expects($this->once())
			->method('getById')
			->with(42)
			->willReturn([$path]);

		$sharedWith = $this->getMock('OCP\IUser');
		$sharedBy = $this->getMock('OCP\IUser');
		$shareOwner = $this->getMock('OCP\IUser');
		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['sharedWith', $sharedWith],
				['sharedBy', $sharedBy],
				['shareOwner', $shareOwner],
			]));

		$share = $this->provider->getShareById(1);
		$this->provider->delete($share);

		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('*')
			->from('share');

		$cursor = $qb->execute();
		$result = $cursor->fetchAll();
		$cursor->closeCursor();

		$this->assertCount(1, $result);
	}

	public function testDeleteNestedShares() {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->insert('share')
			->values([
				'id' => $qb->expr()->literal(1),
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_USER),
				'share_with' => $qb->expr()->literal('sharedWith'),
				'uid_owner' => $qb->expr()->literal('sharedBy'),
				'file_source' => $qb->expr()->literal(42),
				'permissions' => $qb->expr()->literal(13),
			]);
		$qb->execute();

		$qb = $this->dbConn->getQueryBuilder();
		$qb->insert('share')
			->values([
				'id' => $qb->expr()->literal(2),
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_USER),
				'share_with' => $qb->expr()->literal('sharedWith2'),
				'uid_owner' => $qb->expr()->literal('sharedBy2'),
				'file_source' => $qb->expr()->literal(42),
				'permissions' => $qb->expr()->literal(13),
				'parent' => $qb->expr()->literal(1),
			]);
		$qb->execute();

		$qb = $this->dbConn->getQueryBuilder();
		$qb->insert('share')
			->values([
				'id' => $qb->expr()->literal(3),
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_USER),
				'share_with' => $qb->expr()->literal('sharedWith2'),
				'uid_owner' => $qb->expr()->literal('sharedBy2'),
				'file_source' => $qb->expr()->literal(42),
				'permissions' => $qb->expr()->literal(13),
				'parent' => $qb->expr()->literal(2),
			]);
		$qb->execute();


		$storage = $this->getMock('OC\Files\Storage\Storage');
		$storage
			->expects($this->exactly(3))
			->method('getOwner')
			->willReturn('shareOwner');
		$path = $this->getMock('OCP\Files\Node');
		$path
			->expects($this->exactly(3))
			->method('getStorage')
			->wilLReturn($storage);
		$this->userFolder
			->expects($this->exactly(3))
			->method('getById')
			->with(42)
			->willReturn([$path]);

		$sharedWith = $this->getMock('OCP\IUser');
		$sharedBy = $this->getMock('OCP\IUser');
		$shareOwner = $this->getMock('OCP\IUser');
		$this->userManager
			->method('get')
			->will($this->returnValueMap([
				['sharedWith', $sharedWith],
				['sharedBy', $sharedBy],
				['shareOwner', $shareOwner],
			]));

		$share = $this->provider->getShareById(1);
		$this->provider->delete($share);

		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('*')
			->from('share');

		$cursor = $qb->execute();
		$result = $cursor->fetchAll();
		$cursor->closeCursor();

		$this->assertEmpty($result);
	}

	/**
	 * @expectedException \OC\Share20\Exception\BackendError
	 */
	public function testDeleteFails() {
		$share = $this->getMock('OC\Share20\IShare');
		$expr = $this->getMock('OCP\DB\QueryBuilder\IExpressionBuilder');
		$qb = $this->getMock('OCP\DB\QueryBuilder\IQueryBuilder');
		$qb->expects($this->once())
			->method('delete')
			->will($this->returnSelf());
		$qb->expects($this->once())
			->method('expr')
			->willReturn($expr);
		$qb->expects($this->once())
			->method('where')
			->will($this->returnSelf());
		$qb->expects($this->once())
			->method('setParameter')
			->will($this->returnSelf());
		$qb->expects($this->once())
			->method('execute')
			->will($this->throwException(new \Exception));

		$db = $this->getMock('OCP\IDBConnection');
		$db->expects($this->once())
			->method('getQueryBuilder')
			->with()
			->willReturn($qb);

		$provider = $this->getMockBuilder('OC\Share20\DefaultShareProvider')
            ->setConstructorArgs([  
                    $db,
                    $this->userManager,
                    $this->groupManager,
                    $this->userFolder,
                ]        
            )            
            ->setMethods(['deleteChildren'])
            ->getMock();
		$provider
			->expects($this->once())
			->method('deleteChildren')
			->with($share);
		

		$provider->delete($share);
	}

}
