<?php
namespace fproject\workflow\events;
use yii\base\Event;
use fproject\workflow\base\Status;
use fproject\workflow\base\Transition;

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
 * @see \fproject\workflow\events\WorkflowEvent
 *
 */
interface IEventSequence
{
	/**
	 * Creates and returns the sequence of events that occurs when a model enters into a workflow.
	 *
	 * @param Status $initStatus the status used to enter into the workflow (the <i>initial status</i>)
	 * @param Object $sender
	 * @return Event[]
	 */
	public function createEnterWorkflowSequence($initStatus, $sender);
	/**
	 * Creates and returns the sequence of events that occurs when a model leaves a workflow.
	 *
	 * @param \fproject\workflow\base\Status $finalStatus the status that the model last visited in the workflow it is leaving
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
