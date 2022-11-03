<?php

/*
 * This file is part of the `src-run/usr-src-builder-dots` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$dotsResolveDirRoot = static function (): string {
    $pathDirectoryJoin = static fn (array $parts): string => implode(DIRECTORY_SEPARATOR, $parts);
    $pathDirectoryList = [__DIR__, '..', '..'];

    while (count($pathDirectoryList) > 1) {
        if (false === realpath($pathDirectoryJoin([...$pathDirectoryList, '.git']))) {
            array_pop($pathDirectoryList);
        } else {
            break;
        }
    }

    return realpath($pathDirectoryJoin($pathDirectoryList)) ?: __DIR__;
};

$dotsResolveGitCall = static function (string $command, array $arguments) use ($dotsResolveDirRoot): string {
    try {
        return ($r = shell_exec(vsprintf(
            sprintf('cd %s && git %s', $dotsResolveDirRoot(), $command),
            array_map(fn (string $a): string => escapeshellarg($a), $arguments)
        ))) ? trim($r) : 'undefined';
    } catch (\Throwable $e) {
        throw new \RuntimeException(sprintf('Failed to call "%s" with the following arguments: %s.', $command, implode(', ', array_map(fn (string $a): string => sprintf('"%s"', $a), $arguments))));
    }
};

$dotsResolveContact = static fn (): string => $dotsResolveGitCall(
    'shortlog --summary --numbered --email | sort -n | tail -n1 | sed -E %s', ['s/(^[0-9\t ]+|[\t ]+$)//g']
);

$dotsResolveProject = static fn (): string => $dotsResolveGitCall(
    'remote get-url --push origin | sed -E %s', ['s/[^:]+:([^/]+\/.+)\.git/\1/g']
);

$dotsResolveHeading = static fn (): string => sprintf(
    <<<'HEADER'
This file is part of the `%s` project.

(c) %s

For the full copyright and license information, please view the LICENSE.md
file that was distributed with this source code.
HEADER,
    $dotsResolveProject(),
    $dotsResolveContact()
);

$dotsFuncsDebugging = static function (): void {
    $funcMappedName = [
        'dotsResolveDirRoot' => 'root dir',
        'dotsResolveContact' => 'author',
        'dotsResolveProject' => 'project',
        'dotsResolveHeading' => 'header',
    ];

    $funcIgnoreList = [
        'dotsFuncsDebugging',
        'dotsResolveGitCall',
    ];

    foreach ($GLOBALS as $index => $value) {
        if ($value instanceof \Closure && str_starts_with($index, 'dots') && !in_array($index, $funcIgnoreList, true)) {
            printf('[DEBUG] %8s -->', $funcMappedName[$index] ?? $index);

            try {
                echo preg_replace(
                    sprintf(
                        '#^%s#',
                        preg_quote($prefix = sprintf('[DEBUG] %8s ---', $funcMappedName[$index] ?? $index), '#')
                    ),
                    '',
                    preg_replace('#^(.*)$#m', sprintf('%s "$1"', $prefix), $value())
                );
            } catch (\Throwable $t) {
                printf('EXCEPTION: "%s"', $t->getMessage());
            }

            printf(PHP_EOL);
        }
    }
};

if (!class_exists(Config::class)) {
    require_once sprintf('%s/vendor/autoload.php', $dotsResolveDirRoot());
}

if (false !== getenv('DEBUG_ENABLE')) {
    $dotsFuncsDebugging();
}

return (new Config())
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setHideProgress(false)
    ->setLineEnding("\n")
    ->setIndent('    ')
    ->setCacheFile('.php-cs-fixer.cache')
    ->setFinder(
        (new Finder())
            ->in($dotsResolveDirRoot())
            ->name('.php-cs-fixer.dist.php')
            ->name('php-cs-fixer.dist.php')
            ->ignoreDotFiles(false)
            ->exclude(['.bldr', 'var', 'vendor'])
    )
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHPUnit57Migration:risky' => true,
        'align_multiline_comment' => [
            'comment_type' => 'phpdocs_like',
        ],
        'array_indentation' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'braces' => [
            'allow_single_line_closure' => true,
        ],
        'binary_operator_spaces' => [
            'operators' => [
                '=>' => 'single_space',
                '=' => 'single_space',
            ],
        ],
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
            ],
        ],
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'concat_space' => [
            'spacing' => 'one',
        ],
        'echo_tag_syntax' => [
            'format' => 'long',
            'long_function' => 'echo',
            'shorten_simple_statements_only' => false,
        ],
        'escape_implicit_backslashes' => true,
        'explicit_indirect_variable' => true,
        'final_internal_class' => true,
        'function_typehint_space' => true,
        'header_comment' => [
            'header' => $dotsResolveHeading(),
            'separate' => 'both',
        ],
        'heredoc_to_nowdoc' => true,
        'linebreak_after_opening_tag' => true,
        'list_syntax' => [
            'syntax' => 'short',
        ],
        'lowercase_cast' => true,
        'mb_str_functions' => true,
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'new_line_for_chained_calls',
        ],
        'native_constant_invocation' => false,
        'native_function_invocation' => false,
        'no_php4_constructor' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'case',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'switch',
                'throw',
                'use',
                'use_trait',
            ],
        ],
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],
        'ordered_imports' => true,
        'php_unit_strict' => true,
        'php_unit_no_expectation_annotation' => true,
        'php_unit_test_class_requires_covers' => true,
        'phpdoc_order' => true,
        'phpdoc_summary' => false,
        'semicolon_after_instruction' => true,
        'short_scalar_cast' => true,
        'single_blank_line_before_namespace' => true,
        'single_line_comment_style' => [
            'comment_types' => [
                'hash',
            ],
        ],
        'single_quote' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => true,
    ])
;
