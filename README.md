![Grafite Archive](GrafiteArchive-banner.png)

**Archive** - A simple data archiving package.

[![Build Status](https://github.com/GrafiteInc/Archive/workflows/php-package-tests.yml/badge.svg?branch=main)](https://github.com/GrafiteInc/Archive/actions?query=workflow%3A%22PHP+Package+Tests%22)
[![Maintainability](https://qlty.sh/gh/GrafiteInc/projects/Archive/maintainability.svg)](https://qlty.sh/gh/GrafiteInc/projects/Archive)
[![Packagist](https://img.shields.io/packagist/dt/grafite/archive.svg)](https://packagist.org/packages/grafite/archive)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

The archiving package is designed for helping long term applications with data handling. You have the power to archive old data sets to json storage files in S3 based on time frames. These same files can be restored with the restore command handling a specified date range.

##### Author(s):
* [Matt Lantz](https://github.com/mlantz) ([@mattylantz](http://twitter.com/mattylantz), mattlantz at gmail dot com)

## Requirements

1. PHP 8.4+

## Compatibility and Support

| Laravel Version | Package Tag | Supported |
|-----------------|-------------|-----------|
| ^12.x - ^13.x | 1.x | yes |

### Installation

Start a new Laravel project:
```php
composer create-project laravel/laravel your-project-name
```

Then run the following to add Archive
```php
composer require "grafite/archive"
```

Time to publish those assets!
```php
php artisan vendor:publish --provider="Grafite\Archive\GrafiteArchiveProvider"
```

### Current Command Surface

The package currently provides the first archive workflow command:

```php
php artisan archive:run --model="App\\Models\\Post" --before="2024-12-31 23:59:59"
```

Exports are encrypted by default using Laravel's `APP_KEY`. You can configure default models, scopes, relationships, archive disk, and archive path in `config/archive.php`, then override those defaults from the command line with options such as `--scope`, `--with`, `--without-scopes`, `--without-relationships`, `--unencrypted`, and `--dry-run`.

## Documentation

[https://documentation.grafite.ca/docs/utilities-archive](https://documentation.grafite.ca/docs/utilities-archive)

## License
Archive is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

### Bug Reporting and Feature Requests
Please add as many details as possible regarding submission of issues and feature requests

### Disclaimer
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
