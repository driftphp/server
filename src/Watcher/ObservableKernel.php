<?php


namespace Drift\Server\Watcher;

/**
 * Interface ObservableKernel
 */
interface ObservableKernel
{
    /**
     * Get watcher folders
     *
     * @return string[]
     */
    public static function getObservableFolders() : array;

    /**
     * Get watcher folders
     *
     * @return string[]
     */
    public static function getObservableExtensions() : array;

    /**
     * Get watcher ignoring folders
     *
     * @return string[]
     */
    public static function getIgnorableFolders() : array;
}