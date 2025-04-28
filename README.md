# :package_description

[![Latest Version on Packagist](https://img.shields.io/packagist/v/Albaroody/staging.svg?style=flat-square)](https://packagist.org/packages/Albaroody/staging)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/Albaroody/staging/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/Albaroody/staging/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/Albaroody/staging/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/Albaroody/staging/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/Albaroody/staging.svg?style=flat-square)](https://packagist.org/packages/Albaroody/staging)
<!--delete-->

# Laravel Staging

[![Latest Version on Packagist](https://img.shields.io/packagist/v/albaroody/staging.svg?style=flat-square)](https://packagist.org/packages/albaroody/staging)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/albaroody/staging/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/albaroody/staging/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/albaroody/staging/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/albaroody/staging/actions?query=workflow%3AFix+PHP+code+style+issues+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/albaroody/staging.svg?style=flat-square)](https://packagist.org/packages/albaroody/staging)

---

Laravel Staging allows you to **stage (draft)** Eloquent models and their **nested relationships** into a clean, separate system before committing them permanently to your main database.

- Stage parent models like `Patient`, `Post`, `Order`, etc.
- Stage related models like `Sales`, `Items`, `Comments`, etc.
- Hydrate full Eloquent models from staged data (not just arrays)
- Promote staged data to production tables cleanly
- Keep your main database structure untouched — no intrusive columns added!

Perfect for multi-step forms, draft publishing systems, and modular deferred saving workflows.

---

## Installation

You can install the package via Composer:

```bash
composer require albaroody/staging

You can publish and run the staging migration with:

```bash
php artisan vendor:publish --tag="staging-migrations"
php artisan migrate

You can publish the config file with:
```bash
php artisan vendor:publish --tag="staging-config"

Usage


## Installation

You can install the package via composer:

```bash
composer require Albaroody/staging
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="staging-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="staging-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="staging-views"
```

## Usage
1. Add the Stagable trait to your model:
```php
use Albaroody\\Staging\\Traits\\Stagable;

class Patient extends Model
{
    use Stagable;
}
```
2. Stage a model:
```php
$patient = new Patient([
    'name' => 'John Doe',
]);

$patient->stage();
```
3. Load a staged model:
```php
$stagedPatient = Patient::findStaged($stagingId);

// Now you can use it like a normal model
echo $stagedPatient->name;
```
4. Promote a staged model to the database:
```php
$realPatient = $stagedPatient->promoteFromStaging();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Albaroody](https://github.com/Albaroody)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
