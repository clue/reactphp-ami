<?php

require __DIR__ . '/../vendor/autoload.php';

class TestCase extends PHPUnit_Framework_TestCase
{
    protected function expectCallableOnce()
    {
        $mock = $this->createCallableMock();

        if (func_num_args() > 0) {
            $mock
                ->expects($this->once())
                ->method('__invoke')
                ->with($this->equalTo(func_get_arg(0)));
        } else {
            $mock
                ->expects($this->once())
                ->method('__invoke');
        }

        return $mock;
    }

    /**
     * @link https://github.com/reactphp/react/blob/master/tests/React/Tests/Socket/TestCase.php (taken from reactphp/react)
     */
    protected function createCallableMock()
    {
        return $this->getMockBuilder('CallableStub')->getMock();
    }
}

class CallableStub
{
    public function __invoke()
    {
    }
}
