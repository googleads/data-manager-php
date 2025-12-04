# How to become a contributor and submit your own code

## Contributor License Agreements

We'd love to accept your sample apps and patches! Before we can take them, we
have to jump a couple of legal hurdles.

Please fill out either the individual or corporate Contributor License Agreement
(CLA).

  * If you are an individual writing original source code and you're sure you
    own the intellectual property, then you'll need to sign an
    [individual CLA](https://developers.google.com/open-source/cla/individual).
  * If you work for a company that wants to allow you to contribute your work,
    then you'll need to sign a
    [corporate CLA](https://developers.google.com/open-source/cla/corporate).

Follow either of the two links above to access the appropriate CLA and
instructions for how to sign and return it. Once we receive it, we'll be able to
accept your pull requests.

## Contributing A Patch

1. Submit an issue describing your proposed change to the repo in question.
1. The repo owner will respond to your issue promptly.
1. If your proposed change is accepted, and you haven't already done so, sign a
   Contributor License Agreement (see details above).
1. Fork the desired repo, develop and test your code changes.
1. Ensure that your code adheres to the existing style in the sample to which
   you are contributing. Refer to the
   [Google Cloud Platform Samples Style Guide](https://github.com/GoogleCloudPlatform/Template/wiki/style.html)
   for the recommended coding standards for this organization.
1. Ensure that your code has an appropriate set of unit tests which all pass.
1. Submit a pull request.

## Writing a new sample

Write samples according to the [sample style
guide](https://googlecloudplatform.github.io/samples-style-guide/).

## Testing your code changes

Run tests using `composer test`.

Use `./vendor/bin/phpunit -v` to get more detailed output if there are errors.

## Style

The [Google Cloud Samples Style Guide][style-guide] is considered the primary
guidelines for all Google Cloud samples.

[style-guide]: https://googlecloudplatform.github.io/samples-style-guide/

Samples in this repository also follow the [PSR2][psr2] and [PSR4][psr4]
recommendations. This is enforced using [PHP CS Fixer][php-cs-fixer], using the
config in [.php-cs-fixer.dist.php](.php-cs-fixer.dist.php)

Install that by running:

```
composer require --dev friendsofphp/php-cs-fixer
```

Then to fix all files run:

```
composer fix
```

To fix a single file, run:

```
composer fix path/to/file
```

[psr2]: http://www.php-fig.org/psr/psr-2/
[psr4]: http://www.php-fig.org/psr/psr-4/
[php-cs-fixer]: https://github.com/FriendsOfPHP/PHP-CS-Fixer
