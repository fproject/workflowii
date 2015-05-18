<?php

namespace tests\codeception\unit\models;

use fproject\workflow\base\IWorkflowDefinitionProvider;

class Item06Workflow implements IWorkflowDefinitionProvider
{
	public function getDefinition()
	{
		return [
			'initialStatusId' => 'new',
			'status' => [
				'new' => [
					'label' => 'New Item',
					'transition' => [
						'correction' => [],
						'published' => []
					]
				],
				'correction' => [
					'label' => 'In Correction',
					'transition' => [
						'published' => []
					]
				],
				'published' => [
					'label' => 'Published',
					'transition' => [
						'correction' => [],
						'archive' => []
					]
				],
				'archive' => [
					'label' => 'Archived',
					'transition' => []
				]
			]
		];
	}
}