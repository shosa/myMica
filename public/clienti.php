<?php
// clienti.php
require 'config/config.php';

$pdo = connectDB();

// Aggiungi un nuovo cliente se è stato inviato il modulo
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['nome_cliente']) && isset($_POST['numero_telefono'])) {
    $nome_cliente = $_POST['nome_cliente'];
    $numero_telefono = $_POST['numero_telefono'];

    $stmt = $pdo->prepare("INSERT INTO clienti (nome_cliente, numero_telefono) VALUES (?, ?)");
    $stmt->execute([$nome_cliente, $numero_telefono]);

    header("Location: clienti.php"); // Ricarica la pagina per evitare reinvio modulo
    exit;
}

// Recupera tutti i clienti
$stmt = $pdo->query("SELECT * FROM clienti ORDER BY nome_cliente");
$clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rubrica Clienti</title>
    <link rel="stylesheet" href="css/tailwind-output.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-10 p-2">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-3xl font-bold mb-4">Rubrica Clienti</h1>

            <form action="clienti.php" method="POST" class="mb-6">
                <div class="mb-4">
                    <label for="nome_cliente" class="block text-sm font-medium text-gray-700">Nome Cliente</label>
                    <input type="text" name="nome_cliente" id="nome_cliente" required class="mt-1 p-2 block w-full border border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                    <label for="numero_telefono" class="block text-sm font-medium text-gray-700">Numero di Telefono</label>
                    <input type="text" name="numero_telefono" id="numero_telefono" required class="mt-1 p-2 block w-full border border-gray-300 rounded-md">
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Aggiungi Cliente</button>
            </form>

            <h2 class="text-2xl font-bold mb-4">Elenco Clienti</h2>
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr>
                        <th class="border border-gray-300 px-4 py-2">ID</th>
                        <th class="border border-gray-300 px-4 py-2">Nome</th>
                        <th class="border border-gray-300 px-4 py-2">Numero di Telefono</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clienti as $cliente): ?>
                        <tr>
                            <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($cliente['id_cliente']); ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($cliente['nome_cliente']); ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($cliente['numero_telefono']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
