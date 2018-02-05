<?php declare(strict_types=1); # -*- coding: utf-8 -*-
/*
 * This file is part of the php-coding-standards package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This file contains code from "phpcs-neutron-standard" repository
 * found at https://github.com/Automattic/phpcs-neutron-standard
 * Copyright (c) Automattic
 * released under MIT license.
 */

namespace Inpsyde\InpsydeCodingStandard\Sniffs\CodeQuality;

use Inpsyde\InpsydeCodingStandard\Helpers;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class ArgumentTypeDeclarationSniff implements Sniff
{
    const TYPE_CODES = [
        T_STRING,
        T_ARRAY_HINT,
        T_CALLABLE,
        T_SELF,
    ];

    /**
     * @inheritdoc
     */
    public function register()
    {
        return [T_FUNCTION, T_CLOSURE];
    }

    /**
     * @inheritdoc
     */
    public function process(File $file, $position)
    {
        if (Helpers::isHookClosure($file, $position) || Helpers::isHookFunction($file, $position)) {
            return;
        }

        $tokens = $file->getTokens();

        $paramsStart = $tokens[$position]['parenthesis_opener'] ?? 0;
        $paramsEnd = $tokens[$position]['parenthesis_closer'] ?? 0;

        if (!$paramsStart || !$paramsEnd || $paramsStart >= ($paramsEnd - 1)) {
            return;
        }

        $variables = Helpers::filterTokensByType($paramsStart, $paramsEnd, $file, T_VARIABLE);

        foreach (array_keys($variables) as $varPosition) {
            $typePosition = $file->findPrevious(
                [T_WHITESPACE, T_ELLIPSIS],
                $varPosition - 1,
                $paramsStart + 1,
                true
            );

            $type = $tokens[$typePosition] ?? null;
            if ($type && !in_array($type['code'], self::TYPE_CODES, true)) {
                $file->addWarning('Argument type is missing', $position, 'NoArgumentType');
            }
        }
    }

    /**
     * @param File $file
     * @param int $position
     * @param array $tokens
     * @return bool
     */
    private function isAddHook(File $file, int $position, array $tokens): bool
    {
        $lookForComma = $file->findPrevious(
            [T_WHITESPACE],
            $position - 1,
            null,
            true,
            null,
            true
        );

        if (!$lookForComma || ($tokens[$lookForComma]['code'] ?? '') !== T_COMMA) {
            return false;
        }

        $functionCallOpen = $file->findPrevious(
            [T_OPEN_PARENTHESIS],
            $lookForComma - 2,
            null,
            false,
            null,
            true
        );

        if (!$functionCallOpen) {
            return false;
        }

        $functionCall = $file->findPrevious(
            [T_WHITESPACE],
            $functionCallOpen - 1,
            null,
            true,
            null,
            true
        );

        return in_array(
            ($tokens[$functionCall]['content'] ?? ''),
            ['add_action', 'add_filter'],
            true
        );
    }
}
