<?php

/*
 * This file is part of the Drift Server
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Drift\Server\Tests;

use Drift\Server\Adapter\KernelAdapter;
use Drift\Server\Context\ServerContext;
use Drift\Server\Exception\KernelException;
use Drift\Server\Mime\MimeTypeChecker;
use Drift\Server\OutputPrinter;
use Laminas\Diactoros\Response as LaminasResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Filesystem\FilesystemInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class FakeLaminasKernel.
 */
class FakeLaminasKernel implements KernelAdapter
{
    /**
     * @param LoopInterface            $loop
     * @param string                   $rootPath
     * @param ServerContext            $serverContext
     * @param OutputPrinter            $outputPrinter
     * @param MimeTypeChecker          $mimeTypeChecker
     * @param FilesystemInterface|null $filesystem
     *
     * @return PromiseInterface<self>
     *
     * @throws KernelException
     */
    public static function create(
        LoopInterface $loop,
        string $rootPath,
        ServerContext $serverContext,
        OutputPrinter $outputPrinter,
        MimeTypeChecker $mimeTypeChecker,
        ?FilesystemInterface $filesystem
    ): PromiseInterface {
        return resolve(new self());
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function handle(ServerRequestInterface $request): PromiseInterface
    {
        return resolve(new LaminasResponse\JsonResponse([
            'Laminas Response',
        ], 200, []));
    }

    /**
     * Get static folder.
     *
     * @return string|null
     */
    public static function getStaticFolder(): ? string
    {
        return null;
    }

    /**
     * @return PromiseInterface
     */
    public function shutDown(): PromiseInterface
    {
        return resolve();
    }

    /**
     * Get watcher folders.
     *
     * @return string[]
     */
    public static function getObservableFolders(): array
    {
        return [];
    }

    /**
     * Get watcher folders.
     *
     * @return string[]
     */
    public static function getObservableExtensions(): array
    {
        return [];
    }

    /**
     * Get watcher ignoring folders.
     *
     * @return string[]
     */
    public static function getIgnorableFolders(): array
    {
        return [];
    }
}
