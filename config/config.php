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

return [
    'volta' => [
       'component' =>  [
           'books' => [

               /* see Settings::getSupportedResources and Settings::registerContentParsers for defaults
               'supportedResources' => [],

               /* see Settings::registerContentParsers for defaults
               'contentParsers' => [ ],

               /* see Settings::setCache() for defaults
               'cache' => [
                   'class' =>
                   'options' => [],
               ],

               /* see BooksController::setDocumentNodeTemplate for defaults
               'template' => ''



               'cache' => [
                   'class' => \Volta\Component\Books\Cache::class,
                   'options' => [
                       'directory' => __DIR__  . '/../__cache/'
                   ],
               ],

                */

               'library' => [
                   'een' => realpath(__DIR__ . '/../resources/ExampleBook'),
                   'twee' => realpath(__DIR__ . '/../resources/ExampleBook2'),
                   'drie' => realpath(__DIR__ . '/../resources/ExampleBook'),
                   'vier' => realpath(__DIR__ . '/../resources/ExampleBook'),
               ],

           ]
       ]
    ],

];