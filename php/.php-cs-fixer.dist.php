<?php

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__)
    ->exclude('var')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return new PhpCsFixer\Config()
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@PHP8x2Migration' => true,
        '@PHPUnit10x0Migration:risky' => true,

        'types_spaces' => false,
        'single_line_throw' => false,

        // Strictness
        'declare_strict_types' => true,
        'strict_comparison' => true,
        'strict_param' => true,

        // Imports
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'no_unused_imports' => true,
        'single_import_per_statement' => true,

        // Clean code / simplifications
        'array_syntax' => ['syntax' => 'short'],
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'return_assignment' => true,
        'simplified_if_return' => true,
        'simplified_null_return' => true,
        'ternary_to_elvis_operator' => true,
        'ternary_to_null_coalescing' => true,

        // Safer language constructs
        'is_null' => true,
        'logical_operators' => true,
        'modernize_strpos' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unreachable_default_argument_value' => true,
        'non_printable_character' => true,

        // PHPDoc quality
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'no_empty_phpdoc' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_line_span' => ['property' => 'single', 'method' => 'single', 'const' => 'single'],
        'phpdoc_no_package' => true,
        'phpdoc_order' => true,
        'phpdoc_order_by_value' => ['annotations' => ['throws']],
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_to_comment' => false,
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'phpdoc_var_without_name' => true,

        // Types and signatures
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_types' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'void_return' => true,

        // String/concat readability
        'concat_space' => ['spacing' => 'one'],
        'single_quote' => ['strings_containing_single_quote_chars' => true],
    ])
    ->setFinder($finder)
;
