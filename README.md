# functional

Check your code for compliance with Functional programming principles.

## Install

Via Composer

``` bash
$ composer require kdaviesnz/functional
```

## Usage

``` php

		require_once("vendor/autoload.php");
		require_once("src/FunctionalController.php");
        require_once("src/FunctionalModel.php");
        require_once("src/FunctionalView.php");

        $model = new \kdaviesnz\functional\FunctionalModel();
        $controller = new \kdaviesnz\functional\FunctionalController($model);

        $controller->setSourceDir("src");

        $view = new \kdaviesnz\functional\FunctionalView($controller, $model);
        echo $view->outputFunctionsWithMutatedVariables();
        echo $view->outputFunctionsWithLoops();
        echo $view->outputSimilarFunctions();

```

## Change log

Please see CHANGELOG.md for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see CONTRIBUTING.md and CODE_OF_CONDUCT.md for details.

## Security

If you discover any security related issues, please email kdaviesnz@gmail.com instead of using the issue tracker.

## Credits

- kdaviesnz@gmail.com

## License

The MIT License (MIT). Please see LICENSE.md for more information.

# Functional
