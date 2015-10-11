<?php

namespace tests\unit\workflow\serialize;

use Codeception\Specify;
use fproject\workflow\core\ArrayWorkflowItemFactory;
use Yii;
use yii\codeception\TestCase;

class TransitionTest extends TestCase
{
	use Specify;

    /** @var  ArrayWorkflowItemFactory $src */
	public $src;

	protected function setUp()
	{
		parent::setUp();
		$this->src = new ArrayWorkflowItemFactory();
	}

	/**
	 *
	 */
	public function testTransitionNotFound()
	{
		$this->src->addWorkflowDefinition('wid', [
			'initialStatusId' => 'A',
			'status' => [
				'A' => []
			]
		]);

		$this->specify('empty transition set', function () {
			$tr = $this->src->getTransitions('wid/A', null, null);
			verify('empty transition set is returned', count($tr) )->equals(0);
		});
	}

    public function testTransitionSuccess()
    {
    	$this->src->addWorkflowDefinition('wid', [
			'initialStatusId' => 'A',
    		'status' => [
				'A' => [
					'transition' => ['B' => []]
    			],
    			'B' => [],
				'C' => [
					'transition' => ['B' => ['label'=>'Go to B']]
				],
    		]
    	]);

    	$this->specify('end and start status can be obtained',function() {
			$tr = $this->src->getTransitions('wid/A', null, null);

			verify('empty transition set is returned', count($tr) )->equals(1);

			reset($tr);
			//$startId = key($tr);
			$transition = current($tr);

			verify('transition is a Transition', get_class($transition))->equals('fproject\workflow\core\Transition');

			verify('start status is a Status instance',get_class($transition->getStartStatus()) )->equals('fproject\workflow\core\Status');
			verify('start status is A', $transition->getStartStatus()->getId())->equals('wid/A');

			verify('end status is a Status instance',get_class($transition->getStartStatus()) )->equals('fproject\workflow\core\Status');
			verify('end status is B', $transition->getEndStatus()->getId())->equals('wid/B');
    	});
    }
    public function testTransitionCached()
    {
    	$this->src->addWorkflowDefinition('wid', [
			'initialStatusId' => 'A',
    		'status' => [
				'A' => [
					'transition' => ['B' => []]
    			],
    			'B' => []
    		]
    	]);
    	$tr = $this->src->getTransitions('wid/A', null, null);
    	reset($tr);
    	//$startId = key($tr);
    	$transition1 = current($tr);

    	$tr=null;
    	$tr = $this->src->getTransitions('wid/A', null, null);
    	reset($tr);
    	//$startId = key($tr);
    	$transition2 = current($tr);

    	$this->assertTrue(spl_object_hash($transition1) == spl_object_hash($transition2));
    }
}
