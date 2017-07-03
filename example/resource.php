<?php
include __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My resource</title>
</head>
<body>
    <p>My resource !</p>
    <p><a href="/logout.php">Logout</a></p>

    <?php $t = $token->create($connect); ?>
    <code><?= htmlspecialchars($t, ENT_QUOTES|ENT_SUBSTITUTE) ?></code>

    <?php
        var_dump($token->validate($t));
    ?>

    <?php var_dump($_SESSION) ?>
    <?php var_dump($connect->getUser()) ?>
</body>
</html>
