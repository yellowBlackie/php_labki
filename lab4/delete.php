<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare('DELETE FROM workouts WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

header('Location: index.php');
exit;
?>