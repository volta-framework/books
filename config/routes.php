<?php
/*
 * This file is part of the Volta package.
 *
 * (c) Rob Demmenie <rob@volta-framework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

use Volta\Component\Books\Controllers\BooksController;

return [
    [
        'methods'  => ['GET'],
        'pattern'  =>  BooksController::getUriOffset() .'[/{bookIndex}[/{bookNode:.*}]]',
        'callable' => BooksController::class,
        'name'     => 'BooksController'
    ],
];
