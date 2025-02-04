<?php /*
 * This file is part of the Volta package.
 *
 * (c) Rob Demmenie <rob@volta-framework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Volta\Component\Books;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\LoggerTrait;
use Stringable;

class Logger implements LoggerInterface
{
    use  LoggerTrait;

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $w = 120;
        switch ($level) {
            case LogLevel::CRITICAL: // no break
            case LogLevel::ERROR   : fwrite(STDOUT, sprintf("\n\t\e[31m%s\e[0m\n", wordwrap($message, $w, "\n\t"))); break;
            case LogLevel::ALERT   : // no break;
            case LogLevel::WARNING : fwrite(STDERR, sprintf("\n\t\e[33m%s\e[0m\n", wordwrap($message, $w, "\n\t"))); break;

            case LogLevel::DEBUG   : fwrite(STDOUT, sprintf("\e[32m%-10s%s\e[0m\n", $level, wordwrap($message, $w, "\n\t"))); break;

            default:                 fwrite(STDOUT, sprintf("%-10s%s\n", $level, wordwrap($message, $w, "\n           ")));
        }
        if (!empty($context[0])) {
            fwrite(STDOUT, sprintf("         \e[3m\e[90m (%s) \e[0m\n", $context[0]));
        }
    }
}
