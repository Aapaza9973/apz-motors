<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz redirige a la pantalla de login para visitantes no autenticados.
     */
    public function test_la_raiz_redirige_al_login_sin_autenticacion(): void
    {
        $this->get('/')->assertRedirect('/login');
    }
}
