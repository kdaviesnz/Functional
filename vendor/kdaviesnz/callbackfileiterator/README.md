# componentname

Extremely simple class that iterators over files and applies a user-defined callback.

## Install

Via Composer

``` bash
$ composer require kdaviesnz/callbackfileiterator
```

## Usage

``` php

		require_once("src/CallbackFileIterator.php");
		// The callback that is passed to CallbackFileIterator should be a function 
		// that takes the name of the current file as the one and only parameter.
		$callback = function() {
			return function(string $filename) {
				echo $filename . "\n";
			};
		};
		$callbackIterator = new CallbackFileIterator("src", $callback(), true);

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

# CallbackFileIterator
