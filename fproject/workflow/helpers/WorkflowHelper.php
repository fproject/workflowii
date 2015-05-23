<?php
namespace fproject\workflow\helpers;

use fproject\workflow\core\Status;
use fproject\workflow\factory\IWorkflowFactory;
use yii\base\Component;
use fproject\workflow\core\ActiveWorkflowBehavior;
use fproject\workflow\core\WorkflowException;

class WorkflowHelper
{
	/**
	 * Returns an associative array containing all statuses that can be reached by model.
	 * 
	 * Note that the current model status is NOT included in this list.
	 * @param Component $model
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
	 * @param IWorkflowFactory $workflowFactory
	 * @return Array
	 */
	public static function getAllStatusListData($workflowId, $workflowFactory)
	{
		$listData = [];
		$statuses = $workflowFactory->getAllStatuses($workflowId);
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
