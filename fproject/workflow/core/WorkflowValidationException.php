<?php
namespace fproject\workflow\core;

use Yii;
use yii\base\Exception;

class WorkflowValidationException extends Exception
{
	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
		return 'Workflow Validation Exception';
	}
}
