<?php
$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/sections', __DIR__ . '/tests'])
    ->exclude(['vendor']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'no_trailing_whitespace' => true,
        'single_quote' => true,
        'line_ending' => true,
        'lowercase_keywords' => true,
        'trim_array_spaces' => true,
        'no_whitespace_in_blank_line' => true,
    ])
    ->setFinder($finder);
