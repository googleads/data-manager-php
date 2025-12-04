# Data Manager API Utilities and Samples for PHP

Utilities and code samples for working with the Data Manager API and PHP.

## Requirements

* PHP 8.1+

## Documentation

Please refer to our [Developer
Site](https://developers.google.com/data-manager/api/get-started/set-up-access#php)
for documentation on how to install, configure, and use this client library.

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
