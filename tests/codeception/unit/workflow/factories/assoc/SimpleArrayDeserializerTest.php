<?php

namespace tests\unit\workflow\factories\assoc;

use fproject\workflow\core\ArrayWorkflowItemFactory;
use fproject\workflow\serialize\SimpleArrayDeserializer;
use Yii;
use yii\codeception\TestCase;

class SimpleArrayDeserializerTest extends TestCase
{
	use \Codeception\Specify;
	
	public $src;
	
	protected function setUp()
	{
		parent::setUp();
		Yii::$app->set('deserializer',[
			'class' => SimpleArrayDeserializer::className(),
		]);
		
		$this->src = new ArrayWorkflowItemFactory([
			'deserializer' => 'deserializer'
		]);
	}
	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessage Workflow definition must be provided as an array
	 */
	public function testParseInvalidType()
	{
		Yii::$app->deserializer->parse('WID',null,$this->src);
	}
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessage Missing argument : workflow Id
	 */
	public function testMissingWorkflowId()
	{
		Yii::$app->deserializer->parse('',null,$this->src);
	}	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessage Workflow definition must be provided as associative array
	 */
	public function testNonAssociativeArray1()
	{
		Yii::$app->deserializer->parse('WID',['a'],$this->src);
	}	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessage Workflow definition must be provided as associative array
	 */
	public function testNonAssociativeArray2()
	{
		Yii::$app->deserializer->parse('WID',['a'=> [], 'b'],$this->src);
	}	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessage Status must belong to workflow : EXT/a
	 */
	public function testExternalStatusError()
	{
		Yii::$app->deserializer->parse('WID',[
			'EXT/a' => [],
			'b' => []
		],$this->src);
	}
	
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessage  Associative array not supported (status : WID/a)
	 */
	public function testEndStatusAssociativeError()
	{
		Yii::$app->deserializer->parse('WID',[
			'a' => ['b' => 'value'],
			'b' => []
		],$this->src);
	}
	/**
	 * @expectedException fproject\workflow\core\WorkflowValidationException
	 * @expectedExceptionMessage End status list must be an array for status  : WID/a
	 */
	public function testEndStatusTypeNotSupported()
	{
		Yii::$app->deserializer->parse('WID',[
			'a' => 4,
			'b' => []
		],$this->src);
	}		
	
	public function testParseArraySuccess()
	{
		$workflow = Yii::$app->deserializer->parse('WID',[
			'a' => ['b','c'],
			'b' => ['a'],
			'c' => []
		],$this->src);
				
		verify('status "a" is set ', array_key_exists('WID/a',($workflow['status'])) )->true();
		verify('status "b" is set ', array_key_exists('WID/b',($workflow['status'])) )->true();
		verify('status "c" is set ', array_key_exists('WID/c',($workflow['status'])) )->true();
		
		verify('status transitions from "a" are set ', $workflow['status']['WID/a']['transition'])->equals(['WID/b'=>[],'WID/c'=>[]]);
		verify('status transitions from "b" are set ', $workflow['status']['WID/b']['transition'])->equals(['WID/a'=>[]]);
		verify('status transitions from "a" are set ', $workflow['status']['WID/c'])->equals(null);
	}		
	
	public function testParseStringSuccess()
	{
		$workflow = Yii::$app->deserializer->parse('WID',[
			'a' => 'b,c',
			'b' => 'a',
			'c' => []
		],$this->src);
				
		verify('status "a" is set ', array_key_exists('WID/a',($workflow['status'])) )->true();
		verify('status "b" is set ', array_key_exists('WID/b',($workflow['status'])) )->true();
		verify('status "c" is set ', array_key_exists('WID/c',($workflow['status'])) )->true();
		
		verify('status transitions from "a" are set ', $workflow['status']['WID/a']['transition'])->equals(['WID/b'=>[],'WID/c'=>[]]);
		verify('status transitions from "b" are set ', $workflow['status']['WID/b']['transition'])->equals(['WID/a'=>[]]);
		verify('status transitions from "a" are set ', $workflow['status']['WID/c'])->equals(null);
	}
}
