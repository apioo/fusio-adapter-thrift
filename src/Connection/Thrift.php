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

namespace Fusio\Adapter\Thrift\Connection;

use Fusio\Engine\Connection\DeploymentInterface;
use Fusio\Engine\ConnectionAbstract;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TBufferedTransport;
use Thrift\Transport\THttpClient;
use Thrift\Transport\TSocket;

/**
 * Thrift
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class Thrift extends ConnectionAbstract implements DeploymentInterface
{
    public const TYPE_HTTP = 'http';
    public const TYPE_HTTPS = 'https';
    public const TYPE_SOCKET = 'socket';

    public function getName(): string
    {
        return 'Thrift';
    }

    public function getConnection(ParametersInterface $config): mixed
    {
        $name = $config->get('name');
        $ns   = $config->get('namespace');
        $type = $config->get('type');
        $host = $config->get('host');
        $port = (int) $config->get('port');
        $path = $config->get('path');

        $cacheDir = $this->getCacheDir();
        $baseDir  = $cacheDir . '/thrift/' . $name . '/gen-php';
        $class    = ucfirst($name) . 'Client';

        if (!empty($ns)) {
            $baseDir.= '/' . $ns;
            $class = $ns . '\\' . $class;
        }

        /** @psalm-suppress UnresolvableInclude */
        require_once $baseDir . '/Types.php';
        /** @psalm-suppress UnresolvableInclude */
        require_once $baseDir . '/' . $name . '.php';

        if ($type == self::TYPE_SOCKET) {
            $socket = new TSocket($host, $port);
        } else {
            $socket = new THttpClient($host, $port, $path, $type ?: 'http');
        }

        $transport = new TBufferedTransport($socket, 1024, 1024);
        $protocol  = new TBinaryProtocol($transport);
        $transport->open();

        /** @psalm-suppress InvalidStringClass */
        return new $class($protocol);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $types = [
            'http' => 'HTTP',
            'https' => 'HTTPS',
            'socket' => 'Socket',
        ];

        $builder->add($elementFactory->newInput('name', 'Name', 'text', 'Name of the service'));
        $builder->add($elementFactory->newInput('namespace', 'Namespace', 'text', 'Optional the used namespace'));
        $builder->add($elementFactory->newSelect('type', 'Type', $types, 'Protocol which is used to connect to the server'));
        $builder->add($elementFactory->newInput('host', 'Host', 'text', 'The IP or hostname of the server'));
        $builder->add($elementFactory->newInput('port', 'Port', 'text', 'The port of the server'));
        $builder->add($elementFactory->newInput('path', 'Path', 'text', 'If it is a HTTP server optional the path to the server'));
        $builder->add($elementFactory->newTextArea('definition', 'Definition', 'text', 'The Thrift service definition'));
    }

    public function onUp(string $name, ParametersInterface $config): void
    {
        $cacheDir = $this->getCacheDir();

        $dir = $cacheDir . '/thrift';
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $name = strtolower($config->get('name'));
        $base = $dir . '/' . $name;
        $file = $base . '.thrift';
        file_put_contents($file, $config->get('definition'));

        if (!is_dir($base)) {
            mkdir($base);
        }

        /** @psalm-suppress ForbiddenCode */
        shell_exec('thrift -r -o ' . escapeshellarg($base) . ' --gen php ' . escapeshellarg($file));

        if (!is_dir($base . '/gen-php')) {
            throw new ConfigurationException('Could not generate PHP classes');
        }
    }

    public function onDown(string $name, ParametersInterface $config): void
    {
        // @TODO maybe remove the dir
    }

    private function getCacheDir(): string
    {
        if (defined('PSX_PATH_CACHE')) {
            return PSX_PATH_CACHE;
        } else {
            return sys_get_temp_dir();
        }
    }
}
