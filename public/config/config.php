<?php
// config.php

function connectDB() {
    $host = 'localhost'; // O l'hostname del tuo server MySQL
    $dbname = 'my_mybeautyagenda';
    $username = 'root'; // Sostituisci con il tuo username del database
    $password = ''; // Sostituisci con la tua password del database

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Connessione al database fallita: " . $e->getMessage());
    }
}
?>
