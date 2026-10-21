<?php
// Copyright 2026 Google LLC
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
 * Sample of creating a user list in an operating account.
 */

require_once dirname(__DIR__, 1) . '/vendor/autoload.php';

use Google\Ads\DataManager\V1\Client\UserListServiceClient;
use Google\Ads\DataManager\V1\ContactIdInfo;
use Google\Ads\DataManager\V1\CreateUserListRequest;
use Google\Ads\DataManager\V1\DataSourceType;
use Google\Ads\DataManager\V1\IngestedUserListInfo;
use Google\Ads\DataManager\V1\IngestedUserListInfo\UploadKeyType;
use Google\Ads\DataManager\V1\MobileIdInfo;
use Google\Ads\DataManager\V1\MobileIdInfo\KeySpace;
use Google\Ads\DataManager\V1\ProductAccount\AccountType;
use Google\Ads\DataManager\V1\UserIdInfo;
use Google\Ads\DataManager\V1\UserList;
use Google\ApiCore\ApiException;
use Google\Protobuf\Duration;

// [START create-user-list]
/**
 * Runs the sample.
 *
 * @param int $operatingAccountType The account type of the operating account.
 * @param string $operatingAccountId The ID of the operating account.
 * @param bool $validateOnly Whether to enable validateOnly on the request.
 * @param int $uploadKeyType The upload key type of the user list.
 * @param int|null $loginAccountType The account type of the login account.
 * @param string|null $loginAccountId The ID of the login account.
 * @param int|null $linkedAccountType The account type of the linked account.
 * @param string|null $linkedAccountId The ID of the linked account.
 * @param int|null $keySpace The key space of mobile IDs. Required when uploadKeyType is MOBILE_ID.
 * @param string|null $appId The mobile application ID. Required when uploadKeyType is MOBILE_ID.
 */
function main(
    int $operatingAccountType,
    string $operatingAccountId,
    bool $validateOnly,
    int $uploadKeyType = UploadKeyType::CONTACT_ID,
    ?int $loginAccountType = null,
    ?string $loginAccountId = null,
    ?int $linkedAccountType = null,
    ?string $linkedAccountId = null,
    ?int $keySpace = null,
    ?string $appId = null
): void {
    // Validates parameters.
    if (($loginAccountType !== null) !== ($loginAccountId !== null)) {
        throw new \InvalidArgumentException(
            'Must specify either both or neither of login account type and login account ID'
        );
    }
    if (($linkedAccountType !== null) !== ($linkedAccountId !== null)) {
        throw new \InvalidArgumentException(
            'Must specify either both or neither of linked account type and linked account ID'
        );
    }
    if ($uploadKeyType === UploadKeyType::MOBILE_ID) {
        if ($keySpace === null || empty($appId)) {
            throw new \InvalidArgumentException(
                'Must specify both key_space and app_id when upload_key_type is MOBILE_ID'
            );
        }
    } elseif ($keySpace !== null || !empty($appId)) {
        throw new \InvalidArgumentException(
            'key_space and app_id are only supported when upload_key_type is MOBILE_ID'
        );
    }

    // Optional: Sets headers to specify the login-account and linked-account if
    // your credentials aren't for a user in the operating account (for example,
    // when authenticating through a Google Ads manager account or Data Partner
    // account).
    $headers = [];

    if ($loginAccountType !== null && $loginAccountId !== null) {
        $headers['login-account'] = [
            UserListServiceClient::accountName(
                AccountType::name($loginAccountType),
                $loginAccountId
            ),
        ];
    }

    if ($linkedAccountType !== null && $linkedAccountId !== null) {
        $headers['linked-account'] = [
            UserListServiceClient::accountName(
                AccountType::name($linkedAccountType),
                $linkedAccountId
            ),
        ];
    }

    // Configures the Customer Match user list.
    // For more details on constructing a UserList, see:
    // https://developers.google.com/data-manager/api/devguides/audiences/google-ads/customer-match/create-audience
    $ingestedUserListInfo = (new IngestedUserListInfo())
        // Sets to a list containing a single UploadKeyType.
        ->setUploadKeyTypes([$uploadKeyType]);

    switch ($uploadKeyType) {
        case UploadKeyType::CONTACT_ID:
            // Use CONTACT_ID for hashed email addresses, phone numbers, or physical addresses.
            $ingestedUserListInfo->setContactIdInfo(
                (new ContactIdInfo())
                    ->setDataSourceType(DataSourceType::DATA_SOURCE_TYPE_FIRST_PARTY)
            );
            break;
        case UploadKeyType::USER_ID:
            // Use USER_ID for first-party user IDs assigned to logged-in users.
            // Note: Display & Video 360 does not support USER_ID lists.
            $ingestedUserListInfo->setUserIdInfo(
                (new UserIdInfo())
                    ->setDataSourceType(DataSourceType::DATA_SOURCE_TYPE_FIRST_PARTY)
            );
            break;
        case UploadKeyType::MOBILE_ID:
            // Use MOBILE_ID for mobile advertising IDs.
            $ingestedUserListInfo->setMobileIdInfo(
                (new MobileIdInfo())
                    ->setDataSourceType(DataSourceType::DATA_SOURCE_TYPE_FIRST_PARTY)
                    ->setKeySpace($keySpace)
                    ->setAppId($appId)
            );
            break;
        default:
            throw new \InvalidArgumentException(
                sprintf('Unsupported upload key type: %s', UploadKeyType::name($uploadKeyType))
            );
    }

    $uploadKeyTypeName = UploadKeyType::name($uploadKeyType);
    $userList = (new UserList())
        // Required. The display name must be unique among all lists owned by the account.
        ->setDisplayName(sprintf(
            'Customer Match (%s) - %s',
            $uploadKeyTypeName,
            (new \DateTime())->format('Y-m-d H:i:s')
        ))
        ->setDescription(sprintf('Customer Match audience for %s', $uploadKeyTypeName))
        // Optional. The duration an audience member remains in the list after being added. Must
        // correspond to whole days (defaults to maximum if not set).
        ->setMembershipDuration((new Duration())->setSeconds(30 * 24 * 60 * 60))
        // Required for a Customer Match audience.
        ->setIngestedUserListInfo($ingestedUserListInfo);

    // Creates a client for UserListService.
    $userListClient = new UserListServiceClient();

    // Builds the request targeting the parent operating account.
    $request = (new CreateUserListRequest())
        ->setParent(
            UserListServiceClient::accountName(
                AccountType::name($operatingAccountType),
                $operatingAccountId
            )
        )
        ->setUserList($userList)
        // Sets validate_only. If true, then the Data Manager API only validates the request
        // but doesn't apply changes.
        ->setValidateOnly($validateOnly);

    try {
        // Sends the request with any configured header metadata.
        $response = $userListClient->createUserList($request, ['headers' => $headers]);

        // Logs the created user list. When validate_only is false, $response->getId() contains the
        // created user list ID, which you pass as the product_destination_id in subsequent ingest or
        // remove requests.
        echo "Response:\n" . json_encode(json_decode($response->serializeToJsonString()), JSON_PRETTY_PRINT) . "\n";
    } catch (ApiException $e) {
        echo 'Error sending request: ' . $e->getMessage() . "\n";
    } finally {
        $userListClient->close();
    }
}
// [END create-user-list]

// Command-line argument parsing
$options = getopt(
    '',
    [
        'operating_account_type:',
        'operating_account_id:',
        'upload_key_type::',
        'login_account_type::',
        'login_account_id::',
        'linked_account_type::',
        'linked_account_id::',
        'key_space::',
        'app_id::',
        'validate_only::'
    ]
);

$operatingAccountType = $options['operating_account_type'] ?? null;
$operatingAccountId = $options['operating_account_id'] ?? null;
$uploadKeyType = $options['upload_key_type'] ?? 'CONTACT_ID';

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

if (empty($operatingAccountType) || empty($operatingAccountId)) {
    echo 'Usage: php create_user_list.php ' .
        '--operating_account_type=<account_type> ' .
        "--operating_account_id=<account_id>\n" .
        'Optional: --upload_key_type=<CONTACT_ID|USER_ID|MOBILE_ID> ' .
        '--login_account_type=<account_type> --login_account_id=<account_id> ' .
        '--linked_account_type=<account_type> --linked_account_id=<account_id> ' .
        '--key_space=<ANDROID|IOS> --app_id=<app_id> ' .
        "--validate_only=<true|false>\n";
    exit(1);
}

// Converts the operating account type string to an AccountType enum.
$parsedOperatingAccountType = AccountType::value($operatingAccountType);

// Converts the upload key type string to an UploadKeyType enum.
$parsedUploadKeyType = UploadKeyType::value($uploadKeyType);

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

$parsedKeySpace = null;
if (isset($options['key_space'])) {
    // Converts the key space string to a KeySpace enum.
    $parsedKeySpace = KeySpace::value($options['key_space']);
}

main(
    $parsedOperatingAccountType,
    $operatingAccountId,
    $validateOnly,
    $parsedUploadKeyType,
    $parsedLoginAccountType,
    $options['login_account_id'] ?? null,
    $parsedLinkedAccountType,
    $options['linked_account_id'] ?? null,
    $parsedKeySpace,
    $options['app_id'] ?? null
);
