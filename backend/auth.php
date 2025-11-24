<?php
declare(strict_types=1);

// Uso de password_hash / password_verify
function crearHashPassword(string $password): string {
    // PASSWORD_DEFAULT usa el algoritmo más seguro disponible;
    // permite actualizaciones automáticas cuando cambie el algoritmo.
    return password_hash($password, PASSWORD_DEFAULT);
}

function verificarPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

// Helper para timing-attack safe string compare
function hashEquals(string $known, string $user): bool {
    return hash_equals($known, $user);
}
