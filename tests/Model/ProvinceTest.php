<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Exception\InvalidProvince;
use App\Model\Country;
use App\Model\Province;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ProvinceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider countryProvider
     */
    public function testFromString(
        string $code,
        string $expected,
        string $name,
        Country $country
    ): void {
        $province = Province::fromString($code);

        $this->assertEquals($expected, $province->abbreviation());
        $this->assertEquals($name, $province->name());
        $this->assertEquals($country, $province->country());
        $this->assertEquals($expected, $province->toString());
        $this->assertEquals($expected, (string) $province);
    }

    public function countryProvider(): \Generator
    {
        yield ['SK', 'SK', 'Saskatchewan', Country::fromString('CA')];
        yield ['sk', 'SK', 'Saskatchewan', Country::fromString('CA')];

        yield ['LA', 'LA', 'Louisiana', Country::fromString('US')];
        yield ['la', 'LA', 'Louisiana', Country::fromString('US')];
    }

    /**
     * @dataProvider invalidProvider
     */
    public function testInvalid(?string $value): void
    {
        $this->expectException(InvalidProvince::class);

        Province::fromString($value);
    }

    public function invalidProvider(): \Generator
    {
        yield ['UK'];
        yield [''];
        yield ['A'];
    }

    public function testSameValueAs(): void
    {
        $province1 = Province::fromString('SK');
        $province2 = Province::fromString('SK');

        $this->assertTrue($province1->sameValueAs($province2));
    }

    public function testSameValueAsFalse(): void
    {
        $province1 = Province::fromString('MS');
        $province2 = Province::fromString('MO');

        $this->assertFalse($province1->sameValueAs($province2));
    }

    public function testSameValueAsDiffClass(): void
    {
        $province = Province::fromString('MS');
        $email = \App\Model\Email::fromString('email@example.com');

        $this->assertFalse($province->sameValueAs($email));
    }
}