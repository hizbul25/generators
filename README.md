# Laravel 5 Generators

L5 includes a bunch of generators out of the box, so this package only needs to add couple of command in a single command, like:

- `generate:all  generate:all --controller=SomeController --model=some --view=some --migration=create_some_table`

*With one or two more to come.*

## Usage

### Step 1: Install Through Composer

```
composer require hizbul/generators
```

### Step 2: Add the Service Provider

You'll only want to use these generators for local development, so you don't want to update the production  `providers` array in `config/app.php`. Instead, add the provider in `app/Providers/AppServiceProvider.php`, like so:

```php
public function register()
{
	if ($this->app->environment() == 'local') {
		$this->app->register('Hizbul\Generators\GenerateAllServiceProvider');
	}
}
```

Happy using :)
