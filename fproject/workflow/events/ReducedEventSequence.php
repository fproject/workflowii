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

namespace fproject\workflow\events;

use fproject\workflow\core\Status;
use fproject\workflow\core\Transition;
use yii\base\Object;

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
     * @param Status $initStatus
     * @param Object $sender
     *
     * @see \fproject\workflow\events\IEventSequenceScheme::createEnterWorkflowSequence()
     * @return array|\yii\base\Event[]
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
			],
			'after' => [
				new WorkflowEvent(
					WorkflowEvent::afterEnterWorkflow($initStatus->getWorkflowId()),
					[
						'end'        => $initStatus,
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
     * @param Status $finalStatus
     * @param Object $sender
     * @return array|\yii\base\Event[]
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
     * @param Transition $transition
     * @param Object $sender
     *
     * @return array|\yii\base\Event[]
     *
     * @see \fproject\workflow\events\IEventSequenceScheme::createChangeStatusSequence()
     *
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
