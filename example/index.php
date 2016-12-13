<?php session_start() ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Connect client example</title>
</head>
<body>
<p><a href="/resource.php">My resource</a></p>
<?php var_dump($_SESSION); ?>
</body>
</html>
