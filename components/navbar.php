<!-- SIDEBAR -->
<?php
$colore = "indigo";

// Verifica se la sessione contiene lo stato della navbar, altrimenti imposta un valore predefinito
if (!isset($_SESSION['navbar_toggled'])) {
    $_SESSION['navbar_toggled'] = false; // Valore predefinito
}

// Gestione della modifica dello stato della navbar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_navbar'])) {
    $_SESSION['navbar_toggled'] = $_POST['toggle_navbar'] === 'true';
}
?>
<ul class="navbar-nav shadow bg-gradient-<?php echo $colore; ?> sidebar sidebar-dark accordion <?php echo $_SESSION['navbar_toggled'] ? '' : 'toggled'; ?>"
    id="accordionSidebar">
    <!-- SIDEBAR INTESTAZIONE -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo BASE_URL ?>/index">
        <div class="sidebar-brand-icon">
            <img src="<?php echo BASE_URL ?>/img/roundLogo.png" alt="" width="40" height="40">
        </div>

    </a>

    <!-- DIVISORE -->
    <hr class="sidebar-divider my-0">
    <li class="nav-item">
        <a id="home" class="nav-link" href="<?php echo BASE_URL ?>/index">
            <i class="fas fa-fw fa-home"></i>
            <span>Dashboard</span></a>
    </li>

    <!-- DIVISORE -->
    <hr class="sidebar-divider">

    <!-- TITOLO SEZIONE -->

    <li class="nav-item">
        <a id="calendario" class="nav-link collapsed" href="<?php echo BASE_URL ?>/functions/calendar/calendario"
            aria-expanded="true" aria-controls="collapseProd">
            <i class="fa fa-calendar"></i>
            <span>Calendario</span>
        </a>
    </li>
    <hr class="sidebar-divider">
    <li class="nav-item">
        <a id="servizi" class="nav-link collapsed" href="<?php echo BASE_URL ?>/functions/services/services"
            aria-expanded="true" aria-controls="collapseProd">
            <i class="far fa-spa"></i>
            <span>Servizi</span>
        </a>
    </li>
    <hr class="sidebar-divider">
    <li class="nav-item">
        <a id="clienti" class="nav-link collapsed" href="<?php echo BASE_URL ?>/functions/customers/customers"
            aria-expanded="true" aria-controls="collapseProd">
            <i class="far fa-users"></i>
            <span>Clienti</span>
        </a>
    </li>
    <hr class="sidebar-divider">
    <li class="nav-item">
        <a id="statistiche" class="nav-link collapsed" href="<?php echo BASE_URL ?>/functions/statistics/home"
            aria-expanded="true" aria-controls="collapseProd">
            <i class="far fa-chart-pie"></i>
            <span>Statistiche</span>
        </a>
    </li>
    <hr class="sidebar-divider">
</ul>
<style>
    .border-width-6 {
        border-width: 6px !important;

    }

    .nav-item i {
        font-size: 1.5rem;
    }

    .nav-item .active {
        background-color: #9353fc !important;
        color: white !important;
        border-left: solid 6pt white;
        border-radius: 0 10px 10px 0;
        width: 95% !important;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sidebarToggleButton = document.getElementById('sidebarToggle');

        if (sidebarToggleButton) {
            sidebarToggleButton.addEventListener('click', function () {
                var isToggled = document.getElementById('accordionSidebar').classList.contains('toggled');

                // Invia una richiesta AJAX al server per aggiornare lo stato della navbar
                var xhr = new XMLHttpRequest();
                xhr.open('POST', window.location.href, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send('toggle_navbar=' + !isToggled);
            });
        }
        var currentUrl = window.location.pathname;

        // Rimuove eventuali prefissi e normalizza l'URL
        currentUrl = currentUrl.replace(/\/$/, ''); // Rimuove l'eventuale barra finale

        // Mappa degli URL alle classi degli elementi di navigazione
        var navLinks = {
            '/index': 'home',
            '/functions/calendar/calendario': 'calendario',
            '/functions/services/services': 'servizi',
            '/functions/customers/customers': 'clienti',
            '/functions/statistics/home': 'statistiche',


            // Aggiungi qui altri link come necessario
        };

        // Controlla se l'URL corrente corrisponde a uno degli URL nel menu
        for (var url in navLinks) {
            if (navLinks.hasOwnProperty(url) && currentUrl.includes(url)) {
                var navItem = document.getElementById(navLinks[url]);
                if (navItem) {
                    navItem.classList.add('active');
                    var parentNavLink = navItem.closest('.nav-item').querySelector('.nav-link');
                    if (parentNavLink) {
                        parentNavLink.classList.remove('collapsed');
                    }
                    var parentCollapse = navItem.closest('.collapse');
                    var navbarNav = document.getElementById('accordionSidebar');
                    if (parentCollapse && (!navbarNav || !navbarNav.classList.contains('toggled'))) {
                        parentCollapse.classList.add('show');
                    }
                }
            }
        }

        // Gestione speciale per la dashboard
        if (currentUrl.endsWith('/index') || currentUrl === '/index' || currentUrl === '/' || currentUrl.endsWith('/index.php')) {
            var homeNavItem = document.getElementById('home');
            if (homeNavItem) {
                homeNavItem.classList.add('active');
                homeNavItem.classList.remove('text-<?php echo $colore; ?>');
            }
        }
    });

</script>

<!-- FINE SIDEBAR -->