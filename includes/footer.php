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
<?php if (isset($scripts)): ?>
    <?php foreach ($scripts as $script): ?>
        <script src="<?php echo $script; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html> 