<footer>
    <div class="footer-content">
        <div>© <?php echo date('Y'); ?> Cotreball</div>
        <div class="footer-links">
            <a href="/legal/terms.php">Términos y Condiciones</a>
            <a href="/legal/privacy.php">Política de Privacidad</a>
        </div>
    </div>
</footer>

<!-- Scripts comunes -->
<script src="/assets/js/main.js"></script>
<?php if (isset($scripts)): ?>
    <?php foreach ($scripts as $script): ?>
        <script src="<?php echo $script; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<script>
    // Menú hamburguesa
    document.querySelector('.menu-toggle').addEventListener('click', function() {
        document.querySelector('.header-right').classList.toggle('active');
    });

    // Cerrar menú al hacer clic en un enlace
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                document.querySelector('.header-right').classList.remove('active');
            }
        });
    });

    // Cerrar menú al redimensionar la ventana
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            document.querySelector('.header-right').classList.remove('active');
        }
    });
</script>
</body>
</html> 