<?php

namespace OC\Share20;

use OCP\Files\Node;
use OCP\IUser;
use OCP\IGroup;

class Share implements IShare {

	/** @var string */
	private $id;

	/** @var Node */
	private $path;

	/** @var int */
	private $shareType;

	/** @var IUser|IGroup|string */
	private $sharedWith;

	/** @var IUser|string */
	private $sharedBy;

	/** @var IUser|string */
	private $shareOwner;

	/** @var int */
	private $permissions;

	/** @var \DateTime */
	private $expireDate;

	/** @var string */
	private $password;

	/** @var string */
	private $token;

	/** @var int */
	private $parent;

	/**
	 * Set the id of the share
	 *
	 * @param int id
	 * @return Share The modified object
	 */
	public function setId($id) {
		$this->id = $id;
		return $this;
	}

	/**
	 * Get the id of the share
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set the path of this share
	 *
	 * @param Node $path
	 * @return Share The modified object
	 */
	public function setPath(Node $path) {
		$this->path = $path;
		return $this;
	}

	/**
	 * Get the path of this share for the current user
	 * 
	 * @return Node
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Set the shareType
	 *
	 * @param int $shareType
	 * @return Share The modified object
	 */
	public function setShareType($shareType) {
		$this->shareType = $shareType;
		return $this;
	}

	/**
	 * Get the shareType 
	 *
	 * @return int
	 */
	public function getShareType() {
		return $this->shareType;
	}

	/**
	 * Set the receiver of this share
	 *
	 * @param IUser|IGroup|string
	 * @return Share The modified object
	 */
	public function setSharedWith($sharedWith) {
		$this->sharedWith = $sharedWith;
		return $this;
	}

	/**
	 * Get the receiver of this share
	 *
	 * @return IUser|IGroup|string
	 */
	public function getSharedWith() {
		return $this->sharedWith;
	}

	/**
	 * Set the permissions
	 *
	 * @param int $permissions
	 * @return Share The modified object
	 */
	public function setPermissions($permissions) {
		//TODO checkes

		$this->permissions = $permissions;
		return $this;
	}

	/**
	 * Get the share permissions
	 *
	 * @return int
	 */
	public function getPermissions() {
		return $this->permissions;
	}

	/**
	 * Set the expiration date
	 *
	 * @param \DateTime $expireDate
	 * @return Share The modified object
	 */
	public function setExpirationDate(\DateTime $expireDate) {
		//TODO checks

		$this->expireDate = $expireDate;
		return $this;
	}

	/**
	 * Get the share expiration date
	 *
	 * @return \DateTime
	 */
	public function getExpirationDate() {
		return $this->expireDate;
	}

	/**
	 * Set the sharer of the path
	 *
	 * @param IUser|string $sharedBy
	 * @return Share The modified object
	 */
	public function setSharedBy($sharedBy) {
		//TODO checks
		$this->sharedBy = $sharedBy;

		return $this;
	}

	/**
	 * Get share sharer
	 *
	 * @return IUser|string
	 */
	public function getSharedBy() {
		//TODO check if set
		return $this->sharedBy;
	}

	/**
	 * Set the original share owner (who owns the path)
	 *
	 * @param IUser|string
	 *
	 * @return Share The modified object
	 */
	public function setShareOwner($shareOwner) {
		//TODO checks

		$this->shareOwner = $shareOwner;
		return $this;
	}

	/**
	 * Get the original share owner (who owns the path)
	 * 
	 * @return IUser|string
	 */
	public function getShareOwner() {
		//TODO check if set
		return $this->shareOwner;
	}

	/**
	 * Set the password
	 *
	 * @param string $password
	 *
	 * @return Share The modified object
	 */
	public function setPassword($password) {
		//TODO verify

		$this->password = $password;
		return $this;
	}

	/**
	 * Get the password
	 *
	 * @return string
	 */
	public function getPassword($password) {
		return $this->password;
	}

	/**
	 * Set the token
	 *
	 * @param string $token
	 * @return Share The modified object
	 */
	public function setToken($token) {
		$this->token = $token;
		return $this;
	}

	/**
	 * Get the token
	 *
	 * @return string
	 */
	public function getToken() {
		return $this->token;
	}

	/**
	 * Set the parent id of this share
	 *
	 * @param int $parent
	 * @return Share The modified object
	 */
	public function setParent($parent) {
		$this->parent = $parent;
		return $this;
	}

	/**
	 * Get the parent id of this share
	 *
	 * @return int
	 */
	public function getParent() {
		return $this->parent;
	}
}
