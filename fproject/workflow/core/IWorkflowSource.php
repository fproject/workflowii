<?php
namespace fproject\workflow\core;
use yii\base\Component;

/**
 * This interface must be implemented by any PHP class that
 * is able to provide a workflow definition. 
 */
interface IWorkflowSource
{
	/**
	 * Returns the workflow definition in the form of an array.
     * @param Component|ActiveWorkflowBehavior $model
	 * @return array
	 */
	public function getDefinition($model);
}
