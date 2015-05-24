<?php

namespace tests\unit\workflow\serialize;

use Codeception\Specify;
use fproject\workflow\core\ArrayWorkflowItemFactory;
use fproject\workflow\serialize\ArrayDeserializer;
use Yii;
use yii\codeception\TestCase;

/**
 * @property ArrayDeserializer deserializer
 * @property ArrayDeserializer deserializerA
 * @property ArrayDeserializer deserializerB
 */
class ArrayDeserializerTest extends TestCase
{
    use Specify;

	/** @var  ArrayWorkflowItemFactory $src */
	public $src;
	
	protected function setUp()
	{
		parent::setUp();
		$this->src = new ArrayWorkflowItemFactory();
		Yii::$app->set('deserializer',[
			'class' => ArrayDeserializer::className(),
		]);		
	}

    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        else
        {
            return parent::__get($name);
        }
    }

    public function getDeserializer()
    {
        return Yii::$app->deserializer;
    }

    public function getDeserializerB()
    {
        return Yii::$app->deserializerB;
    }

    public function getDeserializerA()
    {
        return Yii::$app->deserializerA;
    }

	/**
	 * 
	 */
	public function testCreateInstance()
	{
		Yii::$app->set('deserializerA',[
			'class' => ArrayDeserializer::className(),
			'validate' => false
		]);
		verify('validate is assigned',$this->deserializerA->validate)->false();

		Yii::$app->set('deserializerB',[
			'class' => ArrayDeserializer::className(),
		]);
		verify('validate default value is true',$this->deserializerB->validate)->true();
	}
	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp #Missing "initialStatusId"#
	 */
	public function testParseNoInitStatus()
	{
		$this->deserializer->deserialize('WID',[
			'status'=> []
		],$this->src);
	}
	/**
	 * @expectedException fproject\workflow\core\WorkflowException
	 * @expectedExceptionMessageRegExp #Not a valid status id : incorrect status local id format in 'hello A'#
	 */
	public function testParseInvalidInitStatusID()
	{
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'hello A'
		],$this->src);
	}	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp #No status definition found#
	 */
	public function testParseNoStatus()
	{
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A'
		],$this->src);
	}	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp #Wrong definition for status A : array expected#
	 */
	public function testParseWrongStatusDefinition1()
	{
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => [ 'A' => 1]
				
		],$this->src);
	}	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp /Wrong status definition : key = 1 value =[.*0 => 'A'.*]/
	 */
	public function testParseWrongStatusDefinition2()
	{
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => [ 1 => ['A']]
		],$this->src);
	}	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp #Invalid Status definition : array expected#
	 */
	public function testParseWrongStatusDefinition3()
	{
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => 'A'
		],$this->src);
	}		
	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp #Invalid metadata definition for status WID/A : array expected#
	 */
	public function testParseWrongMetadataDefinition1()
	{
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => [ 
			'A' => [
				'metadata' => 1
			]
		]
		],$this->src);
	}	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp #Invalid metadata definition for status WID/A : associative array expected#
	 */
	public function testParseWrongMetadataDefinition2()
	{
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
				'status' => [
				'A' => [
					'metadata' => ['A','B']
				]
			]
		],$this->src);
	}		
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp #Wrong definition for between WID/A and B : array expected#
	 */
	public function testParseWrongTransitionDefinition1()
	{
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
				'status' => [
				'A' => [
					'transition' => ['B' => 1]
				]
			]
		],$this->src);
	}	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * 
	 * expectedExceptionMessageRegExp /Wrong transition definition for status WID.A : key = 1 value = [.*0 => 'B'.*]/
	 */
	public function testParseWrongTransitionDefinition2()
	{
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
				'status' => [
				'A' => [
					'transition' => [1 => ['B']]
				]
			]
		],$this->src);
	}	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp #Invalid transition definition format for status WID/A : string or array expected#
	 */
	public function testParseWrongTransitionDefinition3()
	{
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
				'status' => [
				'A' => [
					'transition' => 1
				]
			]
		],$this->src);
	}		
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp #Initial status not defined : WID/C#
	 */
	public function testParseValidationFailedMissingInitStatus()
	{
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'C',
				'status' => [
				'A' => [
					'transition' => 'B'
				],
				'B'
			]
		],$this->src);
	}	
	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp #Initial status must belong to workflow : EXT/C#
	 */
	public function testParseValidationFailedExternalInitStatus()
	{	
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'EXT/C',
				'status' => [
				'A' => [
					'transition' => 'B'
				],
				'B'
			]
		],$this->src);
	}		
	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp #Status must belong to workflow : EXT/B#
	 */
	public function testParseValidationFailedExternalStatus1()
	{	
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
				'status' => [
				'A' => [
					'transition' => 'B'
				],
				'EXT/B'
			]
		],$this->src);
	}		
	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp #Status must belong to workflow : EXT/A#
	 */
	public function testParseValidationFailedExternalStatus2()
	{	
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
				'status' => [
				'EXT/A' => [
					'transition' => 'B'
				],
				'B'
			]
		],$this->src);
	}
	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessageRegExp /One or more end status are not defined :.*?/
	 */
	public function testParseValidationFailedMissingStatus()
	{
		$this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
				'status' => [
				'A' => [
					'transition' => 'B'
				]
			]
		],$this->src);
	}	

	public function testParseMinimalWorkflow1()
	{
		$workflow = $this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => ['A']
		],$this->src);
		verify('initial status is WID/A',$workflow['initialStatusId'])->equals('WID/A');
		verify('status WID/A is present',\array_key_exists('WID/A', $workflow['status']))->true();
		verify('status WID/A definition is NULL',$workflow['status']['WID/A'])->isEmpty();
	}
	
	public function testParseMinimalWorkflow2()
	{
		$workflow = $this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => ['A'=> null]
		],$this->src);
		verify('initial status is WID/A',$workflow['initialStatusId'])->equals('WID/A');
		verify('status WID/A is present',\array_key_exists('WID/A', $workflow['status']))->true();
		verify('status WID/A definition is NULL',$workflow['status']['WID/A'])->isEmpty();
	}	
	
	public function testParseMinimalWorkflow3()
	{
		$workflow = $this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => ['A'=> []]
		],$this->src);
		verify('initial status is WID/A',$workflow['initialStatusId'])->equals('WID/A');
		verify('status WID/A is present',\array_key_exists('WID/A', $workflow['status']))->true();
		verify('status WID/A definition is NULL',$workflow['status']['WID/A'])->isEmpty();
	}	
	
	public function testParseMinimalWorkflowWithConfig()
	{
		$workflow = $this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => ['A'],
			'property' => 'value'
		],$this->src);
		
		verify('status WID/A definition is NULL',$workflow['property'])->equals('value');
	}		
	
	public function testParseStatusWithConfig()
	{
		$workflow = $this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => [
				'A' => ['property' => 'value']
			]
		],$this->src);
		
		verify('status WID/A definition is NULL',$workflow['status']['WID/A']['property'])->equals('value');
	}	
	
	
	public function testParseMetadata()
	{
		$workflow = $this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => [
				'A'=> [
					'metadata' => ['color' => 'red']
				]
			]
		],$this->src);
		verify('metadata is set',$workflow['status']['WID/A']['metadata']['color'])->equals('red');
	}		
	
	public function testParseTransitionSingle1()
	{
		$workflow = $this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => [
				'A'=> [
					'transition' => 'B'
				],
				'B'
			]
		],$this->src);
		verify('transition is set',\array_key_exists('WID/B',$workflow['status']['WID/A']['transition']))->true();
		verify('transition has no config set',$workflow['status']['WID/A']['transition']['WID/B'] === [])->true();
	}	
	
	public function testParseTransitionSingle2()
	{
		$workflow = $this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => [
				'A'=> [
					'transition' => ['B']
				],
				'B'
			]
		],$this->src);
		verify('transition is set',\array_key_exists('WID/B',$workflow['status']['WID/A']['transition']))->true();
		verify('transition has no config set',$workflow['status']['WID/A']['transition']['WID/B'] === [])->true();
	}
	
	public function testParseTransitionSingle3()
	{
		$workflow = $this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => [
				'A'=> [
					'transition' => ['B' => []]
				],
				'B'
			]
		],$this->src);
		verify('transition is set',\array_key_exists('WID/B',$workflow['status']['WID/A']['transition']))->true();
		verify('transition has no config set',$workflow['status']['WID/A']['transition']['WID/B'] === [])->true();
	}	

	public function testParseTransitionMulti1()
	{
		$workflow = $this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => [
				'A'=> [
					'transition' => 'B,C'
				],
				'B','C'
			]
		],$this->src);
		verify('transition to B is set',\array_key_exists('WID/B',$workflow['status']['WID/A']['transition']))->true();
		verify('transition to C is set',\array_key_exists('WID/C',$workflow['status']['WID/A']['transition']))->true();
		
		verify('transition to B has no config set',$workflow['status']['WID/A']['transition']['WID/B'] === [])->true();
		verify('transition to C has no config set',$workflow['status']['WID/A']['transition']['WID/C'] === [])->true();
	}		
	
	public function testParseTransitionMulti2()
	{
		$workflow = $this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => [
				'A'=> [
					'transition' => ['B','C']
				],
				'B','C'
			]
		],$this->src);
		verify('transition to B is set',\array_key_exists('WID/B',$workflow['status']['WID/A']['transition']))->true();
		verify('transition to C is set',\array_key_exists('WID/C',$workflow['status']['WID/A']['transition']))->true();
		
		verify('transition to B has no config set',$workflow['status']['WID/A']['transition']['WID/B'] === [])->true();
		verify('transition to C has no config set',$workflow['status']['WID/A']['transition']['WID/C'] === [])->true();
	}		
	
	public function testParseTransitionMultiWidhtConfig()
	{
		$workflow = $this->deserializer->deserialize('WID',[
			'initialStatusId' => 'A',
			'status' => [
				'A'=> [
					'transition' => ['B' => ['kb' => 'vb'] ,'C' => []]
				],
				'B','C'
			]
		],$this->src);
		verify('transition to B is set',\array_key_exists('WID/B',$workflow['status']['WID/A']['transition']))->true();
		verify('transition to C is set',\array_key_exists('WID/C',$workflow['status']['WID/A']['transition']))->true();
		
		verify('transition to B has no config set',$workflow['status']['WID/A']['transition']['WID/B'] === ['kb' => 'vb'])->true();
		verify('transition to C has no config set',$workflow['status']['WID/A']['transition']['WID/C'] === [])->true();
	}

    /** Test case for https://github.com/fproject/workflowii/issues/2 */
    public function testParseBugNo2()
    {
        $workflow = $this->deserializer->deserialize('WID',[
            'initialStatusId' => 'draft',
            'status' => [
                'draft' => [
                    'transition' => ['open', 'deleted']
                ],
                'open' => [
                    'transition' => ['in-progress', 'resolved', 'closed', 'cancelled']
                ],
                'in-progress' => [
                    'transition' => ['open', 'resolved', 'closed', 'cancelled']
                ],
                'resolved' => [
                    'transition' => ['open', 'in-progress', 'closed']
                ],
                'closed' => [
                    'transition' => ['open', 'in-progress']
                ],
                'cancelled' => ['open', 'in-progress', 'resolved', 'closed'],
                'deleted'
            ]
        ],$this->src);
        verify('initial status is WID/draft',$workflow['initialStatusId'])->equals('WID/draft');
    }
}
