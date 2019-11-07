<?php declare(strict_types=1);

namespace Drift\Server;

use React\EventLoop\LoopInterface;
use seregazhuk\PhpWatcher\Config\Builder;
use seregazhuk\PhpWatcher\Config\WatchList;
use seregazhuk\PhpWatcher\Filesystem\ChangesListener;
use seregazhuk\PhpWatcher\Screen\Screen;
use seregazhuk\PhpWatcher\Screen\SpinnerFactory;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ChangesWatcher
{
    private $loop;
    private $screen;
    private $configBuilder;

    public static function isAvailable(): bool
    {
        return class_exists('\seregazhuk\PhpWatcher\Config\WatchList');
    }

    public function __construct(LoopInterface $loop, SymfonyStyle $output)
    {
        $this->loop = $loop;
        $this->screen = new Screen($output, SpinnerFactory::create($output, false));
        $this->configBuilder = new Builder();
    }

    public function watch(Application $application): void
    {
        $watchList = $this->getWatchList();

        $this->screen->showOptions($watchList);
        $this->screen->showSpinner($this->loop);

        $filesystemListener = new ChangesListener($this->loop, $watchList);
        $filesystemListener->on('change', function () use ($application) {
            $this->restartApplication($application);
        });
        $filesystemListener->start();
    }

    private function getWatchList(): WatchList
    {
        $configFilePath = $this->configBuilder->findConfigFile();
        if ($configFilePath === null) {
            return new WatchList(['src'], ['php']);
        }

        return $this->configBuilder->fromConfigFile($configFilePath)->watchList();
    }

    private function restartApplication(Application $application): void
    {
        $this->screen->restarting();
        $application->stop();
        $application->run();
    }
}
