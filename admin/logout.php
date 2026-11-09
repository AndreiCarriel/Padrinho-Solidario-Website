<?php require __DIR__.'/bootstrap.php'; unset($_SESSION['admin_id'],$_SESSION['admin_nome']); session_regenerate_id(true); header('Location: login.php'); exit;
