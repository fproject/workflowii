<?php

namespace tests\codeception\unit\models;

use fproject\workflow\core\IWorkflowSource;

class Item06Workflow implements IWorkflowSource
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