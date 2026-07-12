<?php

namespace Tests\Unit;

use App\Rules\MaliPhone;
use PHPUnit\Framework\TestCase;

class MaliPhoneTest extends TestCase
{
    public function test_accepts_common_malian_phone_formats(): void
    {
        $validNumbers = [
            '67 20 57 36',
            '67205736',
            '+223 67 20 57 36',
            '00223 67 20 57 36',
            '67-20-57-36',
            '67.20.57.36',
        ];

        foreach ($validNumbers as $number) {
            $failed = false;

            (new MaliPhone())->validate('telephone', $number, function () use (&$failed) {
                $failed = true;
            });

            $this->assertFalse($failed, "{$number} should be accepted.");
        }
    }

    public function test_normalizes_to_local_eight_digit_format(): void
    {
        $this->assertSame('67205736', MaliPhone::normalize('67 20 57 36'));
        $this->assertSame('67205736', MaliPhone::normalize('+223 67 20 57 36'));
        $this->assertSame('67205736', MaliPhone::normalize('00223 67-20-57-36'));
    }

    public function test_rejects_invalid_malian_phone_numbers(): void
    {
        $invalidNumbers = [
            '17205736',
            '6720573',
            '+224 67 20 57 36',
            '67 20 57 36 99',
        ];

        foreach ($invalidNumbers as $number) {
            $failed = false;

            (new MaliPhone())->validate('telephone', $number, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "{$number} should be rejected.");
        }
    }
}
