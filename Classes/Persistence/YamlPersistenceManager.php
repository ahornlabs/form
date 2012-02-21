<?php
namespace TYPO3\Form\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Form".                 *
 *                                                                        *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * persistence identifier is some resource:// uri probably
 *
 * @FLOW3\Scope("singleton")
 */
class YamlPersistenceManager implements FormPersistenceManagerInterface {

	/**
	 * @var string
	 */
	protected $savePath;

	/**
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		if (isset($settings['yamlPersistenceManager']['savePath'])) {
			$this->savePath = $settings['yamlPersistenceManager']['savePath'];
			if (!is_dir($this->savePath)) {
				\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($this->savePath);
			}
		}
	}

	public function load($persistenceIdentifier) {
		if (!$this->exists($persistenceIdentifier)) {
			throw new \TYPO3\Form\Exception\PersistenceManagerException(sprintf('The form identified by "%s" could not be loaded.', $persistenceIdentifier), 1329307034);
		}
		$formPathAndFilename = $this->getFormPathAndFilename($persistenceIdentifier);
		return \Symfony\Component\Yaml\Yaml::parse(file_get_contents($formPathAndFilename));
	}

	public function save($persistenceIdentifier, array $formDefinition) {
		$formPathAndFilename = $this->getFormPathAndFilename($persistenceIdentifier);
		file_put_contents($formPathAndFilename, \Symfony\Component\Yaml\Yaml::dump($formDefinition, 99));
	}

	public function exists($persistenceIdentifier) {
		return is_file($this->getFormPathAndFilename($persistenceIdentifier));
	}

	public function listForms() {
		$forms = array();
		$directoryIterator = new \DirectoryIterator($this->savePath);

		foreach ($directoryIterator as $fileObject) {
			if (!$fileObject->isFile()) {
				continue;
			}
			$fileInfo = pathinfo($fileObject->getFilename());
			if (strtolower($fileInfo['extension']) !== 'yaml') {
				continue;
			}
			$persistenceIdentifier = $fileInfo['filename'];
			$form = $this->load($persistenceIdentifier);
			$forms[] = array(
				'identifier' => $form['identifier'],
				'name' => isset($form['label']) ? $form['label'] : $form['identifier'],
				'persistenceIdentifier' => $persistenceIdentifier
			);
		}
		return $forms;
	}

	/**
	 * Returns the absolute path and filename of the form with the specified $persistenceIdentifier
	 * Note: This (intentionally) does not check whether the file actually exists
	 *
	 * @param string $persistenceIdentifier
	 * @return string the absolute path and filename of the form with the specified $persistenceIdentifier
	 */
	protected function getFormPathAndFilename($persistenceIdentifier) {
		$formFileName = sprintf('%s.yaml', $persistenceIdentifier);
		return \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($this->savePath, $formFileName));
	}
}
?>