# Workflow Engine for Yii 2 Framework
[![Latest Stable Version](https://poser.pugx.org/fproject/workflowii/v/stable)](https://packagist.org/packages/fproject/workflowii)
[![Total Downloads](https://poser.pugx.org/fproject/workflowii/downloads)](https://packagist.org/packages/fproject/workflowii)
[![Latest Unstable Version](https://poser.pugx.org/fproject/workflowii/v/unstable)](https://packagist.org/packages/fproject/workflowii)
[![Build](https://travis-ci.org/fproject/workflowii.svg?branch=master)](https://travis-ci.org/fproject/workflowii)
[![License](https://poser.pugx.org/fproject/workflowii/license)](https://packagist.org/packages/fproject/workflowii)

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

You can either run

```
php composer.phar require --prefer-dist fproject/workflowii "*"
```

or add this line to the require section of your `composer.json` file.
```
"fproject/workflowii": "*"
```

# Quick Start 

## Configuration

For this "*Quick start Guide*" we will be using default configuration settings, but note that *workflowii* is designed to be highly
flexible so to adapt to a lot of execution contexts... well at least that was my goal.

## Create A Workflow
 
A workflow is defined as a PHP class that implements the `\fproject\workflow\base\IWorkflowDefinitionProvider` interface. This interface
declares the *getDefinition()* method that must return an array representing the workflow. 

Let's define a very *simple workflow* that will be used to manage posts in a basic blog system.

<img src="guide/images/workflow1.png"/>

Here is the PHP class that implements the definition for our workflow :

*ArticleWorkflow.php in @app/models*
```php
<?php
namespace app\models;

class ArticleWorkflow implements \fproject\workflow\base\IWorkflowDefinitionProvider 
{
	public function getDefinition() {
		return [
			'initialStatusId' => 'draft',
			'status' => [
				'draft' => [
					'transition' => ['published','deleted']
				],
				'published' => [
					'transition' => ['draft','deleted']
				],
				'deleted' => [
					'transition' => ['draft']
				]
			]
		];
	}
}
```

## Attach To The Model

Now let's have a look to our Post model. We decide to store the status of a post in a column named `status` of type STRING(40). 

The last step is to associate the workflow definition with posts models. To do so we must declare the *WorkflowBehavior* behavior 
in the Post model class and let the default configuration settings do the rest.
 
```php
<?php

namespace app\models;
/**
 * @property integer $id
 * @property string $title
 * @property string $body
 * @property string $status column used to store the status of the article
 */
class Article extends \yii\db\ActiveRecord
{
    public function behaviors()
    {
    	return [
			\fproject\workflow\base\WorkflowBehavior::className()
    	];
    }
    // ...
```

That's it ! We are ready to play with *WorkflowBehavior*.

## Use It !

Now that we are all setup, we can use the *WorkflowBehavior* methods to set/get the status of our posts : the *WorkflowBehavior* will 
take care that the post doesn't reach a status where it is not supposed to go, depending on the workflow definition that we have created.

```php
$article = new Post();
$article->status = 'draft';
$article->save();
echo 'post status is : '. $article->workflowStatus->label;
```
This will print the following message :

	post status is : Draft
	 
If you do the same thing but instead of *draft* set the status to *published* and try to save it, the following exception is thrown :

	Not an initial status : ArticleWorkflow/published ("ArticleWorkflow/draft" expected)

That's because in your workflow definition the **initial status** is  set to *draft* and not *published*.

Ok, one more example for the fun ! This time we are not going to perform the transition when the Post is saved (like we did in the previous
example), but immediately by invoking the `sendToStatus` method. Our Post is going to try to reach status *published* passing through *deleted* 
which is strictly forbidden by the workflow. Will it be successful in this risky attempt of breaking workflow rules ?   

```php
$article = new Post();
$article->sendToStatus('draft');
$article->sendToStatus('deleted');
$article->sendToStatus('published');	// danger zone !
```

Game Over ! There is no transition between *deleted* and *published*, and that's what *SimpleWorkflow* tries to explain to our
fearless post object.

	Workflow Exception – fproject\workflow\base\WorkflowException
	No transition found between status ArticleWorkflow/deleted and ArticleWorkflow/published
	
Yes, that's severe, but there was many ways to avoid this exception like for instance by first validating that the transition was possible. 

## What's Next ?

This is just one way of using the *WorkflowBehavior* but there's much more and hopefully enough to assist you
in your workflow management inside your Yii2 web app.

In the meantime you can have a look to the [Usage Guide](guide) (still under dev) and send any feedback. 

#Roadmap

- At the first stage, we build a workflow engine based on Yii 2 Framework with basic functionalities.
- At the second stage, we are planning to develop a web component that allows users display/edit workflows by
interacting with a RIA GUI, using HTML5 or Flex.

#License


**workflowii** is released under the Apache 2.0 License. See the bundled `LICENSE.md` for details.

#Links


- [GitHub](https://github.com/fproject/workflowii)
- [Packagist](https://packagist.org/packages/fproject/workflowii)
