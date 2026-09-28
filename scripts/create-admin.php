<?php

$pdo = require __DIR__ . '/../app/config/database.php';

echo PHP_EOL;
echo "CRIAR ADMINISTRADOR" . PHP_EOL;
echo PHP_EOL;

echo "Nome: ";
$name = trim(fgets(STDIN));

echo "E-mail: ";
$email = trim(fgets(STDIN));

echo "Senha: ";
$password = trim(fgets(STDIN));

if (
    $name === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($password) < 8
) {
    exit(
        PHP_EOL
        . "Dados inválidos. A senha deve possuir pelo menos 8 caracteres."
        . PHP_EOL
    );
}

$statement = $pdo->prepare(
    'SELECT id
     FROM administradores
     WHERE email = :email
     LIMIT 1'
);

$statement->execute([
    'email' => $email,
]);

if ($statement->fetch()) {
    exit(PHP_EOL . "Já existe um administrador com esse e-mail." . PHP_EOL);
}

$statement = $pdo->prepare(
    'INSERT INTO administradores (
        nome,
        email,
        senha_hash,
        ativo
    ) VALUES (
        :nome,
        :email,
        :senha_hash,
        1
    )'
);

$statement->execute([
    'nome' => $name,
    'email' => $email,
    'senha_hash' => password_hash(
        $password,
        PASSWORD_DEFAULT
    ),
]);

echo PHP_EOL;
echo "Administrador criado com sucesso!" . PHP_EOL;
echo PHP_EOL;