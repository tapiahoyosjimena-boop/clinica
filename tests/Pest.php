<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Caso de prueba
|--------------------------------------------------------------------------
|
| El closure que proporcionas a tus funciones de prueba siempre se vincula a
| una clase de caso de prueba PHPUnit concreta. Por defecto, esa clase es
| "PHPUnit\Framework\TestCase". Puedes cambiarla con la función "pest()"
| para vincular otras clases o traits.
|
*/

pest()->extend(TestCase::class)
    ->in('Feature');
