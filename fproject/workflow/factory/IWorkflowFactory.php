<?php
namespace fproject\workflow\factory;

use fproject\workflow\core\IStatus;
use fproject\workflow\core\Transition;
use fproject\workflow\core\Workflow;
use fproject\workflow\core\ActiveWorkflowBehavior;
use fproject\workflow\core\WorkflowException;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Provides workflow items (Workflow, Status, Transitions) from
 * a workflow definition source.
 */
interface IWorkflowFactory
{
    /**
     * Returns the status whose id is passed as argument.
     * If this status was never loaded before, it is loaded now and stored for later use (lazy loading).
     *
     * If a $model is provided, it must be an instance of ActiveWorkflowBehavior, or a Component instance with a
     * ActiveWorkflowBehavior attached.
     * This model is used to complete the status ID if the one defined by the $id argument is not
     * complete (e.g. 'draft' instead of 'post/draft').
     *
     * @param mixed $id the status id
     * @param Component|ActiveWorkflowBehavior|string $wfIdOrModel
     * @return IStatus the status instance or NULL if no status could be found for this id.
     *
     * @see IStatus
     *
     */
	public function getStatus($id, $wfIdOrModel = null);

	/**
	 * Returns an array containing all Status instances belonging to the workflow
	 * whose id is passed as argument.
	 * 
	 * @param string $id workflow Id
	 * @return IStatus[] An array of IStatus instances
	 * @throws WorkflowException no workflow is found with this Id
     *
     * @see IStatus
     *
	 */
	public function getAllStatuses($id);

	/**
	 * Returns an array of out going transitions leaving the status whose id is passed as argument.
	 *
	 * If no start status is found a WorkflowException must be thrown.
	 * If not outgoing transition exists for the status, an empty array must be returned.
	 * The array returned must be indexed by ....
	 *
	 * @param mixed $statusId
     * @param Component|ActiveWorkflowBehavior|string $wfIdOrModel
     *
	 * @return Transition[] an array containing all out going transition from $statusId. If no such
	 * transition exist, this method returns an empty array.
     *
	 * @throws WorkflowException unexpected error
     * @throws InvalidConfigException
     *
     * @see Transition
	 */
	public function getTransitions($statusId, $wfIdOrModel = null);

	/**
	 * Returns the transitions that leaving a specified status an go to another specified status.
     *
	 * @param mixed $startId the ID of start status
	 * @param mixed $endId the ID of end status
     * @param Component|ActiveWorkflowBehavior|string $wfIdOrModel
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
