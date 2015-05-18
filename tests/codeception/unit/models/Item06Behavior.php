<?php
namespace tests\codeception\unit\models;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\base\Event;
use fproject\workflow\events\WorkflowEvent;

class Item06Behavior  extends Behavior
{
	public $corrected = false;
	public $canBeArchived = false;

	public static $maxPostCount = 2;
	public static $countPost = 0;
	public static $countPostToCorrect = 0;
	public static $countPostCorrected = 0;

	public function events()
	{
		return [
			WorkflowEvent::beforeEnterStatus('Item06Workflow/new') => "beforeNew",
			WorkflowEvent::afterEnterStatus('Item06Workflow/new') => "afterNew",
			WorkflowEvent::afterEnterStatus('Item06Workflow/correction') => "postToCorrect",
			WorkflowEvent::beforeLeaveStatus('Item06Workflow/correction') => "postCorrected",
			WorkflowEvent::beforeEnterStatus('Item06Workflow/published') => "checkCanBePublished",
			WorkflowEvent::beforeChangeStatus('Item06Workflow/published', 'Item06Workflow/archive') => "canBeArchived",
		];
	}
	public function beforeNew($event)
	{
		if(self::$countPost >= self::$maxPostCount) {
			$event->isValid = false;
		}
	}
	public function afterNew($event)
	{
		self::$countPost++;
	}
	public function postToCorrect($event)
	{
		self::$countPostToCorrect++;
	}
	public function postCorrected($event)
	{
		if( ! $this->corrected) {
			$event->isValid = false;
		} else {
			$this->corrected = true;
			self::$countPostToCorrect--;
			self::$countPostCorrected++;
		}
	}
	public function checkCanBePublished($event)
	{
		if( ! $this->corrected) {
			$event->isValid = false;
		}
	}
	public function canBeArchived($event)
	{
		$event->isValid = ( $this->canBeArchived == true );
	}


	//////////////////////////////////////////////////////////////////

	public function markAsCorrected()
	{
		$this->corrected = true;
	}
	public function markAsCandidateForArchive()
	{
		$this->canBeArchived = true;
	}
}