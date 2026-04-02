<?php
declare(strict_types=1);

$password = $_GET['p'] ?? '123456';

echo "<h3>Password:</h3><pre>" . htmlspecialchars($password) . "</pre>";
echo "<h3>Hash:</h3><pre>" . password_hash($password, PASSWORD_DEFAULT) . "</pre>";
