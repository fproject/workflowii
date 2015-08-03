<?php
///////////////////////////////////////////////////////////////////////////////
//
// © Copyright f-project.net 2010-present. All Rights Reserved.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//
///////////////////////////////////////////////////////////////////////////////

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
     * @param Component|ActiveWorkflowBehavior $model the model that owns this workflow
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
     * @param Component|ActiveWorkflowBehavior $model the model that owns this workflow
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
     * @param Component|ActiveWorkflowBehavior $model the model that owns this workflow
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
     * @param Component|ActiveWorkflowBehavior $model the model that owns this workflow
     *
	 * @return Workflow the workflow instance or NULL if no workflow could be found.
     *
     * @see Workflow
	 */
	public function getWorkflow($id, $model);

    /**
     * Returns the id of the default workflow associated with the model.
     *
     * If no default workflow id has been configured, it is created by using the
     * short-name of the owner model class (i.e. the class name without the namespace part),
     * suffixed with defined by `workflowFactory`, default to 'Workflow'.
     *
     * For instance, class 'app\model\Post' has a default workflow id equals to 'PostWorkflow'.
     *
     * @param Component|ActiveWorkflowBehavior $model the model that owns this workflow
     *
     * @return string id for the workflow the owner model is in.
     */
    public function getDefaultWorkflowId($model);

    /**
     * Parses the string $val assuming it is a status id and returns and array
     * containing the workflow ID and status local ID.
     *
     * If $val does not include the workflow ID part (i.e it is not in formated like "workflowID/statusID")
     * this method uses $model and $defaultWorkflowId to get it.
     *
     * @param string $val the status ID to parse. If it is not an absolute ID, $helper is used to get the
     * workflow ID.
     * @param mixed $wfId the workflow ID
     * @param Component|ActiveWorkflowBehavior $model model used as workflow ID provider if needed
     *
     * @param array $wfDef output workflow definition if needed
     * @return string[] array containing the workflow ID in its first index, and the status Local ID
     * in the second
     * @throws WorkflowException
     * @see ArrayWorkflowItemFactory::evaluateWorkflowId()
     */
    public function parseWorkflowStatus($val, $wfId, $model, &$wfDef=null);

    /**
     * Loads definition for the workflow whose id is passed as argument.
     *
     * The workflow Id passed as argument is used to create the class name of the object
     * that holds the workflow definition.
     *
     * @param string $wfId the ID of workflow to search
     * @param Component|ActiveWorkflowBehavior $model
     *
     * @return array the workflow definition array
     *
     * @throws InvalidConfigException
     * @throws WorkflowException
     *
     */
    public function getWorkflowDefinition($wfId, $model);
}
