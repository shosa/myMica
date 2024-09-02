<?php
// servizi.php
require 'config/config.php';

$pdo = connectDB();

// Aggiungi un nuovo servizio se è stato inviato il modulo
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['nome_servizio']) && isset($_POST['tempo_medio']) && isset($_POST['costo'])) {
    $nome_servizio = $_POST['nome_servizio'];
    $tempo_medio = (int) $_POST['tempo_medio'];
    $costo = (float) $_POST['costo'];

    $stmt = $pdo->prepare("INSERT INTO servizi (nome_servizio, tempo_medio, costo) VALUES (?, ?, ?)");
    $stmt->execute([$nome_servizio, $tempo_medio, $costo]);

    header("Location: servizi.php"); // Ricarica la pagina per evitare reinvio modulo
    exit;
}

// Recupera tutti i servizi
$stmt = $pdo->query("SELECT * FROM servizi ORDER BY nome_servizio");
$servizi = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elenco Servizi</title>
    <link rel="stylesheet" href="css/tailwind-output.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-10 p-2">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-3xl font-bold mb-4">Elenco Servizi</h1>

            <form action="servizi.php" method="POST" class="mb-6">
                <div class="mb-4">
                    <label for="nome_servizio" class="block text-sm font-medium text-gray-700">Nome Servizio</label>
                    <input type="text" name="nome_servizio" id="nome_servizio" required class="mt-1 p-2 block w-full border border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                    <label for="tempo_medio" class="block text-sm font-medium text-gray-700">Tempo Medio (minuti)</label>
                    <input type="number" name="tempo_medio" id="tempo_medio" required class="mt-1 p-2 block w-full border border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                    <label for="costo" class="block text-sm font-medium text-gray-700">Costo (€)</label>
                    <input type="number" step="0.01" name="costo" id="costo" required class="mt-1 p-2 block w-full border border-gray-300 rounded-md">
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Aggiungi Servizio</button>
            </form>

            <h2 class="text-2xl font-bold mb-4">Servizi Offerti</h2>
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr>
                        <th class="border border-gray-300 px-4 py-2">ID</th>
                        <th class="border border-gray-300 px-4 py-2">Nome Servizio</th>
                        <th class="border border-gray-300 px-4 py-2">Tempo Medio (min)</th>
                        <th class="border border-gray-300 px-4 py-2">Costo (€)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servizi as $servizio): ?>
                        <tr>
                            <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($servizio['id_servizio']); ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($servizio['nome_servizio']); ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($servizio['tempo_medio']); ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars(number_format($servizio['costo'], 2)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
