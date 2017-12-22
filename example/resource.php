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
    <code><?php var_dump($t) ?></code>

    <?php var_dump($token->validate($t['token'])); ?>

    <?php $t = $token->createApplicationToken(
        $connect->getSaml()->getMetadata()->getServiceProvider()->getEntityID(),
        $connect->getSaml()->getPrivateKey()
    ); ?>
    <code><?php echo var_dump($t) ?></code>

    <?php var_dump($token->validate($t['token'])); ?>

    <?php var_dump($_SESSION) ?>
    <?php var_dump($connect->getUser()) ?>
    <?php var_dump($connect->getSessionIndex()) ?>
</body>
</html>
