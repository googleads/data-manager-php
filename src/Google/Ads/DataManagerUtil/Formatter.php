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

namespace Google\Ads\DataManagerUtil;

/**
 * Formatting utilities for the Data Manager API.
 */
class Formatter
{
    /**
     * Returns the normalized and formatted email address as a string.
     *
     * @param string $email The email address.
     * @return string The formatted email address.
     * @throws \InvalidArgumentException If the provided email address is invalid.
     */
    public function formatEmailAddress(string $email): string
    {
        $email = trim($email);
        if (strlen($email) === 0) {
            throw new \InvalidArgumentException('Email address is blank or empty.');
        }
        if (preg_match('/\s/', $email)) {
            throw new \InvalidArgumentException('Email address contains intermediate whitespace.');
        }

        // Converts to lowercase.
        $email = strtolower($email);
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('Email is not of the form user@domain.');
        }

        list($user, $domain) = $parts;

        if (strlen($user) === 0) {
            throw new \InvalidArgumentException('Email address without the domain is empty.');
        }
        if (strlen($domain) === 0) {
            throw new \InvalidArgumentException('Domain of email address is empty.');
        }

        if ($domain === 'gmail.com' || $domain === 'googlemail.com') {
            // Handles variations of Gmail addresses. See:
            // https://gmail.googleblog.com/2008/03/2-hidden-ways-to-get-more-from-your.html
            // "Create variations of your email address" at:
            // https://support.google.com/a/users/answer/9282734

            // Removes all periods (.).
            $user = str_replace('.', '', $user);
            if (strlen($user) === 0) {
                throw new \InvalidArgumentException(
                    'Email address without the domain is empty after normalization.'
                );
            }
        }
        return "{$user}@{$domain}";
    }

    /**
     * Returns the normalized and formatted phone number as a string.
     *
     * @param string $phone The phone number.
     * @return string The formatted phone number.
     * @throws \InvalidArgumentException If the provided phone number is invalid.
     */
    public function formatPhoneNumber(string $phone): string
    {
        // Removes all whitespace.
        $phone = str_replace(' ', '', $phone);
        if (strlen($phone) === 0) {
            throw new \InvalidArgumentException('Phone number is blank or empty.');
        }
        $phone = preg_replace('/\D/', '', $phone); // Remove all non-digits
        if (strlen($phone) === 0) {
            throw new \InvalidArgumentException('Phone number contains no digits.');
        }
        return "+{$phone}";
    }

    /**
     * Returns bytes containing the hash of the string.
     *
     * @param string $s The string to hash.
     * @return string The raw binary representation of the hash.
     * @throws \InvalidArgumentException If the string is blank or empty.
     */
    public function hashString(string $s): string
    {
        $s = trim($s); // PHP's hash function doesn't automatically strip whitespace like Python's `"".join(s.split())`
        if (strlen($s) === 0) {
            throw new \InvalidArgumentException('String is blank or empty.');
        }
        // Adds 'true' to return raw binary.
        return hash('sha256', $s, true);
    }

    /**
     * Returns the bytes as a hex-encoded string.
     *
     * @param string $bytes The bytes to encode.
     * @return string The hex-encoded string.
     * @throws \InvalidArgumentException If the bytes to encode is empty.
     */
    public function hexEncode(string $bytes): string
    {
        if (strlen($bytes) === 0) {
            throw new \InvalidArgumentException('Bytes empty.');
        }
        return bin2hex($bytes);
    }

    public function base64Encode(string $bytes): string
    {
        if (strlen($bytes) === 0) {
            throw new \InvalidArgumentException('Bytes empty.');
        }
        return base64_encode($bytes);
    }

    /**
     * Returns the normalized and formatted region code as a string.
     *
     * @param string $regionCode The region code.
     * @return string The formatted region code.
     * @throws \InvalidArgumentException If the provided region code is invalid.
     */
    public function formatRegionCode(string $regionCode): string
    {
        $regionCode = strtoupper(trim($regionCode));
        if (strlen($regionCode) !== 2) {
            throw new \InvalidArgumentException(
                'Region code must be two characters.'
            );
        }
        if (!preg_match('/^[A-Z]+$/', $regionCode)) {
            throw new \InvalidArgumentException(
                'Region code contains characters other than A-Z.'
            );
        }
        return $regionCode;
    }

    /**
     * Returns the normalized and formatted given name as a string.
     *
     * @param string $givenName The given name.
     * @return string The formatted given name.
     * @throws \InvalidArgumentException If the provided given name is invalid.
     */
    public function formatGivenName(string $givenName): string
    {
        $givenName = strtolower(trim($givenName));
        if (strlen($givenName) === 0) {
            throw new \InvalidArgumentException('Given name is blank or empty.');
        }
        $givenName = preg_replace('/(?:mr|mrs|ms|dr)\.(?:\s|$)/i', '', $givenName);
        $givenName = trim($givenName);
        if (strlen($givenName) === 0) {
            throw new \InvalidArgumentException('Given name consists solely of a prefix.');
        }
        return $givenName;
    }

    /**
     * Returns the normalized and formatted family name as a string.
     *
     * @param string $familyName The family name.
     * @return string The formatted family name.
     * @throws \InvalidArgumentException If the provided family name is invalid.
     */
    public function formatFamilyName(string $familyName): string
    {
        $familyName = strtolower(trim($familyName));
        if (strlen($familyName) === 0) {
            throw new \InvalidArgumentException('Family name is blank or empty.');
        }
        $pattern = '/(?:,\s*|\s+)(?:jr\.|sr\.|2nd|3rd|ii|iii|iv|v|vi|cpa|dc|dds|vm|jd|md|phd)\s?$/i';
        while (preg_match($pattern, $familyName)) {
            $familyName = preg_replace($pattern, '', $familyName);
        }
        if (strlen($familyName) === 0) {
            throw new \InvalidArgumentException('Family name consists solely of a suffix.');
        }
        return $familyName;
    }

    /**
     * Formats the email address, hashes, and encodes it.
     *
     * @param string $email The email address.
     * @param string $encoding 'hex' or 'base64'.
     * @return string The processed email address.
     */
    public function processEmailAddress(string $email, Encoding $encoding): string
    {
        return $this->hashAndEncode($this->formatEmailAddress($email), $encoding);
    }

    /**
     * Formats the phone number, hashes, and encodes it.
     *
     * @param string $phone The phone number.
     * @param Encoding $encoding The encoding to use.
     * @return string The processed phone number.
     */
    public function processPhoneNumber(string $phone, Encoding $encoding): string
    {
        return $this->hashAndEncode($this->formatPhoneNumber($phone), $encoding);
    }

    /**
     * Formats the given name, hashes, and encodes it.
     *
     * @param string $givenName The given name.
     * @param Encoding $encoding The encoding to use.
     * @return string The processed given name.
     */
    public function processGivenName(string $givenName, Encoding $encoding): string
    {
        return $this->hashAndEncode($this->formatGivenName($givenName), $encoding);
    }

    /**
     * Formats the family name, hashes, and encodes it.
     *
     * @param string $familyName The family name.
     * @param Encoding $encoding The encoding to use.
     * @return string The processed family name.
     */
    public function processFamilyName(string $familyName, Encoding $encoding): string
    {
        return $this->hashAndEncode($this->formatFamilyName($familyName), $encoding);
    }

    /**
     * Formats the region code.
     *
     * @param string $regionCode The region code.
     * @return string The processed region code.
     */
    public function processRegionCode(string $regionCode): string
    {
        return $this->formatRegionCode($regionCode);
    }

    private function hashAndEncode(string $normalizedString, Encoding $encoding): string
    {
        $hashBytes = $this->hashString($normalizedString);
        return $this->encode($hashBytes, $encoding);
    }

    private function encode(string $bytes, Encoding $encoding): string
    {
        return match ($encoding) {
            Encoding::Hex => $this->hexEncode($bytes),
            Encoding::Base64 => $this->base64Encode($bytes),
        };
    }
}
