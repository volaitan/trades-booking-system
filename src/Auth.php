<?php
declare(strict_types=1);

namespace App;

use PDO;

/** Session-based admin login with bcrypt/argon passwords and basic brute-force protection. */
final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 300;
    // Real hash of a throwaway password, so unknown emails take as long to check as real ones.
    private const DUMMY_HASH = '$2y$10$xvLMoLOLUnRDd4npDKwHX.hHWtxq2O9/gy88uYyK1yII5d7zbQH4a';

    public function __construct(private readonly PDO $db)
    {
    }

    public function createUser(string $email, string $name, string $password): int
    {
        if (strlen($password) < 10) {
            throw new ValidationException(['password' => 'Password must be at least 10 characters.']);
        }
        $this->db->prepare('INSERT INTO users (email, name, password_hash) VALUES (?, ?, ?)')
            ->execute([strtolower(trim($email)), trim($name), password_hash($password, PASSWORD_DEFAULT)]);
        return (int) $this->db->lastInsertId();
    }

    /** @param array<string, mixed> $session the session array (passed by reference so tests can use a plain array) */
    public function attempt(array &$session, string $email, string $password, int $now): bool
    {
        $attempts = $session['login_attempts'] ?? ['count' => 0, 'since' => $now];
        if ($attempts['count'] >= self::MAX_ATTEMPTS && $now - $attempts['since'] < self::LOCKOUT_SECONDS) {
            throw new ValidationException(['email' => 'Too many attempts. Please wait a few minutes and try again.']);
        }
        if ($now - $attempts['since'] >= self::LOCKOUT_SECONDS) {
            $attempts = ['count' => 0, 'since' => $now];
        }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch();

        // Always run password_verify so response time doesn't reveal whether the email exists.
        $hash = $user['password_hash'] ?? self::DUMMY_HASH;
        if (!password_verify($password, $hash) || !$user) {
            $attempts['count']++;
            $session['login_attempts'] = $attempts;
            return false;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true); // prevent session fixation
        }
        unset($session['login_attempts']);
        $session['user'] = ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']];
        return true;
    }

    /** @param array<string, mixed> $session */
    public static function user(array $session): ?array
    {
        return $session['user'] ?? null;
    }
}
