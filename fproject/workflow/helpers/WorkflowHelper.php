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

namespace fproject\workflow\helpers;

use fproject\workflow\core\IWorkflowItemFactory;
use fproject\workflow\core\Status;
use yii\base\Component;
use fproject\workflow\core\ActiveWorkflowBehavior;
use fproject\workflow\core\WorkflowException;

class WorkflowHelper
{
	/**
	 * Returns an associative array containing all statuses that can be reached by model.
	 * 
	 * Note that the current model status is NOT included in this list.
	 * @param Model|ActiveWorkflowBehavior
	 * @param boolean $validate
	 * @param boolean $beforeEvents
	 * @throws WorkflowException
	 * @return array
	 */
	public static function getNextStatusListData($model, $validate = false, $beforeEvents = false)
	{
		if (!ActiveWorkflowBehavior::isAttachedTo($model))
        {
			throw new WorkflowException('The model does not have a ActiveWorkflowBehavior behavior');
		}
		$listData = [];
        /** @var ActiveWorkflowBehavior $model */
		$report = $model->getNextStatuses($validate, $beforeEvents);
		foreach ($report as $endStatusId => $info)
        {
			if (!isset($info['isValid']) || $info['isValid'] === true)
            {
                /** @var Status $sts */
                $sts = $info['status'];
				$listData[$endStatusId] = $sts->getLabel();
			}
		}
		return $listData;
	}

	/**
	 * Returns an associative array containing all statuses that belong to a workflow.
	 * The array returned is suitable to be used as list data value in (for instance) a dropdown list control.
	 * 
	 * Usage example : assuming model Post has a ActiveWorkflowBehavior the following code displays a dropdown list
	 * containing all statuses defined in $post current the workflow : 
	 * 
	 * echo Html::dropDownList(
	 * 		'status',
	 * 		null,
	 * 		WorkflowHelper::getAllStatusListData(
	 * 			$post->getWorkflow()->getId(),
	 * 			$post->getWorkflowFactory()
	 * 		)
	 * )
	 * 
	 * @param string $workflowId
	 * @param IWorkflowItemFactory $workflowFactory
     * @param Component|ActiveWorkflowBehavior $model
     *
	 * @return Array
	 */
	public static function getAllStatusListData($workflowId, $workflowFactory, $model)
	{
		$listData = [];
		$statuses = $workflowFactory->getAllStatuses($workflowId, $model);
        /**
         * @var mixed $statusId
         * @var Status $statusInstance
         */
        foreach ($statuses as $statusId => $statusInstance)
        {
			$listData[$statusId] =$statusInstance->getLabel();
		}
		return $listData;
	}
	
	/**
	 * Displays the status for the model passed as argument.
	 * 
	 * This method assumes that the status includes a metadata value called 'labelTemplate' that contains
	 * the HTML template of the rendering status. In this template the string '{label}' will be replaced by the 
	 * status label.
	 * 
	 * Example : 
	 *		'status' => [
	 *			'draft' => [
	 *				'label' => 'Draft',
	 *				'transition' => ['ready' ],
	 *				'metadata' => [
	 *					'labelTemplate' => '<span class="label label-default">{label}</span>'
	 *				]
	 *			],
	 * 
	 * @param ActiveWorkflowBehavior $model
	 * @return string|NULL the HTML rendered status or null if not labelTemplate is found
	 */
	public static function renderLabel($model)
	{
		if($model->hasWorkflowStatus()) {
			$labelTemplate = $model->getWorkflowStatus()->getMetadata('labelTemplate');
			if( !empty($labelTemplate)) {
				return strtr($labelTemplate, ['{label}' => $model->getWorkflowStatus()->getLabel()]);
			}
		}
		return null;
	}
}
