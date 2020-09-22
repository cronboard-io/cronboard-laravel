# Cronboard (for Laravel)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cronboard-io/cronboard-laravel.svg?style=flat-square)](https://packagist.org/packages/cronboard-io/cronboard-laravel)
![Build Status](https://img.shields.io/github/workflow/status/cronboard-io/cronboard-laravel/Tests)

## Installation

You can install the package via composer:

```bash
composer require cronboard-io/cronboard-laravel
```

## Usage

When setting up a new project on [cronboard.io](https://cronboard.io) you will be guided through the setup. The first step would be to link your project with Cronboard.

```bash
php artisan cronboard:install [token]
```

This will create a new project with Cronboard (if one has not been added already) and link it with your environment. 

To monitor your scheduled tasks you need to record them with Cronboard. You can do that with the record command:

```bash
php artisan cronboard:record
```

It is recommended you include this command in your deployment process to make sure your schedule is kept in sync.

You have full control over what tasks get recorded with Cronboard through settings in the `config/cronboard.php` file (after you've published the package configuration). You can see a preview of what will be recorded using:

```bash
php artisan cronboard:preview
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email info@cronboard.io instead of using the issue tracker.

## Credits

- [Stefan Kovachev](https://github.com/skovachev)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
