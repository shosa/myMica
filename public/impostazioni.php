<?php
// impostazioni.php
require 'config/config.php';

$pdo = connectDB();

// Aggiorna le impostazioni se il modulo è stato inviato
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($_POST['giorno_settimana'] as $id => $giorno) {
        $ora_inizio = $_POST['ora_inizio'][$id];
        $ora_fine = $_POST['ora_fine'][$id];
        $giorno_attivo = isset($_POST['giorno_attivo'][$id]) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE impostazioni_calendario SET ora_inizio = ?, ora_fine = ?, giorno_attivo = ? WHERE id = ?");
        $stmt->execute([$ora_inizio, $ora_fine, $giorno_attivo, $id]);
    }

    header("Location: impostazioni.php");
    exit;
}

// Recupera le impostazioni attuali
$stmt = $pdo->query("SELECT * FROM impostazioni_calendario ORDER BY FIELD(giorno_settimana, 'Lunedi', 'Martedi', 'Mercoledi', 'Giovedi', 'Venerdi', 'Sabato', 'Domenica')");
$impostazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impostazioni Calendario</title>
    <link rel="stylesheet" href="css/tailwind-output.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-10 p-2">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-3xl font-bold mb-4">Impostazioni Calendario</h1>

            <form action="impostazioni.php" method="POST">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr>
                            <th class="border border-gray-300 px-4 py-2">Giorno della Settimana</th>
                            <th class="border border-gray-300 px-4 py-2">Ora Inizio</th>
                            <th class="border border-gray-300 px-4 py-2">Ora Fine</th>
                            <th class="border border-gray-300 px-4 py-2">Attivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($impostazioni as $impostazione): ?>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2">
                                    <?php echo htmlspecialchars($impostazione['giorno_settimana']); ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-2">
                                    <input type="time" name="ora_inizio[<?php echo $impostazione['id']; ?>]" value="<?php echo htmlspecialchars($impostazione['ora_inizio']); ?>" class="p-2 border border-gray-300 rounded-md w-full">
                                </td>
                                <td class="border border-gray-300 px-4 py-2">
                                    <input type="time" name="ora_fine[<?php echo $impostazione['id']; ?>]" value="<?php echo htmlspecialchars($impostazione['ora_fine']); ?>" class="p-2 border border-gray-300 rounded-md w-full">
                                </td>
                                <td class="border border-gray-300 px-4 py-2 text-center">
                                    <input type="checkbox" name="giorno_attivo[<?php echo $impostazione['id']; ?>]" <?php echo $impostazione['giorno_attivo'] ? 'checked' : ''; ?> class="form-checkbox h-5 w-5 text-blue-600">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md mt-4 hover:bg-blue-600">Salva Impostazioni</button>
            </form>
        </div>
    </div>
</body>
</html>
