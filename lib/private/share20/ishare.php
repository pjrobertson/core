<?php

namespace OC\Share20;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IUser;
use OCP\IGroup;

interface IShare {

	/**
	 * Get the id of the share
	 *
	 * @return string
	 */
	public function getId();

	/**
	 * Set the path of this share
	 *
	 * @param File|Folder $path
	 * @return Share The modified object
	 */
	public function setPath(Node $path);

	/**
	 * Get the path of this share for the current user
	 * 
	 * @return File|Folder
	 */
	public function getPath();

	/**
	 * Set the shareType
	 *
	 * @param int $shareType
	 * @return Share The modified object
	 */
	public function setShareType($shareType);

	/**
	 * Get the shareType 
	 *
	 * @return int
	 */
	public function getShareType();

	/**
	 * Set the receiver of this share
	 *
	 * @param IUser|IGroup|string
	 * @return Share The modified object
	 */
	public function setSharedWith($sharedWith);

	/**
	 * Get the receiver of this share
	 *
	 * @return IUser|IGroup|string
	 */
	public function getSharedWith();

	/**
	 * Set the permissions
	 *
	 * @param int $permissions
	 * @return Share The modified object
	 */
	public function setPermissions($permissions);

	/**
	 * Get the share permissions
	 *
	 * @return int
	 */
	public function getPermissions();

	/**
	 * Set the expiration date
	 *
	 * @param \DateTime $expireDate
	 * @return Share The modified object
	 */
	public function setExpirationDate(\DateTime $expireDate);

	/**
	 * Get the share expiration date
	 *
	 * @return \DateTime
	 */
	public function getExpirationDate();

	/**
	 * Get share sharer
	 *
	 * @return IUser|string
	 */
	public function getSharedBy();

	/**
	 * Get the original share owner (who owns the path)
	 * 
	 * @return IUser|string
	 */
	public function getShareOwner();

	/**
	 * Set the password
	 *
	 * @param string $password
	 *
	 * @return Share The modified object
	 */
	public function setPassword($password);

	/**
	 * Get the token
	 *
	 * @return string
	 */
	public function getToken();

	/**
	 * Get the parent it
	 *
	 * @return int
	 */
	public function getParent();
}
