<?php

namespace Tests\Unit;

use App\Support\ExpiryParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ExpiryParserTest extends TestCase
{
    public function test_exact_date_is_returned(): void
    {
        $this->assertSame(
            '2027-02-02',
            ExpiryParser::parse('2027-02-02')?->toDateString(),
        );
    }

    public function test_month_only_slash(): void
    {
        $this->assertSame('2027-02-28', ExpiryParser::parse('02/2027')?->toDateString());
    }

    public function test_month_only_dash(): void
    {
        $this->assertSame('2027-02-28', ExpiryParser::parse('2027-02')?->toDateString());
    }

    public function test_month_name_short(): void
    {
        $this->assertSame('2027-02-28', ExpiryParser::parse('Feb 2027')?->toDateString());
    }

    public function test_full_day_month_name(): void
    {
        $this->assertSame('2027-02-15', ExpiryParser::parse('15 Feb 2027')?->toDateString());
    }

    public function test_month_name_with_dot_and_mixed_case(): void
    {
        $this->assertSame('2027-02-28', ExpiryParser::parse('Feb. 2027')?->toDateString());
        $this->assertSame('2027-02-28', ExpiryParser::parse('feb 2027')?->toDateString());
        $this->assertSame('2027-02-28', ExpiryParser::parse('FEB 2027')?->toDateString());
        $this->assertSame('2027-09-30', ExpiryParser::parse('SEPT 2027')?->toDateString());
    }

    public function test_month_day_comma_year(): void
    {
        $this->assertSame('2027-02-15', ExpiryParser::parse('Feb 15, 2027')?->toDateString());
        $this->assertSame('2027-02-15', ExpiryParser::parse('FEB 15, 2027')?->toDateString());
    }

    public function test_day_month_uppercase(): void
    {
        $this->assertSame('2027-02-15', ExpiryParser::parse('15 FEB 2027')?->toDateString());
    }

    public function test_surrounding_whitespace_is_trimmed(): void
    {
        $this->assertSame(
            '2027-02-02',
            ExpiryParser::parse('  2027-02-02  ')?->toDateString(),
        );
    }

    public function test_single_digit_month_and_day_are_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        ExpiryParser::parse('2027-2-2');
    }

    public function test_blanks_and_na_return_null(): void
    {
        $this->assertSame(null, ExpiryParser::parse('N/A'));
        $this->assertSame(null, ExpiryParser::parse('-'));
        $this->assertSame(null, ExpiryParser::parse(''));
        $this->assertSame(null, ExpiryParser::parse(null));
        $this->assertSame(null, ExpiryParser::parse('—'));
        $this->assertSame(null, ExpiryParser::parse('–'));
    }

    public function test_month_day_year_is_rejected_as_ambiguous(): void
    {
        $this->expectException(RuntimeException::class);
        ExpiryParser::parse('03/04/2027');
    }

    public function test_month_day_year_with_dots_is_rejected_as_ambiguous(): void
    {
        $this->expectException(RuntimeException::class);
        ExpiryParser::parse('03.04.2027');
    }

    public function test_month_day_year_with_dashes_is_rejected_as_ambiguous(): void
    {
        $this->expectException(RuntimeException::class);
        ExpiryParser::parse('03-04-2027');
    }

    public function test_february_29_in_a_leap_year_is_valid(): void
    {
        $this->assertSame('2028-02-29', ExpiryParser::parse('2028-02-29')?->toDateString());
        $this->assertSame('2028-02-29', ExpiryParser::parse('29 Feb 2028')?->toDateString());
    }

    public function test_february_29_in_a_non_leap_year_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        ExpiryParser::parse('29 Feb 2027');
    }

    public function test_month_only_december(): void
    {
        $this->assertSame('2027-12-31', ExpiryParser::parse('2027-12')?->toDateString());
    }

    public function test_month_name_with_dot(): void
    {
        $this->assertSame('2027-09-30', ExpiryParser::parse('Sept. 2027')?->toDateString());
    }

    public function test_leap_year_month_only_edge_of_year_maps_to_last_day(): void
    {
        $this->assertSame('2028-02-29', ExpiryParser::parse('02/2028')?->toDateString());
    }

    public function test_impossible_date_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        ExpiryParser::parse('2027-02-30');
    }

    public function test_thirteenth_month_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        ExpiryParser::parse('13/2027');
    }

    public function test_gibberish_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        ExpiryParser::parse('gibberish');
    }
}
