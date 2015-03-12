<?php
namespace Flowpack\ComposerPlugin;

/**
 * Custom installer for flow packages.
 *
 */
class Installer implements \Composer\Installer\InstallerInterface {

	/**
	 * Allowed package type prefixes for valid flow packages.
	 *
	 * @var array
	 */
	protected $allowedPackageTypePrefixes = array('typo3-flow-');

	protected $packageTypeToPathMapping = array(
		'plugin' => 'Packages/Plugins/{flowPackageName}/',
		'site' => 'Packages/Sites/{flowPackageName}/',
		'boilerplate' => 'Packages/Boilerplates/{flowPackageName}/',
		'build' => 'Build/{flowPackageName}/',
		'*' => 'Packages/{camelCasedType}/{flowPackageName}/'
	);

	/**
	 * Decides if the installer supports the given type
	 *
	 * @param  string $packageType
	 * @return bool
	 */
	public function supports($packageType) {
		if ($this->getFlowPackageType($packageType) === FALSE) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Checks that provided package is installed.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param \Composer\Package\PackageInterface $package package instance
	 *
	 * @return bool
	 */
	public function isInstalled(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $package) {
		// TODO: Implement isInstalled() method.
	}

	/**
	 * Installs specific package.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param \Composer\Package\PackageInterface $package package instance
	 */
	public function install(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $package) {
		// TODO: Implement install() method.
	}

	/**
	 * Updates specific package.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param \Composer\Package\PackageInterface $initial already installed package version
	 * @param \Composer\Package\PackageInterface $target updated version
	 *
	 * @throws \Composer\Installer\InvalidArgumentException if $initial package is not installed
	 */
	public function update(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $initial, \Composer\Package\PackageInterface $target) {
		// TODO: Implement update() method.
	}

	/**
	 * Uninstalls specific package.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param \Composer\Package\PackageInterface $package package instance
	 */
	public function uninstall(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $package) {
		// TODO: Implement uninstall() method.
	}

	/**
	 * Returns the installation path of a package
	 *
	 * @param  \Composer\Package\PackageInterface $package
	 * @return string           path
	 */
	public function getInstallPath(\Composer\Package\PackageInterface $package) {
		$flowPackageType = $this->getFlowPackageType($package->getType());
		$camelCasedType = $this->camelCaseFlowPackageType($flowPackageType);
		$flowPackageName = $this->deriveFlowPackageKey($package);

		if (isset($this->packageTypeToPathMapping[$flowPackageType])) {
			$installPath = $this->packageTypeToPathMapping[$flowPackageType];
		} else {
			$installPath = $this->packageTypeToPathMapping['*'];
		}

		return $this->replacePlaceHoldersInPath($installPath, compact('flowPackageType', 'camelCasedType', 'flowPackageName'));
	}

	/**
	 * @param string $flowPackageType
	 * @return string
	 */
	protected function camelCaseFlowPackageType($flowPackageType) {
		$packageTypeParts = explode('-', $flowPackageType);
		$packageTypeParts = array_map('ucfirst', $packageTypeParts);
		return implode('', $packageTypeParts);
	}

	/**
	 * Replace path placeholders in the install path.
	 *
	 * @param string $path
	 * @param array $arguments
	 * @return string
	 */
	protected function replacePlaceHoldersInPath($path, $arguments) {
		foreach ($arguments as $argumentName => $argumentValue) {
			$path = str_replace('{' . $argumentName . '}', $argumentValue, $path);
		}

		return $path;
	}

	/**
	 * Gets the Flow package type based on the given composer package type. "typo3-flow-framework" would return "framework".
	 * Returns FALSE if the given composerPackageType is not a Flow package type.
	 *
	 * @param string $composerPackageType
	 * @return bool|string
	 */
	protected function getFlowPackageType($composerPackageType) {
		foreach ($this->allowedPackageTypePrefixes as $allowedPackagePrefix) {
			$packagePrefixPosition = strpos($composerPackageType, $allowedPackagePrefix);
			if ($packagePrefixPosition === 0) {
				return substr($composerPackageType, strlen($allowedPackagePrefix));
			}
		}

		return FALSE;
	}

	/**
	 * Find the correct Flow package name for the given package.
	 *
	 * @param  array $vars
	 * @return array
	 */
	public function deriveFlowPackageKey(\Composer\Package\PackageInterface $package) {
		$autoload = $package->getAutoload();
		if (isset($autoload['psr-0']) && is_array($autoload['psr-0'])) {
			$namespace = key($autoload['psr-0']);
			$flowPackageName = str_replace('\\', '.', $namespace);
		} elseif (isset($autoload['psr-4']) && is_array($autoload['psr-4'])) {
			$namespace = key($autoload['psr-4']);
			$flowPackageName = rtrim(str_replace('\\', '.', $namespace), '.');
		}

		return $flowPackageName;
	}

}