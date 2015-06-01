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
use fproject\workflow\core\IStatus;
use yii\base\Event;
use fproject\workflow\core\Status;
use fproject\workflow\core\Transition;

/**
 *
 * Defines the interface that must be implemented by all Event sequence.
 * An <b>event sequence</b> is an array of workflow events that occur on three circumstances
 * for which a method is called :
 * <ul>
 *	<li><b>createEnterWorkflowSequence</b> : when a model enters into a workflow</li>
 *	<li><b>createChangeStatusSequence</b> : when a model status change from a non empty status to another one</li>
 *	<li><b>createLeaveWorkflowSequence</b> : when a model leaves a workflow</li>
 *</ul>
 *
 * For each one of these method, the implementation must returns an array of Workflow events that extend
 * \fproject\workflow\events\WorkflowEvent.
 *
 * Two event sequence implementations are provided : {@link \fproject\workflow\events\BasicEventSequence} and
 * {@link \fproject\workflow\events\ExtendedEventSequence}
 *
 * @see WorkflowEvent
 *
 */
interface IEventSequence
{
	/**
	 * Creates and returns the sequence of events that occurs when a model enters into a workflow.
	 *
	 * @param IStatus $initStatus the status used to enter into the workflow (the <i>initial status</i>)
	 * @param Object $sender
	 * @return Event[]
	 */
	public function createEnterWorkflowSequence($initStatus, $sender);
	/**
	 * Creates and returns the sequence of events that occurs when a model leaves a workflow.
	 *
	 * @param IStatus $finalStatus the status that the model last visited in the workflow it is leaving
	 * (the <i>final status</i>)
	 * @param Object $sender
	 * @return Event[]
	 */
	public function createLeaveWorkflowSequence($finalStatus, $sender);
	/**
	 * Creates and returns the sequence of events that occurs when a model changes
	 * from an existing status to another existing status.
	 *
	 * @param Transition $transition the transition representing the status
	 * change
	 * @param Object $sender
	 * @return Event[]
	 */
	public function createChangeStatusSequence($transition, $sender);
}
