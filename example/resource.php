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
    <?php var_dump($_SESSION) ?>
    <?php var_dump($_SESSION['user']['attributions']) ?>
    <?php var_dump($connect) ?>
</body>
</html>
