<?php
namespace tests\codeception\unit\models;

use Yii;
use fproject\workflow\core\IStatusAccessor;

class StatusAccessor07 implements IStatusAccessor
{
	public static $instanceCount = 0;

	public $callGetStatusCount = 0;
	public $callCommitStatusCount = 0;
	public $callSetStatusCount = 0;

	public $callSetStatusLastArg = [];
	public $statusToReturnOnGet = null;

	public function __construct()
	{
		StatusAccessor07::$instanceCount++;
	}
	public function resetCallCounters()
	{
		$this->callGetStatusCount = 0;
		$this->callCommitStatusCount = 0;
		$this->callSetStatusCount = 0;
		$this->callSetStatusLastArg = [];
	}

	/**
	 * @inheritdoc
	 */
	public function readStatus($model) {
		$this->callGetStatusCount++;
		return $this->statusToReturnOnGet;
	}

    /**
     * @inheritdoc
     */
	public function commitStatus($model)
	{
		$this->callCommitStatusCount++;

	}

    /**
     * @inheritdoc
     */
	public function updateStatus($model, $status = null) {
		$this->callSetStatusCount++;
		$this->callSetStatusLastArg = [$model, $status];
	}
}