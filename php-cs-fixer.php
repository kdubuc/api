<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

// All php files
$finder = Finder::create()->in(__DIR__);

// Set of rules
$rules = [
    'psr0'                   => true,
    '@PSR1'                  => true,
    '@PSR2'                  => true,
    '@Symfony'               => true,
    '@Symfony:risky'         => true,
    '@PHP71Migration'        => true,
    'binary_operator_spaces' => [
        'align_double_arrow' => true,
        'align_equals'       => true,
    ],
    'array_syntax' => [
        'syntax' => 'short',
    ],
    'combine_consecutive_unsets'       => true,
    'general_phpdoc_annotation_remove' => [
        'param',
        'return',
    ],
    'is_null' => [
        'use_yoda_style' => true,
    ],
    'linebreak_after_opening_tag' => true,
    'list_syntax'                 => [
        'syntax' => 'short',
    ],
    'mb_str_functions' => true,
    'ordered_imports'  => [
        'sortAlgorithm' => 'length',
    ],
    'return_type_declaration' => [
        'space_before' => 'one',
    ],
    'semicolon_after_instruction' => true,
    'silenced_deprecation_error'  => false,
];

return Config::create()
    ->setFinder($finder)
    ->setRules($rules)
    ->setRiskyAllowed(true);
