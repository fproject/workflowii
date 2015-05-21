<?php
namespace tests\codeception\unit\models;

use Yii;
use fproject\workflow\core\IWorkflowDefinitionProvider;

class Item08Workflow1 implements IWorkflowDefinitionProvider 
{
    public function getDefinition() {
        return [ 
            'initialStatusId' => 'draft',
            'status' => [
                'draft' => [
                    'transition' => ['correction']
                ],
                'correction' => [
                    'transition' => ['draft','ready']
                ],
                'ready' => [
                    'transition' => ['draft', 'correction', 'published']
                ],
                'published' => [
                    'transition' => ['ready', 'archived']
                ],
                'archived' => [
                    'transition' => ['ready']
                ]
            ]
        ];
    }
}