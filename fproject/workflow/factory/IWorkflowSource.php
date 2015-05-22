<?php
namespace fproject\workflow\factory;

use fproject\workflow\core\Status;
use fproject\workflow\core\Transition;
use fproject\workflow\core\Workflow;
use fproject\workflow\core\WorkflowBehavior;
use fproject\workflow\core\WorkflowException;

interface IWorkflowSource
{
    /**
     * Returns the Status instance with id $id.
     * In case of unexpected error the implementation must return a WorkflowException.
     *
     * @see Status
     * @param mixed $id the status id
     * @param WorkflowBehavior|string $wfIdOrModel
     * @return Status the status instance or NULL if no status could be found for this id.
     */
	public function getStatus($id, $wfIdOrModel = null);
	/**
	 * Returns an array containing all Status instances belonging to the workflow
	 * whose id is passed as argument.
	 * 
	 * @param string $id workflow Id
	 * @return array Status instances 
	 * @throws WorkflowException no workflow is found with this Id
	 */
	public function getAllStatuses($id);
	/**
	 * Returns an array of transitions leaving the status whose id is passed as argument.
	 *
	 * If no start status is found a WorkflowException must be thrown.
	 * If not outgoing transition exists for the status, an empty array must be returned.
	 * The array returned must be indexed by ....
	 *
	 * @param mixed $statusId
     * @param WorkflowBehavior|string $wfIdOrModel
     *
	 * @return Transition[] an array containing all out going transition from $statusId. If no such
	 * transition exist, this method returns an empty array.
	 * @throws WorkflowException unexpected error
     *
     * @see Transition
	 */
	public function getTransitions($statusId, $wfIdOrModel = null);
	/**
	 *
	 * @param mixed $startId
	 * @param mixed $endId
     * @param WorkflowBehavior|string $wfIdOrModel
     *
	 */
	public function getTransition($startId, $endId, $wfIdOrModel = null);
	/**
	 * Returns the workflow instance whose id is passed as argument.
	 * In case of unexpected error the implementation must return a WorkflowException.
	 *
	 * @see Workflow
	 * @param mixed $id the workflow id
	 * @return Workflow the workflow instance or NULL if no workflow could be found.
	 */
	public function getWorkflow($id);
}
