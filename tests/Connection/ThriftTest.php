<?php
/*
 * Fusio is an open source API management platform which helps to create innovative API solutions.
 * For the current version and information visit <https://www.fusio-project.org/>
 *
 * Copyright 2015-2023 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Fusio\Adapter\Thrift\Tests\Connection;

use Fusio\Adapter\Thrift\Connection\Thrift;
use Fusio\Adapter\Thrift\Tests\ThriftTestCase;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Form\Element\Input;
use Fusio\Engine\Form\Element\Select;
use Fusio\Engine\Form\Element\TextArea;
use Fusio\Engine\Parameters;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PHPUnit\Framework\TestCase;

/**
 * ThriftTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class ThriftTest extends ThriftTestCase
{
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
            'definition' => file_get_contents(__DIR__ . '/../resources/definition.thrift'),
        ]);

        define('PSX_PATH_CACHE', __DIR__ . '/../resources');

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

        $elements = $builder->getForm()->getElements();
        $this->assertEquals(7, count($elements));
        $this->assertInstanceOf(Input::class, $elements[0]);
        $this->assertInstanceOf(Input::class, $elements[1]);
        $this->assertInstanceOf(Select::class, $elements[2]);
        $this->assertInstanceOf(Input::class, $elements[3]);
        $this->assertInstanceOf(Input::class, $elements[4]);
        $this->assertInstanceOf(Input::class, $elements[5]);
        $this->assertInstanceOf(TextArea::class, $elements[6]);
    }
}
