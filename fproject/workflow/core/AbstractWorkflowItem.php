<?php
namespace fproject\workflow\core;

use yii\base\Object;

/**
 * This is the abstract class for Workflow, Transition and Status objects.
 *
 * It mainly provides a way to store additional class properties without the need to
 * declare them in the class definition. Theses properties are called metadata and stored into
 * an array. They can be accessed like regular class properties.
 *
 */
abstract class AbstractWorkflowItem extends Object implements IAbstractWorkflowItem
{
	private $_metadata = [];

	/**
	 *
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		if (!empty($config['metadata']) && is_array($config['metadata'])) {
			$this->_metadata = $config['metadata'];
			unset($config['metadata']);
		}
		parent::__construct($config);
	}

    /**
     *
     * @see \yii\base\Object::__get()
     * @param string $name
     * @return mixed
     * @throws WorkflowException
     */
	public function __get($name)
	{
		if ($this->canGetProperty($name)) {
			return parent::__get($name);
		} elseif ($this->hasMetadata($name)) {
			return  $this->_metadata[$name];
		} else {
			throw new WorkflowException("No metadata found is the name '$name'");
		}
	}

	/**
	 * @return string the object identifier
	 */
	abstract public function getId();

	/**
	 *
	 * @inheritdoc
	 */
	public function getMetadata($paramName = null, $defaultValue = null)
	{
		if ($paramName === null) {
			return $this->_metadata;
		} elseif($this->hasMetadata($paramName)) {
			return $this->_metadata[$paramName];
		} else {
			return $defaultValue;
		}
	}

    /**
     *
     * @param mixed $paramName
     * @return bool
     * @throws WorkflowException
     */
	public function hasMetadata($paramName)
	{
		if (!is_string($paramName) || empty($paramName)) {
			throw new WorkflowException("Invalid metadata name : non empty string expected");
		}
		return array_key_exists($paramName, $this->_metadata);
	}
}
