<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('somedir')
    ->notPath('src/Symfony/Component/Translation/Tests/fixtures/resources.php')
    ->in(__DIR__);

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PSR12' => true,
    '@PhpCsFixer' => true,
    'strict_param' => true,
    'no_superfluous_phpdoc_tags' => false,
    'phpdoc_no_empty_return' => false,
    'array_syntax' => ['syntax' => 'short'],
])->setFinder($finder);
