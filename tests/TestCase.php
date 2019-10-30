<?php

namespace Clue\Tests\React\Ami;

use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function expectCallableOnce()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke');

        return $mock;
    }

    protected function createCallableMock()
    {
        return $this->getMockBuilder('stdClass')->setMethods(array('__invoke'))->getMock();
    }
}
