<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2017 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Adapter\Thrift\Tests\Connection;

use Fusio\Adapter\Thrift\Connection\Thrift;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Form\Element\Input;
use Fusio\Engine\Form\Element\Select;
use Fusio\Engine\Form\Element\TextArea;
use Fusio\Engine\Parameters;
use Fusio\Engine\Test\EngineTestCaseTrait;

/**
 * ThriftTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class ThriftTest extends \PHPUnit_Framework_TestCase
{
    use EngineTestCaseTrait;

    public function testGetConnection()
    {
        /** @var Thrift $connection */
        $connection = $this->getConnectionFactory()->factory(Thrift::class);

        $config = new Parameters([
            'name'       => 'Calculator',
            'namespace'  => 'App',
            'type'       => 'http',
            'host'       => 'localhost',
            'port'       => '8080',
            'path'       => '/server.php',
            'definition' => $this->getDefinition(),
        ]);

        $connection->onUp('action', $config);

        $client = $connection->getConnection($config);

        $this->assertInstanceOf('App\CalculatorIf', $client);
    }

    public function testConfigure()
    {
        $connection = $this->getConnectionFactory()->factory(Thrift::class);
        $builder    = new Builder();
        $factory    = $this->getFormElementFactory();

        $connection->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());

        $elements = $builder->getForm()->getProperty('element');
        $this->assertEquals(7, count($elements));
        $this->assertInstanceOf(Input::class, $elements[0]);
        $this->assertInstanceOf(Input::class, $elements[1]);
        $this->assertInstanceOf(Select::class, $elements[2]);
        $this->assertInstanceOf(Input::class, $elements[3]);
        $this->assertInstanceOf(Input::class, $elements[4]);
        $this->assertInstanceOf(Input::class, $elements[5]);
        $this->assertInstanceOf(TextArea::class, $elements[6]);
    }

    private function getDefinition()
    {
        return <<<TEXT
namespace php App

exception InvalidOperation {
  1: i32 what,
  2: string why
}

enum Operation {
  ADD = 1,
  SUBTRACT = 2,
  MULTIPLY = 3,
  DIVIDE = 4
}

struct Work {
  1: i32 num1 = 0,
  2: i32 num2,
  3: Operation op,
  4: optional string comment,
}

service Calculator
{
   void ping(),

   i32 add(1:i32 num1, 2:i32 num2),

   i32 calculate(1:i32 logid, 2:Work w) throws (1:InvalidOperation ouch),

   /**
    * This method has a oneway modifier. That means the client only makes
    * a request and does not listen for any response at all. Oneway methods
    * must be void.
    */
   oneway void zip()
}

TEXT;
    }
}
