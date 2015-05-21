<?php
namespace fproject\workflow\events;

use fproject\workflow\core\Status;
use fproject\workflow\core\Transition;
use yii\base\Object;

/**
 * The basic event sequence.
 *
 * @see \fproject\workflow\events\IEventSequence
 */
class BasicEventSequence extends Object implements IEventSequence
{
    /**
     * Produces the following sequence when a model enters a workflow :
     *
     * - beforeEnterWorkflow(workflowID)
     * - beforeEnterStatus(statusID)
     *
     * - afterEnterWorkflow(workflowID)
     * - afterEnterStatus(statusID)
     * @param Status $initStatus
     * @param Object $sender
     * @return array|\yii\base\Event[]
     * @see \fproject\workflow\events\IEventSequenceScheme::createEnterWorkflowSequence()
     */
	public function createEnterWorkflowSequence($initStatus, $sender)
	{
		return [
			'before' => [
				new WorkflowEvent(
					WorkflowEvent::beforeEnterWorkflow($initStatus->getWorkflowId()),
					[
						'end'        => $initStatus,
						'sender'  	 => $sender
					]
				),
				new WorkflowEvent(
					WorkflowEvent::beforeEnterStatus($initStatus->getId()),
					[
						'end'        => $initStatus,
						'sender'  	 => $sender
					]
				)
			],
			'after' => [
				new WorkflowEvent(
					WorkflowEvent::afterEnterWorkflow($initStatus->getWorkflowId()),
					[
						'end'        => $initStatus,
						'sender'  	 => $sender
					]
				),
				new WorkflowEvent(
					WorkflowEvent::afterEnterStatus($initStatus->getId()),
					[
						'end'        => $initStatus,
						'sender'  	 => $sender
					]
				)
			]
		];
	}

    /**
     * Produces the following sequence when a model leaves a workflow :
     *
     * - beforeLeaveStatus(statusID)
     * - beforeLeaveWorkflow(workflowID)
     *
     * - afterLeaveStatus(statusID)
     * - afterLeaveWorkflow(workflowID)
     * @param Status $finalStatus
     * @param Object $sender
     *
     * @see \fproject\workflow\events\IEventSequenceScheme::createLeaveWorkflowSequence()
     *
     * @return array|\yii\base\Event[]
     */
	public function createLeaveWorkflowSequence($finalStatus, $sender)
	{
		return [
			'before' => [
				new WorkflowEvent(
					WorkflowEvent::beforeLeaveStatus($finalStatus->getId()),
					[
						'start'      => $finalStatus,
						'sender'  	 => $sender
					]
				),
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
					WorkflowEvent::afterLeaveStatus($finalStatus->getId()),
					[
						'start'      => $finalStatus,
						'sender'  	 => $sender
					]
				),
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
     * - beforeLeaveStatus(A)
     * - beforeChangeStatus(A,B)
     * - beforeEnterStatus(B)
     *
     * - afterLeaveStatus(A)
     * - afterChangeStatus(A,B)
     * - afterEnterStatus(B)
     * @param Transition $transition
     * @param Object $sender
     * @return array|\yii\base\Event[]
     * @see \fproject\workflow\events\IEventSequenceScheme::createChangeStatusSequence()
     */
	public function createChangeStatusSequence($transition, $sender)
	{
		return [
			'before' => [
				new WorkflowEvent(
					WorkflowEvent::beforeLeaveStatus($transition->getStartStatus()->getId()),
					[
						'start'      => $transition->getStartStatus(),
						'sender'  	 => $sender
					]
				),
				new WorkflowEvent(
					WorkflowEvent::beforeChangeStatus($transition->getStartStatus()->getId(), $transition->getEndStatus()->getId()),
					[
						'start'      => $transition->getStartStatus(),
						'end'  		 => $transition->getEndStatus(),
						'transition' => $transition,
						'sender'  	 => $sender
					]
				),
				new WorkflowEvent(
					WorkflowEvent::beforeEnterStatus($transition->getEndStatus()->getId()),
					[
						'end'  		 => $transition->getEndStatus(),
						'sender'  	 => $sender
					]
				)
			],
			'after' => [
				new WorkflowEvent(
					WorkflowEvent::afterLeaveStatus($transition->getStartStatus()->getId()),
					[
						'start'      => $transition->getStartStatus(),
						'sender'  	 => $sender
					]
				),
				new WorkflowEvent(
					WorkflowEvent::afterChangeStatus($transition->getStartStatus()->getId(), $transition->getEndStatus()->getId()),
					[
						'start'  	 => $transition->getStartStatus(),
						'end'  		 => $transition->getEndStatus(),
						'transition' => $transition,
						'sender'     => $sender
					]
				),
				new WorkflowEvent(
					WorkflowEvent::afterEnterStatus($transition->getEndStatus()->getId()),
					[
						'end'  		 => $transition->getEndStatus(),
						'sender'  	 => $sender
					]
				)
			]
		];
	}
}
