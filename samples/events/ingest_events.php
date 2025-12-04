<?php
// Copyright 2025 Google LLC
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     https://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

/**
 * Sample of sending an IngestEventsRequest without encryption.
 */

require_once dirname(__DIR__, 1) . '/vendor/autoload.php';

use Google\Ads\DataManager\V1\AdIdentifiers;
use Google\Ads\DataManager\V1\Client\IngestionServiceClient;
use Google\Ads\DataManager\V1\Consent;
use Google\Ads\DataManager\V1\ConsentStatus;
use Google\Ads\DataManager\V1\Destination;
use Google\Ads\DataManager\V1\Encoding as DataManagerEncoding;
use Google\Ads\DataManager\V1\Event;
use Google\Ads\DataManager\V1\EventSource;
use Google\Ads\DataManager\V1\IngestEventsRequest;
use Google\Ads\DataManager\V1\ProductAccount;
use Google\Ads\DataManager\V1\ProductAccount\AccountType;
use Google\Ads\DataManager\V1\UserData;
use Google\Ads\DataManager\V1\UserIdentifier;
use Google\Ads\DataManagerUtil\Encoding;
use Google\Ads\DataManagerUtil\Formatter;
use Google\ApiCore\ApiException;
use Google\Protobuf\Timestamp;

// The maximum number of events allowed per request.
const MAX_EVENTS_PER_REQUEST = 2000;

/**
 * Reads the JSON-formatted event data file.
 *
 * @param string $jsonFile The event data file.
 * @return array A list of associative arrays, each representing an event.
 */
function readEventDataFile(string $jsonFile): array
{
    $jsonContent = file_get_contents($jsonFile);
    if ($jsonContent === false) {
        throw new \RuntimeException(sprintf('Could not read JSON file: %s', $jsonFile));
    }
    $events = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \RuntimeException(sprintf('Invalid JSON in file: %s', $jsonFile));
    }
    return $events;
}

/**
 * Runs the sample.
 *
 * @param int $operatingAccountType The account type of the operating account.
 * @param string $operatingAccountId The ID of the operating account.
 * @param string $conversionActionId The ID of the conversion action.
 * @param string $jsonFile The JSON file containing event data.
 * @param bool $validateOnly Whether to enable validateOnly on the request.
 * @param int|null $loginAccountType The account type of the login account.
 * @param string|null $loginAccountId The ID of the login account.
 * @param int|null $linkedAccountType The account type of the linked account.
 * @param string|null $linkedAccountId The ID of the linked account.
 */
function main(
    int $operatingAccountType,
    string $operatingAccountId,
    string $conversionActionId,
    string $jsonFile,
    bool $validateOnly,
    ?int $loginAccountType = null,
    ?string $loginAccountId = null,
    ?int $linkedAccountType = null,
    ?string $linkedAccountId = null
): void {
    // Reads event data from the data file.
    $eventRecords = readEventDataFile($jsonFile);

    // Gets an instance of the UserDataFormatter for normalizing and formatting the data.
    $formatter = new Formatter();

    // Builds the events collection for the request.
    $events = [];
    foreach ($eventRecords as $eventRecord) {
        $event = new Event();

        if (empty($eventRecord['timestamp'])) {
            error_log('Skipping event with no timestamp.');
            continue;
        }
        try {
            $dateTime = new DateTime($eventRecord['timestamp']);
            $timestamp = new Timestamp();
            $timestamp->fromDateTime($dateTime);
            $event->setEventTimestamp($timestamp);
        } catch (\Exception $e) {
            error_log(sprintf('Skipping event with invalid timestamp: %s', $eventRecord['timestamp']));
            continue;
        }

        if (empty($eventRecord['transactionId'])) {
            error_log('Skipping event with no transaction ID');
            continue;
        }
        $event->setTransactionId($eventRecord['transactionId']);

        if (!empty($eventRecord['eventSource'])) {
            try {
                $event->setEventSource(EventSource::value($eventRecord['eventSource']));
            } catch (\UnexpectedValueException $e) {
                error_log('Skipping event with invalid event source: ' . $eventRecord['eventSource']);
                continue;
            }
        }

        if (!empty($eventRecord['gclid'])) {
            $event->setAdIdentifiers((new AdIdentifiers())->setGclid($eventRecord['gclid']));
        }

        if (!empty($eventRecord['currency'])) {
            $event->setCurrency($eventRecord['currency']);
        }

        if (isset($eventRecord['value'])) {
            $event->setConversionValue($eventRecord['value']);
        }

        $userData = new UserData();
        $identifiers = [];

        if (!empty($eventRecord['emails'])) {
            foreach ($eventRecord['emails'] as $email) {
                try {
                    $preparedEmail = $formatter->processEmailAddress($email, Encoding::Hex);
                    $identifiers[] = (new UserIdentifier())->setEmailAddress($preparedEmail);
                } catch (\InvalidArgumentException $e) {
                    // Skips invalid input.
                    error_log(sprintf('Skipping invalid email: %s', $e->getMessage()));
                    continue;
                }
            }
        }

        if (!empty($eventRecord['phoneNumbers'])) {
            foreach ($eventRecord['phoneNumbers'] as $phoneNumber) {
                try {
                    $preparedPhoneNumber = $formatter->processPhoneNumber($phoneNumber, Encoding::Hex);
                    $identifiers[] = (new UserIdentifier())->setPhoneNumber($preparedPhoneNumber);
                } catch (\InvalidArgumentException $e) {
                    // Skips invalid input.
                    error_log(sprintf('Skipping invalid phone number: %s', $e->getMessage()));
                    continue;
                }
            }
        }

        if (!empty($identifiers)) {
            $userData->setUserIdentifiers($identifiers);
            $event->setUserData($userData);
        }
        $events[] = $event;
    }

    // Builds the destination for the request.
    $destination = (new Destination())
        ->setOperatingAccount((new ProductAccount())
            ->setAccountType($operatingAccountType)
            ->setAccountId($operatingAccountId))
        ->setProductDestinationId($conversionActionId);

    if ($loginAccountType !== null && $loginAccountId !== null) {
        $destination->setLoginAccount((new ProductAccount())
            ->setAccountType($loginAccountType)
            ->setAccountId($loginAccountId));
    }

    if ($linkedAccountType !== null && $linkedAccountId !== null) {
        $destination->setLinkedAccount((new ProductAccount())
            ->setAccountType($linkedAccountType)
            ->setAccountId($linkedAccountId));
    }

    $client = new IngestionServiceClient();
    try {
        $requestCount = 0;
        // Batches requests to send up to the maximum number of events per request.
        foreach (array_chunk($events, MAX_EVENTS_PER_REQUEST) as $eventsBatch) {
            $requestCount++;
            // Builds the request.
            $request = (new IngestEventsRequest())
                ->setDestinations([$destination])
                ->setEvents($eventsBatch)
                ->setConsent((new Consent())
                    ->setAdUserData(ConsentStatus::CONSENT_GRANTED)
                    ->setAdPersonalization(ConsentStatus::CONSENT_GRANTED)
                )
                ->setValidateOnly($validateOnly)
                ->setEncoding(DataManagerEncoding::HEX);

            echo "Request:\n" . json_encode(json_decode($request->serializeToJsonString()), JSON_PRETTY_PRINT) . "\n";
            $response = $client->ingestEvents($request);
            echo "Response for request #{$requestCount}:\n" . json_encode(json_decode($response->serializeToJsonString()), JSON_PRETTY_PRINT) . "\n";
        }
        echo "# of requests sent: {$requestCount}\n";
    } catch (ApiException $e) {
        echo 'Error sending request: ' . $e->getMessage() . "\n";
    } finally {
        $client->close();
    }
}

// Command-line argument parsing
$options = getopt(
    '',
    [
        'operating_account_type:',
        'operating_account_id:',
        'login_account_type::',
        'login_account_id::',
        'linked_account_type::',
        'linked_account_id::',
        'conversion_action_id:',
        'json_file:',
        'validate_only::'
    ]
);

$operatingAccountType = $options['operating_account_type'] ?? null;
$operatingAccountId = $options['operating_account_id'] ?? null;
$conversionActionId = $options['conversion_action_id'] ?? null;
$jsonFile = $options['json_file'] ?? null;

// Only validates requests by default.
$validateOnly = true;
if (array_key_exists('validate_only', $options)) {
    $value = $options['validate_only'];
    // `getopt` with `::` returns boolean `false` if the option is passed without a value.
    if ($value === false || !in_array($value, ['true', 'false'], true)) {
        echo "Error: --validate_only requires a value of 'true' or 'false'.\n";
        exit(1);
    }
    $validateOnly = ($value === 'true');
}

if (empty($operatingAccountType) || empty($operatingAccountId) || empty($conversionActionId) || empty($jsonFile)) {
    echo 'Usage: php ingest_events.php ' .
        '--operating_account_type=<account_type> ' .
        '--operating_account_id=<account_id> ' .
        '--conversion_action_id=<conversion_action_id> ' .
        "--json_file=<path_to_json>\n" .
        'Optional: --login_account_type=<account_type> --login_account_id=<account_id> ' .
        '--linked_account_type=<account_type> --linked_account_id=<account_id> ' .
        "--validate_only=<true|false>\n";
    exit(1);
}

// Converts the operating account type string to an AccountType enum.
$parsedOperatingAccountType = AccountType::value($operatingAccountType);

if (isset($options['login_account_type']) != isset($options['login_account_id'])) {
    throw new \InvalidArgumentException(
        'Must specify either both or neither of login account type and login account ID'
    );
}

$parsedLoginAccountType = null;
if (isset($options['login_account_type'])) {
    // Converts the login account type string to an AccountType enum.
    $parsedLoginAccountType = AccountType::value($options['login_account_type']);
}

if (isset($options['linked_account_type']) != isset($options['linked_account_id'])) {
    throw new \InvalidArgumentException(
        'Must specify either both or neither of linked account type and linked account ID'
    );
}

$parsedLinkedAccountType = null;
if (isset($options['linked_account_type'])) {
    // Converts the linked account type string to an AccountType enum.
    $parsedLinkedAccountType = AccountType::value($options['linked_account_type']);
}

main(
    $parsedOperatingAccountType,
    $operatingAccountId,
    $conversionActionId,
    $jsonFile,
    $validateOnly,
    $parsedLoginAccountType,
    $options['login_account_id'] ?? null,
    $parsedLinkedAccountType,
    $options['linked_account_id'] ?? null
);
