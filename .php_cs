<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('src')
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'single_line_after_imports' => false,
    ])
    ->setFinder($finder)
;