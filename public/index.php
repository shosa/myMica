<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro Estetico - Agenda</title>
    <link rel="stylesheet" href="css/tailwind-output.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
</head>
<body class="bg-gray-100">

    <div class="container mx-auto mt-10 p-2">
        <div class="bg-white shadow-md rounded-lg">
            <div class="p-6">
                <h1 class="text-3xl font-bold mb-4">Centro Estetico - Agenda</h1>
                <p class="mb-6">Benvenuti nel sistema di gestione del centro estetico. Utilizza il menu qui sotto per navigare.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    <!-- Menu -->
                    <a href="calendario" class="block p-6 bg-blue-500 text-white rounded-lg shadow-md hover:bg-blue-600">
                        <span class="material-icons align-middle">today</span>
                        <span class="ml-2 align-middle">Calendario</span>
                    </a>
                    <a href="#" class="block p-6 bg-green-500 text-white rounded-lg shadow-md hover:bg-green-600">
                        <span class="material-icons align-middle">event</span>
                        <span class="ml-2 align-middle">Appuntamenti</span>
                    </a>
                    <a href="clienti" class="block p-6 bg-yellow-500 text-white rounded-lg shadow-md hover:bg-yellow-600">
                        <span class="material-icons align-middle">person</span>
                        <span class="ml-2 align-middle">Clienti</span>
                    </a>
                    <a href="servizi" class="block p-6 bg-purple-500 text-white rounded-lg shadow-md hover:bg-purple-600">
                        <span class="material-icons align-middle">insights</span>
                        <span class="ml-2 align-middle">Servizi</span>
                    </a>
                    <a href="impostazioni" class="block p-6 bg-red-500 text-white rounded-lg shadow-md hover:bg-red-600">
                        <span class="material-icons align-middle">settings</span>
                        <span class="ml-2 align-middle">Impostazioni</span>
                    </a>
                    
                </div>
            </div>
        </div>
    </div>

</body>
</html>
