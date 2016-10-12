# composer-travis-lint
[![Build Status](https://secure.travis-ci.org/raphaelstolt/composer-travis-lint.png)](http://travis-ci.org/raphaelstolt/composer-travis-lint)
[![Version](http://img.shields.io/packagist/v/stolt/composer-travis-lint.svg?style=flat)](https://packagist.org/packages/stolt/composer-travis-lint)
![PHP Version](http://img.shields.io/badge/php-5.6+-ff69b4.svg)
[![composer.lock available](https://poser.pugx.org/stolt/composer-travis-lint/composerlock)](https://packagist.org/packages/stolt/composer-travis-lint)

`composer-travis-lint` is a Composer script that `lints` a project/micro-package its [Travis CI](https://travis-ci.org/) configuration aka its `.travis.yml` file.

## Installation
The Composer script should be installed as a development dependency through Composer.

``` bash
composer require --dev stolt/composer-travis-lint
```

## Usage
Once installed add the Composer script to the existing `composer.json` and use it afterwards via `composer travis-lint`.

``` json
{
    "scripts": {
        "travis-lint":  "Stolt\\Composer\\Travis::lint"
    },
}
```

On a first lint run the Composer script will create a cache file called `.ctl.cache` to avoid making unnecessary lint requests against the [Travis CI API](https://docs.travis-ci.com/api#linting). This file should _prolly_ not be `.gitignored` but __defo__ be kept out of releases and therefore end up in the `.gitattributes` file.

New `lint` requests will only be made when the Travis CI configuration file changes.

#### Running tests without integration tests
``` bash
composer ctl:test
```

#### Running all tests
``` bash
composer ctl:test-all
```

#### License
This Composer script is licensed under the MIT license. Please see [LICENSE](LICENSE.md) for more details.

#### Changelog
Please see [CHANGELOG](CHANGELOG.md) for more details.

#### Contributing
Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for more details.
