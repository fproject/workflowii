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

## QUICK START

### Configuration

In this simple start guide we use default configuration settings, but note that *workflowii* is designed to be highly
flexible so to adapt to a lot of execution contexts.

### Create A WorkflowSource
 
A workflow source is defined as a PHP class that implements the `\fproject\workflow\core\IWorkflowSource` interface. This interface
declares the *getDefinition()* method that must return an array representing the workflow of a model object. 

Let's define a very simple workflow source that will be used to manage article posts represented by `Article` model class.
This workflow source will simply return the same workflow for all model object instances of `Article`, by specifying a PHP 
associative array as return value of `getDefinition()` method:

*ArticleWorkflowSource.php*
```php
class ArticleWorkflowSource implements \fproject\workflow\core\IWorkflowSource 
{
	public function getDefinition($model) {
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

### Attach ActiveWorkflowBehavior To The Model

Now let's have a look to our `Article` model that extends from \yii\db\ActiveRecord and has a column named
`status` of type `string`. 

The last step is to associate the workflow definition with articles models. All things we have to do is overriding
the `function behaviors()` method in the `Article` model class and specify the *ActiveWorkflowBehavior* behavior as
return value. That's all, the *workflowii*'s default configuration settings will do the rest for you.

*Article.php*
```php
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

By default, the `ActiveWorkflowBehavior` will search to find a workflow source class that has the same namespace with the
model class and has the class name is the model class name with suffix `WorkflowSource`. In this case, it will be `ArticleWorkflowSource`.

Thus, we are now ready to play with `ArticleWorkflowSource` workflow that dedicated for `Article` model.

### Let's Use It !

Now that we are all setup, we can use the *ActiveWorkflowBehavior* methods to set/get the status of our articles : the *ActiveWorkflowBehavior* will 
take care that the article doesn't reach a status where it is not supposed to go, depending on the workflow definition that we have created.

```php
$article = new Article();
$article->status = 'draft';
$article->save();
echo 'article status now is : '. $article->workflowStatus->label;
```
This will print the following message :

	article status is : Draft
	 
If you do the same thing but instead of *draft* set the status to *published* and try to save it, the following exception is thrown :

	Not an initial status : ArticleWorkflow/published ("ArticleWorkflow/draft" expected)

That's because in your workflow definition the **initial status** is  set to *draft* and not *published*.

Now we will go further. This time we are not going to perform the transition when the Article is saved (like we did in the previous
example), but immediately by invoking the `sendToStatus` method. Our Article is going to try to reach status *published* passing through *deleted* 
which is strictly forbidden by the workflow. Will it be successful in this risky attempt of breaking workflow rules ?   

```php
$article = new Article();
$article->sendToStatus('draft'); // OK
$article->sendToStatus('deleted'); // OK
$article->sendToStatus('published'); // Error!
```

There is no transition between *deleted* and *published*, and that's what *Workflow* tries to explain the problem:

	Workflow Exception – fproject\workflow\core\WorkflowException
	No transition found between status ArticleWorkflow/deleted and ArticleWorkflow/published
	
Yes, that's severe, but there was many ways to avoid this exception like for instance by first validating that the transition was possible. 

### What's Next ?

This is just one way of using the *ActiveWorkflowBehavior* but there's much more and hopefully enough to assist you
in your workflow management inside your Yii2 web app.

In the meantime you can have a look to the [Workflowii User Guide](guide) (still under dev) and send any feedback. 

##ROADMAP

- At the first stage, we build a workflow engine based on Yii 2 Framework with basic functionalities.
- At the second stage, we are planning to develop a web component that allows users display/edit workflows by
interacting with a RIA GUI, using HTML5 or Flex.

##LICENSE


**Workflowii** is released under the Apache 2.0 License. See the bundled `LICENSE.md` for details.

##LINKS

- [GitHub](https://github.com/fproject/workflowii)
- [Packagist](https://packagist.org/packages/fproject/workflowii)
