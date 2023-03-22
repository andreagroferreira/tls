<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('somedir')
    ->notPath('src/Symfony/Component/Translation/Tests/fixtures/resources.php')
    ->in(__DIR__);

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PSR12' => true,
    '@PhpCsFixer' => true,
    '@PHP81Migration' => true,
    'no_superfluous_phpdoc_tags' => false,
    'phpdoc_no_empty_return' => false,
    'multiline_whitespace_before_semicolons' => true,
    'phpdoc_line_span' => true,
    'array_syntax' => ['syntax' => 'short'],
    'yoda_style' => false,
    'concat_space' => ['spacing' => 'one'],
])->setFinder($finder);
