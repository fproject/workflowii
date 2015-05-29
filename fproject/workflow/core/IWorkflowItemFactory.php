<?php
namespace fproject\workflow\core;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Provides workflow items (Workflow, Status, Transitions) from
 * a workflow definition source.
 *
 * @property string workflowSourceSuffix the suffix to make workflow source from workflow ID
 * @property string workflowSuffix the suffix to make workflow ID from the owner model
 */
interface IWorkflowItemFactory
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
     * @param mixed $wfId the workflow ID
     * @param Component|ActiveWorkflowBehavior $model
     * @return IStatus the status instance or NULL if no status could be found for this id.
     *
     * @see IStatus
     *
     */
	public function getStatus($id, $wfId, $model);

	/**
	 * Returns an array containing all Status instances belonging to the workflow
	 * whose id is passed as argument.
	 * 
	 * @param string $id workflow Id
     * @param Component|ActiveWorkflowBehavior $model
     *
	 * @return IStatus[] An array of IStatus instances
	 * @throws WorkflowException no workflow is found with this Id
     *
     * @see IStatus
     *
	 */
	public function getAllStatuses($id, $model);

	/**
	 * Returns an array of out going transitions leaving the status whose id is passed as argument.
	 *
	 * If no start status is found a WorkflowException must be thrown.
	 * If not outgoing transition exists for the status, an empty array must be returned.
	 * The array returned must be indexed by ....
	 *
	 * @param mixed $statusId
     * @param mixed $wfId the workflow ID
     * @param Component|ActiveWorkflowBehavior $model
     *
	 * @return Transition[] an array containing all out going transition from $statusId. If no such
	 * transition exist, this method returns an empty array.
     *
	 * @throws WorkflowException unexpected error
     * @throws InvalidConfigException
     *
     * @see Transition
	 */
	public function getTransitions($statusId, $wfId, $model);

	/**
	 * Returns the transitions that leaving a specified status an go to another specified status.
     *
	 * @param mixed $startId the ID of start status
	 * @param mixed $endId the ID of end status
     * @param mixed $wfId the workflow ID
     * @param Component|ActiveWorkflowBehavior $model
     *
	 */
	public function getTransition($startId, $endId, $wfId, $model);

	/**
	 * Returns the workflow instance whose id is passed as argument.
	 * In case of unexpected error the implementation must return a WorkflowException.
	 *
	 * @param mixed $id the workflow id
     * @param Component|ActiveWorkflowBehavior $model
     *
	 * @return Workflow the workflow instance or NULL if no workflow could be found.
     *
     * @see Workflow
	 */
	public function getWorkflow($id, $model);
}
