<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\IntegrityCheck;

use OC\IntegrityCheck\Exceptions\InvalidSignatureException;
use OC\IntegrityCheck\Helpers\EnvironmentHelper;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OC\Integritycheck\Iterator\ExcludeFileByNameFilterIterator;
use OC\IntegrityCheck\Iterator\ExcludeFoldersByPathFilterIterator;
use OCP\App\IAppManager;
use phpseclib\Crypt\RSA;
use phpseclib\File\X509;

/**
 * Class Checker handles the code signing using X.509 and RSA. ownCloud ships with
 * a public root certificate certificate that allows to issue new certificates that
 * will be trusted for signing code. The CN will be used to verify that a certificate
 * given to a third-party developer may not be used for other applications. For
 * example the author of the application "calendar" would only receive a certificate
 * only valid for this application.
 *
 * @package OC\IntegrityCheck
 */
class Checker {
	/** @var EnvironmentHelper */
	private $environmentHelper;
	/** @var IAppManager */
	private $appManager;
	/** @var FileAccessHelper */
	private $fileAccessHelper;

	/**
	 * @param EnvironmentHelper $environmentHelper
	 * @param FileAccessHelper $fileAccessHelper
	 * @param IAppManager $appManager
	 */
	public function __construct(EnvironmentHelper $environmentHelper,
								FileAccessHelper $fileAccessHelper,
								IAppManager $appManager) {
		$this->environmentHelper = $environmentHelper;
		$this->fileAccessHelper = $fileAccessHelper;
		$this->appManager = $appManager;
	}

	/**
	 * Enumerates all files belonging to the folder. Sensible defaults are excluded.
	 *
	 * @param string $folderToIterate
	 * @return \RecursiveIteratorIterator
	 */
	private function getFolderIterator($folderToIterate) {
		$dirItr = new \RecursiveDirectoryIterator(
			$folderToIterate,
			\RecursiveDirectoryIterator::SKIP_DOTS
		);
		$excludeGenericFilesIterator = new ExcludeFileByNameFilterIterator($dirItr);
		$excludeFoldersIterator = new ExcludeFoldersByPathFilterIterator($excludeGenericFilesIterator);

		return new \RecursiveIteratorIterator(
			$excludeFoldersIterator,
			\RecursiveIteratorIterator::SELF_FIRST
		);
	}

	/**
	 * Returns an array of ['filename' => 'SHA512-hash-of-file'] for all files found
	 * in the iterator.
	 *
	 * @param \RecursiveIteratorIterator $iterator
	 * @param string $path
	 * @return array
	 */
	private function generateHashes(\RecursiveIteratorIterator $iterator,
									$path) {
		$hashes = [];

		$baseDirectoryLength = strlen($path);
		foreach($iterator as $filename => $data) {
			/** @var \DirectoryIterator $data */
			if($data->isDir()) {
				continue;
			}

			$relativeFileName = substr($filename, $baseDirectoryLength);

			// Exclude signature.json files in the appinfo and root folder
			if($relativeFileName === '/appinfo/signature.json') {
				continue;
			}
			// Exclude signature.json files in the appinfo and core folder
			if($relativeFileName === '/core/signature.json') {
				continue;
			}

			$hashes[$relativeFileName] = hash_file('sha512', $filename);
		}
		return $hashes;
	}

	/**
	 * Creates the signature data
	 *
	 * @param array $hashes
	 * @param X509 $certificate
	 * @param RSA $privateKey
	 * @return string
	 */
	private function createSignatureData(array $hashes,
										 X509 $certificate,
										 RSA $privateKey) {
		$signature = $privateKey->sign(json_encode($hashes));

		return [
				'hashes' => $hashes,
				'signature' => base64_encode($signature),
				'certificate' => $certificate->saveX509($certificate->currentCert),
			];
	}

	/**
	 * Write the signature of the specified app
	 *
	 * @param string $appId
	 * @param X509 $certificate
	 * @param RSA $privateKey
	 * @throws \Exception
	 */
	public function writeAppSignature($appId,
									  X509 $certificate,
									  RSA $privateKey) {
		$path = $this->appManager->getAppPath($appId);
		$iterator = $this->getFolderIterator($path);
		$hashes = $this->generateHashes($iterator, $path);
		$signature = $this->createSignatureData($hashes, $certificate, $privateKey);
		$this->fileAccessHelper->file_put_contents(
				$path . '/appinfo/signature.json',
				json_encode($signature, JSON_PRETTY_PRINT)
		);
	}

	/**
	 * Write the signature of core
	 *
	 * @param X509 $certificate
	 * @param RSA $rsa
	 */
	public function writeCoreSignature(X509 $certificate,
									   RSA $rsa) {
		$iterator = $this->getFolderIterator($this->environmentHelper->getServerRoot());
		$hashes = $this->generateHashes($iterator, $this->environmentHelper->getServerRoot());
		$signatureData = $this->createSignatureData($hashes, $certificate, $rsa);
		$this->fileAccessHelper->file_put_contents(
				$this->environmentHelper->getServerRoot() . '/core/signature.json',
				json_encode($signatureData, JSON_PRETTY_PRINT)
		);
	}

	/**
	 * @param string $signaturePath
	 * @param string $basePath
	 * @param string $certificateCN
	 * @return array
	 * @throws InvalidSignatureException
	 * @throws \Exception
	 */
	private function verify($signaturePath, $basePath, $certificateCN) {
		$signatureData = json_decode($this->fileAccessHelper->file_get_contents($signaturePath), true);
		if(!is_array($signatureData)) {
			throw new InvalidSignatureException('Signature data not found.');
		}

		$expectedHashes = $signatureData['hashes'];
		$signature = base64_decode($signatureData['signature']);
		$certificate = $signatureData['certificate'];

		// Check if certificate is signed by ownCloud Root Authority
		$x509 = new \phpseclib\File\X509();
		$rootCertificatePublicKey = $this->fileAccessHelper->file_get_contents($this->environmentHelper->getServerRoot().'/resources/codesigning/root.crt');
		$x509->loadCA($rootCertificatePublicKey);
		$x509->loadX509($certificate);
		// TODO: Load CRL
		// $crl = $this->fileAccessHelper->file_get_contents($this->environmentHelper->getServerRoot().'/resources/codesigning/revoked.crl');
		// $x509->loadCRL($crl);
		if(!$x509->validateSignature()) {
			throw new InvalidSignatureException('Certificate is not valid.');
		}
		// Verify if certificate has proper CN. "core" CN is always trusted.
		if($x509->getDN(true) !== 'CN='.$certificateCN && $x509->getDN(true) !== 'CN=core') {
			throw new InvalidSignatureException(
					sprintf('Certificate is not valid for required scope. (Requested: %s, current: %s)', $certificateCN, $x509->getDN(true))
			);
		}

		// Check if the signature of the files is valid
		$rsa = new \phpseclib\Crypt\RSA();
		$rsa->loadKey($x509->currentCert['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey']);
		if(!$rsa->verify(json_encode($expectedHashes), $signature)) {
			throw new InvalidSignatureException('Signature could not get verified.');
		}

		// Compare the list of files which are not identical
		$currentInstanceHashes = $this->generateHashes($this->getFolderIterator($basePath), $basePath);
		$differencesA = array_diff($expectedHashes, $currentInstanceHashes);
		$differencesB = array_diff($currentInstanceHashes, $expectedHashes);
		$differences = array_unique(array_merge($differencesA, $differencesB));
		$differenceArray = [];
		foreach($differences as $filename => $hash) {
			// Check if file should not exist in the new signature table
			if(!array_key_exists($filename, $expectedHashes)) {
				$differenceArray['FILE_TOO_MUCH'][$filename]['expected'] = '';
				$differenceArray['FILE_TOO_MUCH'][$filename]['current'] = $hash;
				continue;
			}

			// Check if file is missing
			if(!array_key_exists($filename, $currentInstanceHashes)) {
				$differenceArray['FILE_MISSING'][$filename]['expected'] = $expectedHashes[$filename];
				$differenceArray['FILE_MISSING'][$filename]['current'] = '';
				continue;
			}

			// Check if hash does mismatch
			if($expectedHashes[$filename] !== $currentInstanceHashes[$filename]) {
				$differenceArray['INVALID_HASH'][$filename]['expected'] = $expectedHashes[$filename];
				$differenceArray['INVALID_HASH'][$filename]['current'] = $currentInstanceHashes[$filename];
				continue;
			}

			// Should never happen.
			throw new \Exception('Invalid behaviour in file hash comparison experienced. Please report this error to the developers.');
		}

		return $differenceArray;
	}

	/**
	 * Verify the signature of $appId. Returns an array with the following content:
	 * [
	 * 	'FILE_MISSING' =>
	 * 	[
	 * 		'filename' => [
	 * 			'expected' => 'expectedSHA512',
	 * 			'current' => 'currentSHA512',
	 * 		],
	 * 	],
	 * 	'FILE_TOO_MUCH' =>
	 * 	[
	 * 		'filename' => [
	 * 			'expected' => 'expectedSHA512',
	 * 			'current' => 'currentSHA512',
	 * 		],
	 * 	],
	 * 	'INVALID_HASH' =>
	 * 	[
	 * 		'filename' => [
	 * 			'expected' => 'expectedSHA512',
	 * 			'current' => 'currentSHA512',
	 * 		],
	 * 	],
	 * ]
	 *
	 * Array may be empty in case no problems have been found.
	 *
	 * @param string $appId
	 * @return array
	 * @throws InvalidSignatureException
	 * @throws \Exception
	 */
	public function verifyAppSignature($appId) {
		$path = $this->appManager->getAppPath($appId);
		return $this->verify(
				$path .'/appinfo/signature.json',
				$path,
				$appId
		);
	}

	/**
	 * Verify the signature of core. Returns an array with the following content:
	 * [
	 * 	'FILE_MISSING' =>
	 * 	[
	 * 		'filename' => [
	 * 			'expected' => 'expectedSHA512',
	 * 			'current' => 'currentSHA512',
	 * 		],
	 * 	],
	 * 	'FILE_TOO_MUCH' =>
	 * 	[
	 * 		'filename' => [
	 * 			'expected' => 'expectedSHA512',
	 * 			'current' => 'currentSHA512',
	 * 		],
	 * 	],
	 * 	'INVALID_HASH' =>
	 * 	[
	 * 		'filename' => [
	 * 			'expected' => 'expectedSHA512',
	 * 			'current' => 'currentSHA512',
	 * 		],
	 * 	],
	 * ]
	 *
	 * Array may be empty in case no problems have been found.
	 *
	 * @return array
	 * @throws InvalidSignatureException
	 * @throws \Exception
	 */
	public function verifyCoreSignature() {
		return $this->verify(
				$this->environmentHelper->getServerRoot() . '/core/signature.json',
				$this->environmentHelper->getServerRoot(),
				'core'
		);
	}

}
