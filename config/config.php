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
                   'ExampleBook' => realpath(__DIR__ . '/../resources/ExampleBook'),
                   'ExampleBook2' => realpath(__DIR__ . '/../resources/ExampleBook2'),
                   'GuideToVoltaBook' => realpath(__DIR__ . '/../resources/Guide-To-Volta-Books'),
               ],
           ]
       ]
    ],

];