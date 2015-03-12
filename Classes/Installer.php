<?php
namespace Flowpack\ComposerPlugin;

/**
 * Custom installer for flow packages.
 *
 */
class Installer extends \Composer\Installer\LibraryInstaller implements \Composer\Installer\InstallerInterface {

	/**
	 * Allowed package type prefixes for valid flow packages.
	 *
	 * @var array
	 */
	protected $allowedPackageTypePrefixes = array('typo3-flow-');

	/**
	 * Flow package type to path mapping templates.
	 *
	 * @var array
	 */
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
	 * Returns the installation path of a package
	 *
	 * @param  \Composer\Package\PackageInterface $package
	 * @return string           path
	 */
	public function getInstallPath(\Composer\Package\PackageInterface $package) {
		$flowPackageType = $this->getFlowPackageType($package->getType());
		$camelCasedType = $this->camelCaseFlowPackageType($flowPackageType);
		$flowPackageName = $this->deriveFlowPackageName($package);

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
	 * @return string
	 */
	protected function deriveFlowPackageName(\Composer\Package\PackageInterface $package) {
		$autoload = $package->getAutoload();
		if (isset($autoload['psr-0']) && is_array($autoload['psr-0'])) {
			$namespace = key($autoload['psr-0']);
			$flowPackageName = str_replace('\\', '.', $namespace);
		} elseif (isset($autoload['psr-4']) && is_array($autoload['psr-4'])) {
			$namespace = key($autoload['psr-4']);
			$flowPackageName = rtrim(str_replace('\\', '.', $namespace), '.');
		} else {
			$extras = $package->getExtra();
			if (isset($extras['flowPackageName'])) {
				$flowPackageName = $extras['flowPackageName'];
			} else {
				// FIXME: This should never happen, but we will try to make something useful anyway.
				$composerType = $package->getType();
				$typeParts = explode('/', $composerType);
				arrray_map('ucfirst', $typeParts);
				$flowPackageName = implode('.', $typeParts);
			}
		}

		return $flowPackageName;
	}

}