<?php

// A sala de espera agora é a primeira fase da tela jogar.php.

$gameCode = preg_replace('/\D/', '', $_GET['code'] ?? '');

header('Location: /jogar.php?code=' . urlencode($gameCode));

exit;
