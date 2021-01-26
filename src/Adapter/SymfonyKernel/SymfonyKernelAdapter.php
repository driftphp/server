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

namespace Drift\Server\Adapter\SymfonyKernel;

use App\Kernel as ApplicationKernel;
use Drift\HttpKernel\AsyncKernel;
use Drift\Server\Adapter\SymfonyKernelBasedAdapter;
use Exception;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class SymfonyKernelAdapter.
 */
class SymfonyKernelAdapter extends SymfonyKernelBasedAdapter
{
    /**
     * @param $kernel
     *
     * @throws Exception
     */
    protected function checkKernel($kernel)
    {
        // No checks here
    }

    /**
     * @param string $environment
     * @param bool   $debug
     *
     * @return AsyncKernel
     */
    protected static function createKernelByEnvironmentAndDebug(
        string $environment,
        bool $debug
    ): AsyncKernel {
        return new ApplicationKernel($environment, $debug);
    }

    /**
     * @param Kernel|AsyncKernel $kernel
     * @param Request            $request
     *
     * @return PromiseInterface
     */
    protected function kernelHandle(
        Kernel $kernel,
        Request $request
    ): PromiseInterface {
        return resolve($kernel->handle($request));
    }

    /**
     * @return PromiseInterface
     */
    public function shutDown(): PromiseInterface
    {
        // Nothing to do here
    }

    /**
     * Get watcher folders.
     *
     * @return string[]
     */
    public static function getObservableFolders(): array
    {
        return ['App', 'src', 'public', 'views'];
    }
}
