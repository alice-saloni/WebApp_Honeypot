<?php
function isActive($page) {
    return (basename($_SERVER['PHP_SELF']) === $page) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bug Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
