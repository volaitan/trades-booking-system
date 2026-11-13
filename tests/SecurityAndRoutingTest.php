<?php
declare(strict_types=1);

namespace Tests;

use App\Csrf;
use App\Router;
use App\ValidationException;

final class SecurityAndRoutingTest extends TestCase
{
    public function testCsrfTokens(): void
    {
        $session = [];
        $token = Csrf::token($session);
        $this->assertSame(64, strlen($token));
        $this->assertSame($token, Csrf::token($session)); // stable within a session
        $this->assertTrue(Csrf::valid($session, $token));
        $this->assertFalse(Csrf::valid($session, 'forged'));
        $this->assertFalse(Csrf::valid($session, null));
        $this->assertFalse(Csrf::valid([], $token));
    }

    public function testLoginSuccessAndFailure(): void
    {
        $this->app->auth->createUser('Admin@Example.com', 'Admin', 'correct-horse-battery');
        $session = [];
        $this->assertFalse($this->app->auth->attempt($session, 'admin@example.com', 'wrong-password', 1000));
        $this->assertFalse($this->app->auth->attempt($session, 'nobody@example.com', 'whatever', 1000));
        $this->assertTrue($this->app->auth->attempt($session, 'ADMIN@example.com', 'correct-horse-battery', 1000));
        $this->assertSame('admin@example.com', $session['user']['email']);
        $this->assertArrayNotHasKey('login_attempts', $session);
    }

    public function testLockoutAfterRepeatedFailures(): void
    {
        $this->app->auth->createUser('admin@example.com', 'Admin', 'correct-horse-battery');
        $session = [];
        for ($i = 0; $i < 5; $i++) {
            $this->app->auth->attempt($session, 'admin@example.com', 'nope', 1000);
        }
        try {
            $this->app->auth->attempt($session, 'admin@example.com', 'correct-horse-battery', 1060);
            $this->fail('Expected lockout');
        } catch (ValidationException) {
            $this->assertArrayNotHasKey('user', $session);
        }
        // After five minutes the lock lifts
        $this->assertTrue($this->app->auth->attempt($session, 'admin@example.com', 'correct-horse-battery', 1301));
    }

    public function testShortPasswordsRejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->app->auth->createUser('a@example.com', 'A', 'short');
    }

    public function testPasswordsAreHashed(): void
    {
        $this->app->auth->createUser('a@example.com', 'A', 'correct-horse-battery');
        $hash = $this->app->db->query('SELECT password_hash FROM users')->fetchColumn();
        $this->assertStringNotContainsString('correct-horse', $hash);
        $this->assertTrue(password_verify('correct-horse-battery', $hash));
    }

    public function testRouterMatchesParamsAndMethods(): void
    {
        $router = new Router();
        $router->get('/', static fn () => 'home');
        $router->get('/admin/quotes/new', static fn () => 'new');
        $router->get('/admin/quotes/{id}', static fn (string $id) => "quote $id");
        $router->post('/admin/quotes/{id}/invoice', static fn (string $id) => "invoice $id");

        [$handler, $params] = $router->match('GET', '/admin/quotes/42?tab=items');
        $this->assertSame('quote 42', $handler(...array_values($params)));
        $this->assertSame('new', $router->match('GET', '/admin/quotes/new')[0]());
        $this->assertSame('home', $router->match('GET', '/')[0]());
        $this->assertNull($router->match('GET', '/admin/quotes/abc'));
        $this->assertNull($router->match('GET', '/admin/quotes/1/invoice'));
        $this->assertTrue($router->allowsOtherMethod('GET', '/admin/quotes/1/invoice'));
    }

    public function testTemplateEscaping(): void
    {
        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', e('<script>alert("x")</script>'));
    }
}
