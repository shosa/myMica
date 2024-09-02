<?php
include("../../config/config.php");
session_start();
$pdo = getDbInstance();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome_cliente = $_POST['nome_cliente'];
    $telefono_cliente = $_POST['telefono_cliente'] ?: null;

    if (!empty($nome_cliente)) {
        $stmt = $pdo->prepare("INSERT INTO clienti (nome_cliente, numero_telefono) VALUES (?, ?)");
        $stmt->execute([$nome_cliente, $telefono_cliente]);

        // Recupera l'ID del cliente appena inserito
        $id_cliente = $pdo->lastInsertId();

        // Restituisce una risposta JSON con l'ID e il nome del cliente
        echo json_encode([
            'success' => true,
            'id_cliente' => $id_cliente,
            'nome_cliente' => $nome_cliente
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Il nome del cliente è obbligatorio.'
        ]);
    }
}
