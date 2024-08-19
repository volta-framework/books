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

/*
 * This file will  be included to load the routes. Assumed to be part of a slim application
 * We could test if the app is available but it seems a bit redundant as this file will only be included
 * when using the slim framework.
 *
 * The placeholders "bookIndex" and "bookNode" are optional in the uri but not in the route pattern.
 * The offset is optional in the uri and pattern. It may contain multiple slugs. i.e. "/books/horror" or just "/books"
 */
$app->get(
    pattern: BooksController::getUriOffset() .'[/{bookIndex}[/{bookNode:.*}]]',
    callable: BooksController::class
)->setName(BooksController::getUriOffset());

