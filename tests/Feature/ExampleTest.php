<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        // La raíz redirige a login si no está autenticado
        $response = $this->get('/');

        $response->assertStatus(302); // Redirect esperado
        $response->assertRedirect(route('login'));
    }
}
