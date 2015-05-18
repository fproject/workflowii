<?php
namespace fproject\workflow\events;

use yii\base\Object;
use fproject\workflow\events\IEventSequence;

/**
 * The reduced event sequence.
 *
 * @see \fproject\workflow\events\IEventSequence
 *
 */
class ReducedEventSequence extends Object implements IEventSequence
{
	/**
	 * Produces the following sequence when a model enters a workflow :
	 *
	 * - beforeEnterWorkflow(WID)
	 *
	 * - afterEnterWorkflow(WID)
	 *
	 * @see \fproject\workflow\events\IEventSequenceScheme::createEnterWorkflowSequence()
	 */
	public function createEnterWorkflowSequence($initalStatus, $sender)
	{
		return [
			'before' => [
				new WorkflowEvent(
					WorkflowEvent::beforeEnterWorkflow($initalStatus->getWorkflowId()),
					[
						'end'        => $initalStatus,
						'sender'  	 => $sender
					]
				),
			],
			'after' => [
				new WorkflowEvent(
					WorkflowEvent::afterEnterWorkflow($initalStatus->getWorkflowId()),
					[
						'end'        => $initalStatus,
						'sender'  	 => $sender
					]
				),
			]
		];
	}

	/**
	 * Produces the following sequence when a model leaves a workflow :
	 *
	 * - beforeLeaveWorkflow(WID)
	 *
	 * - afterLeaveWorkflow(WID)
	 *
	 * @see \fproject\workflow\events\IEventSequenceScheme::createLeaveWorkflowSequence()
	 */
	public function createLeaveWorkflowSequence($finalStatus, $sender)
	{
		return [
			'before' => [
				new WorkflowEvent(
					WorkflowEvent::beforeLeaveWorkflow($finalStatus->getWorkflowId()),
					[
						'start'      => $finalStatus,
						'sender'  	 => $sender
					]
				)
			],
			'after' => [
				new WorkflowEvent(
					WorkflowEvent::afterLeaveWorkflow($finalStatus->getWorkflowId()),
					[
						'start'      => $finalStatus,
						'sender'  	 => $sender
					]
				)
			]
		];
	}

	/**
	 * Produces the following sequence when a model changes from status A to status B:
	 *
	 * - beforeChangeStatus(A,B)
	 *
	 * - afterChangeStatus(A,B)
	 *
	 * @see \fproject\workflow\events\IEventSequenceScheme::createChangeStatusSequence()
	 */
	public function createChangeStatusSequence($transition, $sender)
	{
		return [
			'before' => [
				new WorkflowEvent(
					WorkflowEvent::beforeChangeStatus($transition->getStartStatus()->getId(), $transition->getEndStatus()->getId()),
					[
						'start'      => $transition->getStartStatus(),
						'end'  		 => $transition->getEndStatus(),
						'transition' => $transition,
						'sender'  	 => $sender
					]
				)
			],
			'after' => [
				new WorkflowEvent(
					WorkflowEvent::afterChangeStatus($transition->getStartStatus()->getId(), $transition->getEndStatus()->getId()),
					[
						'start'  	 => $transition->getStartStatus(),
						'end'  		 => $transition->getEndStatus(),
						'transition' => $transition,
						'sender'     => $sender
					]
				)
			]
		];
	}
}
