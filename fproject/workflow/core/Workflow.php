<?php
namespace fproject\workflow\core;

use Yii;
use yii\base\InvalidConfigException;

class Workflow extends AbstractWorkflowItem
{
	const PARAM_INITIAL_STATUS_ID = 'initialStatusId';

	private $_id;
	private $_initialStatusId;

	public function __construct($config = [])
	{
		if (!empty($config['id'])) {
			$this->_id = $config['id'];
			unset($config['id']);
		} else {
			throw new InvalidConfigException('missing workflow id ');
		}

		if (!empty($config[self::PARAM_INITIAL_STATUS_ID])) {
			$this->_initialStatusId = $config[self::PARAM_INITIAL_STATUS_ID];
			unset($config[self::PARAM_INITIAL_STATUS_ID]);
		} else {
			throw new InvalidConfigException('missing initial status id');
		}
		parent::__construct($config);
	}

    /** @inheritdoc */
	public function getId()
	{
		return $this->_id;
	}

    /**
     * Gets the initial status's ID
     */
    public function getInitialStatusId()
	{
		return $this->_initialStatusId;
	}
}
