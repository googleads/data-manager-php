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
 * Sample of sending an IngestAudienceMembersRequest without encryption.
 */

require_once dirname(__DIR__, 1) . '/vendor/autoload.php';

use Google\Ads\DataManager\V1\AudienceMember;
use Google\Ads\DataManager\V1\Client\IngestionServiceClient;
use Google\Ads\DataManager\V1\CompositeData;
use Google\Ads\DataManager\V1\Consent;
use Google\Ads\DataManager\V1\ConsentStatus;
use Google\Ads\DataManager\V1\Destination;
use Google\Ads\DataManager\V1\Encoding as DataManagerEncoding;
use Google\Ads\DataManager\V1\IngestAudienceMembersRequest;
use Google\Ads\DataManager\V1\IpData;
use Google\Ads\DataManager\V1\ProductAccount;
use Google\Ads\DataManager\V1\ProductAccount\AccountType;
use Google\Ads\DataManager\V1\TermsOfService;
use Google\Ads\DataManager\V1\TermsOfServiceStatus;
use Google\Ads\DataManager\V1\UserData;
use Google\Ads\DataManager\V1\UserIdentifier;
use Google\Ads\DataManagerUtil\Encoding;
use Google\Ads\DataManagerUtil\Formatter;
use Google\ApiCore\ApiException;
use Google\Protobuf\Timestamp;

/**
 * Reads the JSON member data file.
 *
 * @param string $jsonFile The member data file. Expected format is a JSON array of
 * objects, where each object can contain "emails" and "phoneNumbers" arrays.
 * @return array A list of associative arrays, each representing a member.
 */
function readMemberData(string $jsonFile): array
{
    $content = file_get_contents($jsonFile);
    if ($content === false) {
        throw new \RuntimeException(sprintf('Could not read JSON file: %s', $jsonFile));
    }
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \RuntimeException(sprintf('Error decoding JSON: %s', json_last_error_msg()));
    }
    return $data;
}

/**
 * Runs the sample.
 *
 * @param int $operatingAccountType The account type of the operating account.
 * @param string $operatingAccountId The ID of the operating account.
 * @param string $audienceId The ID of the destination audience.
 * @param string $jsonFile The JSON file containing member data.
 * @param bool $validateOnly Whether to enable validateOnly on the request.
 * @param int|null $loginAccountType The account type of the login account.
 * @param string|null $loginAccountId The ID of the login account.
 * @param int|null $linkedAccountType The account type of the linked account.
 * @param string|null $linkedAccountId The ID of the linked account.
 */
function main(
    int $operatingAccountType,
    string $operatingAccountId,
    string $audienceId,
    string $jsonFile,
    bool $validateOnly,
    ?int $loginAccountType = null,
    ?string $loginAccountId = null,
    ?int $linkedAccountType = null,
    ?string $linkedAccountId = null
): void {
    // Reads member data from the data file.
    $memberRows = readMemberData($jsonFile);

    // Gets an instance of the UserDataFormatter for normalizing and formatting the data.
    $formatter = new Formatter();

    // Builds the audience_members collection for the request.
    $audienceMembers = [];
    foreach ($memberRows as $memberRow) {
        $identifiers = [];
        // Adds a UserIdentifier for each valid email address for the member.
        $emails = $memberRow['emails'] ?? [];
        foreach ($emails as $email) {
            try {
                // Formats, hashes, and encodes the email address.
                $processedEmail = $formatter->processEmailAddress($email, Encoding::Hex);
                // Sets the email address identifier to the encoded email hash.
                $identifiers[] = (new UserIdentifier())->setEmailAddress($processedEmail);
            } catch (\InvalidArgumentException $e) {
                // Skips invalid input.
                error_log(sprintf('Skipping invalid email: %s', $e->getMessage()));
                continue;
            }
        }

        // Adds a UserIdentifier for each valid phone number for the member.
        $phoneNumbers = $memberRow['phoneNumbers'] ?? [];
        foreach ($phoneNumbers as $phone) {
            try {
                // Formats, hashes, and encodes the phone number.
                $processedPhone = $formatter->processPhoneNumber($phone, Encoding::Hex);
                // Sets the phone number identifier to the encoded phone number hash.
                $identifiers[] = (new UserIdentifier())->setPhoneNumber($processedPhone);
            } catch (\InvalidArgumentException $e) {
                // Skips invalid input.
                error_log(sprintf('Skipping invalid phone: %s', $e->getMessage()));
                continue;
            }
        }

        // Adds IP address information for each valid entry for the member.
        $ipDatas = [];
        $ipInfos = $memberRow['ipInfos'] ?? [];
        foreach ($ipInfos as $ipInfo) {
            if ($operatingAccountType !== AccountType::GOOGLE_ADS) {
                error_log(sprintf(
                    'Skipping IP address information for operating account type %s. '
                    . 'Sending IP address is only supported for operating account type GOOGLE_ADS.',
                    AccountType::name($operatingAccountType)
                ));
                continue;
            }

            // Trims leading and trailing whitespace from the IP address.
            $ipAddress = trim($ipInfo['ipAddress'] ?? '');
            if (empty($ipAddress)) {
                // Skips the IP information from the input file since it is missing IP address, which is
                // required.
                error_log('Skipping IP address information with no IP address');
                continue;
            }

            // Creates an IpData message and sets its IP address.
            $ipData = (new IpData())->setIpAddress($ipAddress);

            // Adds observe start time to the IpData if present and in the expected timestamp format.
            $startTimeStr = trim($ipInfo['observeStartTime'] ?? '');
            if (!empty($startTimeStr)) {
                try {
                    $startTime = new Timestamp();
                    $startTime->fromDateTime(new \DateTime($startTimeStr));
                    $ipData->setObserveStartTime($startTime);
                } catch (\Exception $e) {
                    error_log(sprintf("Ignoring observe start time '%s' since it can't be parsed", $startTimeStr));
                }
            }

            // Adds observe end time to the IpData if present and in the expected timestamp format.
            $endTimeStr = trim($ipInfo['observeEndTime'] ?? '');
            if (!empty($endTimeStr)) {
                try {
                    $endTime = new Timestamp();
                    $endTime->fromDateTime(new \DateTime($endTimeStr));
                    $ipData->setObserveEndTime($endTime);
                } catch (\Exception $e) {
                    error_log(sprintf("Ignoring observe end time '%s' since it can't be parsed", $endTimeStr));
                }
            }

            $ipDatas[] = $ipData;
        }

        if (!empty($identifiers) || !empty($ipDatas)) {
            $compositeData = new CompositeData();
            if (!empty($identifiers)) {
                $userData = (new UserData())->setUserIdentifiers($identifiers);
                $compositeData->setUserData($userData);
            }
            if (!empty($ipDatas)) {
                $compositeData->setIpData($ipDatas);
            }

            // Adds an AudienceMember with the CompositeData.
            $audienceMember = (new AudienceMember())->setCompositeData($compositeData);
            $audienceMembers[] = $audienceMember;
        }
    }

    // Builds the destination for the request.
    $destination = new Destination();
    $destination->setOperatingAccount(new ProductAccount()
        ->setAccountType($operatingAccountType)
        ->setAccountId($operatingAccountId));

    if ($loginAccountType !== null && $loginAccountId !== null) {
        $destination->setLoginAccount(new ProductAccount()
            ->setAccountType($loginAccountType)
            ->setAccountId($loginAccountId));
    }

    if ($linkedAccountType !== null && $linkedAccountId !== null) {
        $destination->setLinkedAccount(new ProductAccount()
            ->setAccountType($linkedAccountType)
            ->setAccountId($linkedAccountId));
    }

    $destination->setProductDestinationId($audienceId);

    // Builds the request.
    $request = (new IngestAudienceMembersRequest())
        ->setDestinations([$destination])
        ->setAudienceMembers($audienceMembers)
        ->setConsent((new Consent())
            ->setAdUserData(ConsentStatus::CONSENT_GRANTED)
            ->setAdPersonalization(ConsentStatus::CONSENT_GRANTED)
        )
        ->setTermsOfService((new TermsOfService())
            ->setCustomerMatchTermsOfServiceStatus(TermsOfServiceStatus::ACCEPTED)
        )
        // Sets encoding to match the encoding used.
        ->setEncoding(DataManagerEncoding::HEX)
        // Sets validate_only to true to validate but not apply the changes in the request.
        ->setValidateOnly(true);

    // Creates a client for the ingestion service.
    $client = new IngestionServiceClient();
    try {
        // Logs the request.
        echo "Request:\n" . json_encode(json_decode($request->serializeToJsonString()), JSON_PRETTY_PRINT) . "\n";

        // Sends the request.
        $response = $client->ingestAudienceMembers($request);
        echo "Response:\n" . json_encode(json_decode($response->serializeToJsonString()), JSON_PRETTY_PRINT) . "\n";
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
        'audience_id:',
        'json_file:',
        'validate_only::'
    ]
);

$operatingAccountType = $options['operating_account_type'] ?? null;
$operatingAccountId = $options['operating_account_id'] ?? null;
$audienceId = $options['audience_id'] ?? null;
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

if (empty($operatingAccountType) || empty($operatingAccountId) || empty($audienceId) || empty($jsonFile)) {
    echo 'Usage: php ingest_audience_members.php ' .
        '--operating_account_type=<account_type> ' .
        '--operating_account_id=<account_id> ' .
        '--audience_id=<audience_id> ' .
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
    $audienceId,
    $jsonFile,
    $validateOnly,
    $parsedLoginAccountType,
    $options['login_account_id'] ?? null,
    $parsedLinkedAccountType,
    $options['linked_account_id'] ?? null
);
