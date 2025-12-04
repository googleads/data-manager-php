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

use PHPUnit\Framework\TestCase;

/**
 * Tests for the Formatter utility.
 */
class FormatterTest extends TestCase
{
    private Formatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new Formatter();
    }

    /**
     * @dataProvider validEmailAddressProvider
     */
    public function testFormatEmailAddressValidInputs(string $input, string $expected): void
    {
        $this->assertEquals($expected, $this->formatter->formatEmailAddress($input));
    }

    public static function validEmailAddressProvider(): array
    {
        return [
            'case normalized name' => ['QuinnY@example.com', 'quinny@example.com'],
            'case normalized domain' => ['QuinnY@EXAMPLE.com', 'quinny@example.com'],
            'periods stripped from gmail.com' => ['Jefferson.Loves.hiking@gmail.com', 'jeffersonloveshiking@gmail.com'],
            'periods stripped from googlemail.com' => ['Jefferson.LOVES.Hiking@googlemail.com', 'jeffersonloveshiking@googlemail.com'],
        ];
    }

    /**
     * @dataProvider invalidEmailAddressProvider
     */
    public function testFormatEmailAddressInvalidInputs(?string $input): void
    {
        $this->expectException(($input === null) ? \Error::class : \InvalidArgumentException::class);
        $this->formatter->formatEmailAddress($input);
    }

    public static function invalidEmailAddressProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'blank string' => ['  '],
            'no @ symbol' => ['quinn'],
            'empty user part' => [' @googlemail.com'],
            'empty user part after normalization' => [' ...@gmail.com'],
        ];
    }

    /**
     * @dataProvider validPhoneNumberProvider
     */
    public function testFormatPhoneNumberValidInputs(string $input, string $expected): void
    {
        $this->assertEquals($expected, $this->formatter->formatPhoneNumber($input));
    }

    public static function validPhoneNumberProvider(): array
    {
        return [
            'with spaces' => ['1 800 555 0100', '+18005550100'],
            'no spaces' => ['18005550100', '+18005550100'],
            'with dashes' => ['+1 800-555-0100', '+18005550100'],
            'international no prefix' => ['441134960987', '+441134960987'],
            'international with prefix' => ['+441134960987', '+441134960987'],
            'international with dashes and prefix' => ['+44-113-496-0987', '+441134960987'],
        ];
    }

    /**
     * @dataProvider invalidPhoneNumberProvider
     */
    public function testFormatPhoneNumberInvalidInputs(?string $input): void
    {
        $this->expectException(($input === null) ? \Error::class : \InvalidArgumentException::class);
        $this->formatter->formatPhoneNumber($input);
    }

    public static function invalidPhoneNumberProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'blank string' => ['  '],
            'no digits' => [' +A BCD EFG '],
        ];
    }

    /**
     * @dataProvider validHashStringProvider
     */
    public function testHashStringValidInputs(string $input, string $expectedHashBytes): void
    {
        $hashBytes = $this->formatter->hashString($input);
        $this->assertIsString($hashBytes);
        $this->assertEquals($expectedHashBytes, $hashBytes);
    }

    public static function validHashStringProvider(): array
    {
        return [
            'email hash' => ['alexz@example.com', hex2bin('509e933019bb285a134a9334b8bb679dff79d0ce023d529af4bd744d47b4fd8a')],
            'phone hash' => ['+18005550100', hex2bin('fb4f73a6ec5fdb7077d564cdd22c3554b43ce49168550c3b12c547b78c517b30')],
            'simple string' => ['abc', hex2bin('ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad')],
        ];
    }

    /**
     * @dataProvider invalidHashStringProvider
     */
    public function testHashStringInvalidInputs(?string $input): void
    {
        $this->expectException(($input === null) ? \Error::class : \InvalidArgumentException::class);
        $this->formatter->hashString($input);
    }

    public static function invalidHashStringProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'blank string' => [' '],
            'multiple blanks' => ['   '],
        ];
    }

    /**
     * @dataProvider validHexEncodeProvider
     */
    public function testHexEncodeValidInputs(string $inputBytes, string $expectedHashBytes): void
    {
        $encodedString = $this->formatter->hexEncode($inputBytes);
        $this->assertIsString($encodedString);
        $this->assertEquals($expectedHashBytes, $encodedString);
    }

    public static function validHexEncodeProvider(): array
    {
        return [
            'alphanumeric' => ['acK123', '61634b313233'],
            'numbers and symbols' => ['999_XYZ', '3939395f58595a'],
        ];
    }

    /**
     * @dataProvider invalidHexEncodeProvider
     */
    public function testHexEncodeInvalidInputs(?string $inputBytes): void
    {
        $this->expectException(($inputBytes === null) ? \Error::class : \InvalidArgumentException::class);
        $this->formatter->hexEncode($inputBytes);
    }

    public static function invalidHexEncodeProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
        ];
    }

    /**
     * @dataProvider validBase64EncodeProvider
     */
    public function testBase64EncodeValidInputs(string $inputBytes, string $expectedBase64): void
    {
        $encodedString = $this->formatter->base64Encode($inputBytes);
        $this->assertIsString($encodedString);
        $this->assertEquals($expectedBase64, $encodedString);
    }

    public static function validBase64EncodeProvider(): array
    {
        return [
            'alphanumeric' => ['acK123', 'YWNLMTIz'],
            'numbers and symbols' => ['999_XYZ', 'OTk5X1hZWg=='],
        ];
    }

    /**
     * @dataProvider invalidBase64EncodeProvider
     */
    public function testBase64EncodeInvalidInputs(?string $inputBytes): void
    {
        $this->expectException(($inputBytes === null) ? \Error::class : \InvalidArgumentException::class);
        $this->formatter->base64Encode($inputBytes);
    }

    public static function invalidBase64EncodeProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
        ];
    }

    /**
     * @dataProvider validRegionCodeProvider
     */
    public function testFormatRegionCodeValidInputs(string $input, string $expected): void
    {
        $this->assertEquals($expected, $this->formatter->formatRegionCode($input));
    }

    public static function validRegionCodeProvider(): array
    {
        return [
            ['us', 'US'],
            ['us  ', 'US'],
            ['  us  ', 'US'],
        ];
    }

    /**
     * @dataProvider invalidRegionCodeProvider
     */
    public function testFormatRegionCodeInvalidInputs(?string $input): void
    {
        $this->expectException(($input === null) ? \Error::class : \InvalidArgumentException::class);
        $this->formatter->formatRegionCode($input);
    }

    public static function invalidRegionCodeProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'blank string' => ['  '],
            'too short' => ['u'],
            'too long' => ['usa'],
            'with space' => ['u s'],
            'with number' => ['u2'],
        ];
    }

    /**
     * @dataProvider validGivenNameProvider
     */
    public function testFormatGivenNameValidInputs(string $input, string $expected): void
    {
        $this->assertEquals($expected, $this->formatter->formatGivenName($input));
    }

    public static function validGivenNameProvider(): array
    {
        return [
            [' Alex   ', 'alex'],
            [' Mr. Alex   ', 'alex'],
            [' Mrs. Alex   ', 'alex'],
            [' Dr. Alex   ', 'alex'],
            [' Alex Dr.', 'alex'],
            [' Mralex   ', 'mralex'],
        ];
    }

    /**
     * @dataProvider invalidGivenNameProvider
     */
    public function testFormatGivenNameInvalidInputs(?string $input): void
    {
        $this->expectException(($input === null) ? \Error::class : \InvalidArgumentException::class);
        $this->formatter->formatGivenName($input);
    }

    public static function invalidGivenNameProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'blank string' => ['  '],
            'only prefix' => [' Mr. '],
        ];
    }

    /**
     * @dataProvider validFamilyNameProvider
     */
    public function testFormatFamilyNameValidInputs(string $input, string $expected): void
    {
        $this->assertEquals($expected, $this->formatter->formatFamilyName($input));
    }

    public static function validFamilyNameProvider(): array
    {
        return [
            [' Quinn   ', 'quinn'],
            ['Quinn-Alex', 'quinn-alex'],
            [' Quinn, Jr.   ', 'quinn'],
            [' Quinn,Jr.   ', 'quinn'],
            [' Quinn Sr.  ', 'quinn'],
            ['quinn, jr. dds', 'quinn'],
            ['quinn, jr., dds', 'quinn'],
            ['Boardds', 'boardds'],
            ['lacparm', 'lacparm'],
        ];
    }

    /**
     * @dataProvider invalidFamilyNameProvider
     */
    public function testFormatFamilyNameInvalidInputs(?string $input): void
    {
        $this->expectException(($input === null) ? \Error::class : \InvalidArgumentException::class);
        $this->formatter->formatFamilyName($input);
    }

    public static function invalidFamilyNameProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'blank string' => ['  '],
            'only suffix' => [', Jr. '],
            [',Jr.,DDS '],
        ];
    }

    public function testProcessEmailAddressHex(): void
    {
        $this->assertEquals(
            '509e933019bb285a134a9334b8bb679dff79d0ce023d529af4bd744d47b4fd8a',
            $this->formatter->processEmailAddress('alexz@example.com', Encoding::Hex)
        );
    }

    public function testProcessEmailAddressBase64(): void
    {
        $this->assertEquals(
            'UJ6TMBm7KFoTSpM0uLtnnf950M4CPVKa9L10TUe0/Yo=',
            $this->formatter->processEmailAddress('alexz@example.com', Encoding::Base64)
        );
    }

    public function testProcessPhoneNumberHex(): void
    {
        $this->assertEquals(
            'fb4f73a6ec5fdb7077d564cdd22c3554b43ce49168550c3b12c547b78c517b30',
            $this->formatter->processPhoneNumber('+18005550100', Encoding::Hex)
        );
    }

    public function testProcessPhoneNumberBase64(): void
    {
        $this->assertEquals(
            '+09zpuxf23B31WTN0iw1VLQ85JFoVQw7EsVHt4xRezA=',
            $this->formatter->processPhoneNumber('+18005550100', Encoding::Base64)
        );
    }

    public function testProcessGivenNameHex(): void
    {
        $this->assertEquals(
            '128a07bfe2df877c52076e60d7774cf5baaa046c5a6c48daf30ff43ecca2f814',
            $this->formatter->processGivenName('Givenname', Encoding::Hex)
        );
    }

    public function testProcessGivenNameBase64(): void
    {
        $this->assertEquals(
            'EooHv+Lfh3xSB25g13dM9bqqBGxabEja8w/0Psyi+BQ=',
            $this->formatter->processGivenName('Givenname', Encoding::Base64)
        );
    }

    public function testProcessFamilyNameHex(): void
    {
        $this->assertEquals(
            '77762c287e61ce065bee5c15464012c6fbe088398b8057627d5577249430d574',
            $this->formatter->processFamilyName('Familyname', Encoding::Hex)
        );
    }

    public function testProcessFamilyNameBase64(): void
    {
        $this->assertEquals(
            'd3YsKH5hzgZb7lwVRkASxvvgiDmLgFdifVV3JJQw1XQ=',
            $this->formatter->processFamilyName('Familyname', Encoding::Base64)
        );
    }

    public function testProcessRegionCode(): void
    {
        $this->assertEquals('US', $this->formatter->processRegionCode(' us'));
    }
}
