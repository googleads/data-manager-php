# Data Manager API Utility Library and Samples for PHP

Utility library and code samples for working with the
[Data Manager API](https://developers.google.com/data-manager/api) and PHP.

## Requirements

* PHP 8.2+

## Documentation

Please refer to our [Developer
Site](https://developers.google.com/data-manager/api/get-started/set-up-access#php)
for documentation on how to install, configure, and use the client library.

## Add dependencies to your project

To use the utility library in your PHP project, install it using [Composer](https://getcomposer.org/):

```shell
composer require googleads/data-manager-util
```

### Quickstart

```php
use Google\Ads\DataManagerUtil\Formatter;
use Google\Ads\DataManagerUtil\Encoding;

$formatter = new Formatter();

// Format, normalize, hash (SHA-256), and hex-encode an email address:
$email = $formatter->processEmailAddress('User.Name@gmail.com', Encoding::Hex);

// Format, normalize, hash (SHA-256), and hex-encode a phone number:
$phone = $formatter->processPhoneNumber('+1 (800) 555-0100', Encoding::Hex);
```

## Repository structure

- [`src/`](src/): Source code for the `googleads/data-manager-util` library.
  Follow the instructions above to declare a dependency on `googleads/data-manager-util`
  in your project. Use the utilities in the library to help with common tasks like
  formatting, normalizing, hashing, and encoding data for Data Manager API requests.

- [`samples/`](samples/): Code samples demonstrating how to construct and send
  requests to the Data Manager API using the API client library (`googleads/data-manager`)
  and the utility library.

## Usage

The `samples/audiences` directory contains example scripts demonstrating how to
ingest audience members.

* `ingest_audience_members.php`: Shows how to ingest audience members without
  encryption.

Before running the samples, ensure you have set up your Google Cloud project,
enabled the necessary APIs, and configured authentication (e.g., Application
Default Credentials).

## Run samples

To run a sample, invoke the script using the command line. You can pass
arguments to the script in one of two ways:

### 1.  Explicitly, on the command line

```shell
php samples/events/ingest_events.php \
  --operating_account_type=<operating_account_type> \
  --operating_account_id=<operating_account_id> \
  --conversion_action_id=<conversion_action_id> \
  --json_file='</path/to/your/file>'
```

### 2.  Using an arguments file

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
