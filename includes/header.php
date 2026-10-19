<?php
// includes/header.php
if (!isset($pageTitle)) {
    $pageTitle = 'Dynamic Template Parser System';
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="styles.css" />

  </head>
  <body>
    <div class="app-shell">
