<!-- Topbar Search -->
<?php
// Puoi aggiungere qui la tua logica PHP, se necessario.
?>
<style>
    .navbar-brand {
        flex: 1;
        text-align: center;
    }

    .topbar {
        display: flex;
        justify-content: center;
        align-items: center;
    }
</style>
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle">
        <i class="fa fa-bars text-indigo"></i>
    </button>

    <!-- Contenitore per il testo centrato -->
    <div class="navbar-brand mx-auto text-center">
        <a href="<?php echo BASE_URL ?>/index" class="h3 text-indigo mb-0"
            style="margin-left:-6% !important; text-decoration: none !important;">MyMica</a>
    </div>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">
        <!-- Nav Item - Notifications -->
        <div class="topbar-divider d-none d-sm-block"></div>
    </ul>
</nav>