<script src="<?php echo BASE_URL; ?>/vendor/jquery/jquery.min.js"></script>
<script src="<?php echo BASE_URL; ?>/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="<?php echo BASE_URL; ?>/js/sb-admin-2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?php echo BASE_URL; ?>/service-worker.js')
                .then((registration) => {
                    console.log('Service Worker registrato:', registration.scope);
                })
                .catch((error) => {
                    console.error('Registrazione Service Worker fallita:', error);
                });
        });
    }
</script>