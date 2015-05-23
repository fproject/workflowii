<?php

namespace tests\unit\workflow\factory\assoc;

use Yii;
use yii\codeception\TestCase;
use fproject\workflow\factories\assoc\WorkflowArrayFactory;

class LoadWorkflowTest extends TestCase
{
	use \Codeception\Specify;

	public $src;

	protected function setUp()
	{
		parent::setUp();
		$this->src = new WorkflowArrayFactory();
	}


    public function testLoadWorkflowSuccess1()
    {
    	$src = new WorkflowArrayFactory();
    	$src->addWorkflowDefinition('wid', [
			'initialStatusId' => 'A',
			'status' => [
				'A' => [
					'label' => 'Entry',
					'transition' => ['B','A']
				],
				'B' => [
					'label' => 'Published',
					'transition' => ['A','C']
				],
				'C' => [
					'label' => 'node C',
					'transition' => ['A','D']
				],'D'
			]
		]);
    	
    	verify($src->getStatus('wid/A'))->notNull();
    	verify($src->getStatus('wid/B'))->notNull();
    	verify($src->getStatus('wid/C'))->notNull();
    	verify($src->getStatus('wid/D'))->notNull();
    	
    	verify(count($src->getTransitions('wid/A')))->equals(2);
    }
    
    public function testLoadWorkflowSuccess2()
    {
    	$src = new WorkflowArrayFactory();
    	$src->addWorkflowDefinition('wid', [
    		'initialStatusId' => 'A',
    		'status' => [
    			'A' => [
    				'label' => 'Entry',
    				'transition' => 'A,B'
    			],
    			'B' => [
    				'label' => 'Published',
    				'transition' => '  A  , B  '
    			],
    		]
    	]);
    	 
    	verify($src->getStatus('wid/A'))->notNull();
    	verify($src->getStatus('wid/B'))->notNull();
    	 
    	verify(count($src->getTransitions('wid/A')))->equals(2);
    }    
}
