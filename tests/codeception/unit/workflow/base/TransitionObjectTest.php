<?php
namespace tests\unit\workflow\base;

use Yii;
use yii\codeception\TestCase;
use yii\base\InvalidConfigException;
use tests\codeception\unit\models\Item01;
use fproject\workflow\core\Workflow;
use fproject\workflow\core\Status;
use fproject\workflow\core\Transition;
use yii\db\Transaction;

class TransitionObjectTest extends TestCase
{
	use\Codeception\Specify;

	public function testTransitionCreationSuccess()
	{
		$this->specify('create a transition instance with success', function ()
		{
			$start = new Status([
				'id' => 'draft',
				'workflowId' => 'workflow1'
			]);

			$end = new Status([
				'id' => 'published',
				'workflowId' => 'workflow1'
			]);

			$tr = new Transition([
				'start' => $start,
				'end' => $end
			]);

			verify("start status id is 'draft'", $tr->getStartStatus()->getId())->equals('draft');
			verify("end status id is 'published'", $tr->getEndStatus()->getId())->equals('published');
		});
	}

	public function testEmptyStartStatusFails()
	{
		$this->specify('create transition with NULL start status fails', function ()
		{
			$this->setExpectedException(
				'yii\base\InvalidConfigException',
				'missing start status'
			);
			new Transition([
				'start' => null,
				'end'   => new Status([
					'id' => 'published',
					'workflowId' => 'workflow1'
				])
			]);
		});
	}
	public function testMissingStartStatusFails()
	{

		$this->specify('create transition with no start status provided fails ', function ()
		{
			$this->setExpectedException(
				'yii\base\InvalidConfigException',
				'missing start status'
			);
			new Transition([
				'end'   => new Status([
					'id' => 'published',
					'workflowId' => 'workflow1'
				])
			]);
		});
	}
	public function testNotStatusStartStatusFails()
	{

		$this->specify('create transition with start status not Status instance fails ', function ()
		{
			$this->setExpectedException(
				'fproject\workflow\core\WorkflowException',
				'Start status must be an instance of Status'
			);
			new Transition([
				'start'   => new \stdClass()
			]);
		});
	}
	public function testMissingEndStatusFails()
	{
		$this->specify('create transition with no end status provided fails', function ()
		{
			$this->setExpectedException(
				'yii\base\InvalidConfigException',
				'missing end status'
			);
			new Transition([
				'start'   => new Status([
					'id' => 'published',
					'workflowId' => 'workflow1'
				])
			]);
		});
	}

	public function testEmptyEndStatusFails()
	{
		$this->specify('create transition with empty end status fails', function ()
		{
			$this->setExpectedException(
				'yii\base\InvalidConfigException',
				'missing end status'
			);
			new Transition([
				'start'   => new Status([
					'id' => 'published',
					'workflowId' => 'workflow1'
				]),
				'end' => null
			]);
		});
	}
	public function testNotStatusEndStatusFails()
	{

		$this->specify('create transition with end status not Status instance fails ', function ()
		{
			$this->setExpectedException(
				'fproject\workflow\core\WorkflowException',
				'End status must be an instance of Status'
			);
			new Transition([
				'start' => new Status([
					'id' => 'published',
					'workflowId' => 'workflow1'
				]),
				'end'   => new \stdClass()
			]);
		});
	}
}
