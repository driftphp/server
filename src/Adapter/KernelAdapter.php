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

namespace Drift\Server\Adapter;

use Drift\Console\OutputPrinter;
use Drift\Server\Context\ServerContext;
use Drift\Server\Exception\KernelException;
use Drift\Server\Mime\MimeTypeChecker;
use Drift\Server\Watcher\ObservableKernel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Filesystem\FilesystemInterface;
use React\Promise\PromiseInterface;

/**
 * Class KernelAdapter.
 */
interface KernelAdapter extends ObservableKernel
{
    /**
     * @param LoopInterface       $loop
     * @param string              $rootPath
     * @param ServerContext       $serverContext
     * @param FilesystemInterface $filesystem
     * @param OutputPrinter       $outputPrinter
     * @param MimeTypeChecker     $mimeTypeChecker
     *
     * @return PromiseInterface<self>
     *
     * @throws KernelException
     */
    public static function create(
        LoopInterface $loop,
        string $rootPath,
        ServerContext $serverContext,
        FilesystemInterface $filesystem,
        OutputPrinter $outputPrinter,
        MimeTypeChecker $mimeTypeChecker
    ): PromiseInterface;

    /**
     * @param ServerRequestInterface $request
     * @param callable               $resolve
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function handle(
        ServerRequestInterface $request,
        callable $resolve
    ): PromiseInterface;

    /**
     * Get static folder.
     *
     * @return string|null
     */
    public static function getStaticFolder(): ? string;

    /**
     * @return PromiseInterface
     */
    public function shutDown(): PromiseInterface;
}
