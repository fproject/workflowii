<?php
namespace fproject\workflow\core;

/**
 * This interface must be implemented by any PHP class that
 * is able to provide a workflow definition. 
 */
interface IWorkflowSource
{
	/**
	 * Returns the workflow definition in the form of an array.
	 * @return array
	 */
	public function getDefinition();
}
