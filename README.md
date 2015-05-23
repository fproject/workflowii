# Workflow Engine for Yii 2 Framework
[![Latest Stable Version](https://poser.pugx.org/fproject/workflowii/v/stable)](https://packagist.org/packages/fproject/workflowii)
[![Total Downloads](https://poser.pugx.org/fproject/workflowii/downloads)](https://packagist.org/packages/fproject/workflowii)
[![Latest Unstable Version](https://poser.pugx.org/fproject/workflowii/v/unstable)](https://packagist.org/packages/fproject/workflowii)
[![Build](https://travis-ci.org/fproject/workflowii.svg?branch=master)](https://travis-ci.org/fproject/workflowii)
[![License](https://poser.pugx.org/fproject/workflowii/license)](https://packagist.org/packages/fproject/workflowii)

## INSTALLATION

The preferred way to install **Workflowii** is through [composer](http://getcomposer.org/download/).

You can either run:
```
php composer.phar require fproject/workflowii "*"
```

or add this block to the *require* section of your `composer.json` file:
```javascript
"require" : {
		"php" : ">=5.4.0",
		"yiisoft/yii2" : "*",
		"fproject/workflowii": "*",
		// ...
	}
```

## REQUIREMENTS

The minimum requirement by Workflowii:
- Your Web server supports PHP 5.4 or above
- Your Web server is running on Yii 2.0.0 or above

## Quick Start 

### Configuration

For this "*Quick start Guide*" we will be using default configuration settings, but note that *workflowii* is designed to be highly
flexible so to adapt to a lot of execution contexts..

### Create A Workflow
 
A workflow is defined as a PHP class that implements the `\fproject\workflow\core\IWorkflowSource` interface. This interface
declares the *getDefinition()* method that must return an array representing the workflow. 

Let's define a very simple workflow that will be used to manage article posts.

Here is the PHP class that implements the definition for our workflow :

*ArticleWorkflow.php in @app/models*
```php
<?php
namespace app\models;

class ArticleWorkflow implements \fproject\workflow\core\IWorkflowSource 
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

### Attach To The Model

Now let's have a look to our Artical model. We decide to store the status of a article in a column named `status` of type STRING(40). 

The last step is to associate the workflow definition with articles models. To do so we must declare the *ActiveWorkflowBehavior* behavior 
in the Article model class and let the default configuration settings do the rest.
 
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
			\fproject\workflow\core\ActiveWorkflowBehavior::className()
    	];
    }
    // ...
```

That's it ! We are ready to play with *ActiveWorkflowBehavior*.

### Use It !

Now that we are all setup, we can use the *ActiveWorkflowBehavior* methods to set/get the status of our articles : the *ActiveWorkflowBehavior* will 
take care that the article doesn't reach a status where it is not supposed to go, depending on the workflow definition that we have created.

```php
$article = new Article();
$article->status = 'draft';
$article->save();
echo 'article status is : '. $article->workflowStatus->label;
```
This will print the following message :

	article status is : Draft
	 
If you do the same thing but instead of *draft* set the status to *published* and try to save it, the following exception is thrown :

	Not an initial status : ArticleWorkflow/published ("ArticleWorkflow/draft" expected)

That's because in your workflow definition the **initial status** is  set to *draft* and not *published*.

Ok, one more example for the fun ! This time we are not going to perform the transition when the Article is saved (like we did in the previous
example), but immediately by invoking the `sendToStatus` method. Our Article is going to try to reach status *published* passing through *deleted* 
which is strictly forbidden by the workflow. Will it be successful in this risky attempt of breaking workflow rules ?   

```php
$article = new Article();
$article->sendToStatus('draft');
$article->sendToStatus('deleted');
$article->sendToStatus('published');	// danger zone !
```

There is no transition between *deleted* and *published*, and that's what *Workflow* tries to explain to our
fearless Article object:

	Workflow Exception – fproject\workflow\core\WorkflowException
	No transition found between status ArticleWorkflow/deleted and ArticleWorkflow/published
	
Yes, that's severe, but there was many ways to avoid this exception like for instance by first validating that the transition was possible. 

### What's Next ?

This is just one way of using the *ActiveWorkflowBehavior* but there's much more and hopefully enough to assist you
in your workflow management inside your Yii2 web app.

In the meantime you can have a look to the [Usage Guide](guide) (still under dev) and send any feedback. 

##ROADMAP

- At the first stage, we build a workflow engine based on Yii 2 Framework with basic functionalities.
- At the second stage, we are planning to develop a web component that allows users display/edit workflows by
interacting with a RIA GUI, using HTML5 or Flex.

##LICENSE


**workflowii** is released under the Apache 2.0 License. See the bundled `LICENSE.md` for details.

##LINKS

- [GitHub](https://github.com/fproject/workflowii)
- [Packagist](https://packagist.org/packages/fproject/workflowii)
