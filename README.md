# Data Manager API utility library and samples for PHP

[![Packagist Version](https://img.shields.io/packagist/v/googleads/data-manager-util.svg)](https://packagist.org/packages/googleads/data-manager-util)

Utility library and code samples for working with the
[Data Manager API](https://developers.google.com/data-manager/api) and PHP.

## Requirements

- PHP 8.2+

## Setup instructions

The `googleads/data-manager-util` utility library is published to
[Packagist](https://packagist.org/packages/googleads/data-manager-util). Install
it using [Composer](https://getcomposer.org/):

```shell
composer require googleads/data-manager-util
```

For complete instructions on setting up API access and installing the client and
utility libraries, see the
[Set up API access](https://developers.google.com/data-manager/api/devguides/quickstart/set-up-access)
and
[Install a client library](https://developers.google.com/data-manager/api/devguides/quickstart/install-library#php)
guides.

## Repository structure

- [`src/`](src/): Source code for the `googleads/data-manager-util` Packagist
  package. Use the utilities in the library to help with common tasks like
  formatting, normalizing, hashing, and encoding data for Data Manager API
  requests.

- [`samples/`](samples/): Code samples demonstrating how to construct and send
  requests to the Data Manager API using the
  [`googleads/data-manager`](https://packagist.org/packages/googleads/data-manager)
  client library and the `googleads/data-manager-util` utility library.

## Run samples

To run a sample, invoke the script using the command line. You can pass
arguments to the script in one of two ways:

### 1. Explicitly, on the command line

```shell
php samples/events/ingest_events.php \
  --operating_account_type=<operating_account_type> \
  --operating_account_id=<operating_account_id> \
  --conversion_action_id=<conversion_action_id> \
  --json_file='</path/to/your/file>'
```

### 2. Using an arguments file

You can also save arguments in a file.

```
samples/events/ingest_events.php
--operating_account_type=<operating_account_type>
--operating_account_id=<operating_account_id>
--conversion_action_id=<conversion_action_id>
--json_file='</path/to/your/file>'
```

Then, run the sample using `xargs`:

```shell
xargs -a /path/to/your/argsfile php
```

## Issue tracker

- https://github.com/googleads/data-manager-php/issues

## Contributing

Contributions welcome! See the [Contributing Guide](CONTRIBUTING.md).

## Authors

- [Josh Radcliff](https://github.com/jradcliff)
- [Lindsey Volta](https://github.com/lindsey-volta)
