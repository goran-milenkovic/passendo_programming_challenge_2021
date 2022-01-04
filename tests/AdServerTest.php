<?php

use PHPUnit\Framework\TestCase;

final class AdServerTest extends TestCase
{
    public function testSuccess(): void
    {
        $ad_server = new AdServer();
        //space pre sign-a
        $this->assertEquals($ad_server->shouldAdBeServed(['age' => 20], 'age>=18 and age<=30'), true);
        $this->assertEquals($ad_server->shouldAdBeServed(['age' => 45, 'category' => 'economics'], '(age>=18 and age<=30) or (age>40 and age<60)'), true);
        $this->assertEquals($ad_server->shouldAdBeServed(['age' => 44, 'category' => 'economics'], '(age=18 and age<=30) or (age=[40-45] or age>60)'), true);
        $this->assertEquals($ad_server->shouldAdBeServed(['age' => 45, 'category' => 'economics'], '(age=18 and age<=30) or (age=44,45 or age>60)'), true);
        $this->assertEquals($ad_server->shouldAdBeServed(['age' => 45, 'category' => 'economics'], '(age=18 and age<=30) or (category=sport,economics or age>60)'), true);
        $this->assertEquals($ad_server->shouldAdBeServed(['age' => 55, 'category' => 'economics'], '(age=18 and age<=30) or (category=sport,econmics or age>60)'), false);
    }

    public function testInvalidNumberOfClosingBrackets() : void
    {
        $ad_server = new AdServer();
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Too many closing parentheses');
        $ad_server->shouldAdBeServed(['age' => 45, 'category' => 'economics'], '(age=18 and age<=30) or (age=44,45 or age>60))');
    }

    public function testInvalidNumberOfClosingBracketsInsideMainExpression() : void
    {
        $ad_server = new AdServer();
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Too many closing parentheses');
        $ad_server->shouldAdBeServed(['age' => 45, 'category' => 'economics'], '(age=18 and age<=30))) or (age=44,45 or age>60)');
    }
    
    public function testInvalidKeyNotExist() : void
    {
        $ad_server = new AdServer();
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Key agge not exists');
        $ad_server->shouldAdBeServed(['age' => 45, 'category' => 'economics'], '(agge=18 and age<=30) or (age=44,45 or age>60))');
    }

    public function testInvalidLeftRangeValueNotSpecified() : void
    {
        $ad_server = new AdServer();
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Left range value for key age not specified');
        $ad_server->shouldAdBeServed(['age' => 45, 'category' => 'economics'], '(age=18 and age<=30) or (age=[-45] or age>60))');
    }

    public function testInvalidRightRangeValueNotSpecified() : void
    {
        $ad_server = new AdServer();
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Right range value for key age not specified');
        $ad_server->shouldAdBeServed(['age' => 45, 'category' => 'economics'], '(age=18 and age<=30) or (age=[45-] or age>60))');
    }

    public function testInvalidLessThanOrEqualValueNotSpecified() : void
    {
        $ad_server = new AdServer();
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Value for key age not specified');
        $ad_server->shouldAdBeServed(['age' => 45, 'category' => 'economics'], '(age=18 and age<=) or (age=[45-] or age>60))');
    }

    public function testInvalidMissingValueAfterComma() : void
    {
        $ad_server = new AdServer();
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Value for key age missing after comma');
        $ad_server->shouldAdBeServed(['age' => 45, 'category' => 'economics'], '(age=18 and age=19,) or (age=[45-80] or age>60))');
    }
    public function testInvalidMissingValueAfterEqual() : void
    {
        $ad_server = new AdServer();
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Value for key age missing after =');
        $ad_server->shouldAdBeServed(['age' => 45, 'category' => 'economics'], '(age= and age=19,43) or (age=[45-80] or age>60))');
    }

    public function testInvalidNotEnoughClosingBrackets() : void
    {
        $ad_server = new AdServer();
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Not enough closing brackets');
        $ad_server->shouldAdBeServed(['age' => 45, 'category' => 'economics'], '(age=15 and age=19,23) or (((age=[45-80] or age>60))');
    }

    public function testInvalidNotNumericValueForLeftRangeValue() : void
    {
        $ad_server = new AdServer();
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Left range value must be numeric');
        $ad_server->shouldAdBeServed(['age' => 45, 'category' => 'economics'], '(age=15 and age=19,23) or (((age=[passendo-45] or age>60))');
    }

    public function testInvalidNotNumericValueForRightRangeValue() : void
    {
        $ad_server = new AdServer();
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Right range value must be numeric');
        $ad_server->shouldAdBeServed(['age' => 45, 'category' => 'economics'], '(age=15 and age=19,23) or (((age=[45-php] or age>60))');
    }
    
}
