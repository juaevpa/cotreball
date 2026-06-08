<footer class="bg-primary-900 text-white mt-auto">
    <!-- City links section -->
    <div class="max-w-7xl mx-auto px-4 py-12">
        <h3 class="text-lg font-semibold text-center mb-6 text-primary-300">
            Espacios de coworking por ciudad
        </h3>
        <div class="flex flex-wrap justify-center gap-2">
            <?php
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->query("
                SELECT DISTINCT city, COUNT(*) as count
                FROM spaces
                WHERE approved = 1
                GROUP BY city
                ORDER BY count DESC, city ASC
            ");
            $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($cities as $city) {
                $citySlug = strtolower(str_replace(' ', '-', trim($city['city'])));
                $cityName = htmlspecialchars($city['city']);
                echo "<a href='/$citySlug' class='inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm bg-white/10 hover:bg-white/20 transition-colors'>";
                echo "Coworking en $cityName";
                echo " <span class='text-primary-400 text-xs'>({$city['count']})</span>";
                echo "</a>";
            }
            ?>
        </div>
    </div>
    <!-- Para propietarios -->
    <div class="max-w-7xl mx-auto px-4 pb-8">
        <div class="border border-white/10 rounded-xl p-6 text-center">
            <p class="text-primary-300 mb-3">¿Gestionas un espacio de coworking?</p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="<?php echo isset($_SESSION['user_id']) ? '/admin/spaces/create' : '/auth/register?intent=space'; ?>"
                   class="inline-flex items-center gap-2 px-6 py-2.5 bg-secondary-500 hover:bg-secondary-400
                          text-white font-medium rounded-lg transition-colors text-sm">
                    <i class="fas fa-plus-circle"></i> Publica tu espacio gratis
                </a>
                <a href="/premium" class="inline-flex items-center gap-2 px-6 py-2.5 border border-amber-400/30
                          text-amber-300 hover:bg-amber-400/10 font-medium rounded-lg transition-colors text-sm">
                    <i class="fas fa-crown"></i> Destaca con Premium
                </a>
            </div>
        </div>
    </div>
    <!-- Bottom bar -->
    <div class="border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col sm:flex-row justify-between
                    items-center gap-2 text-sm text-primary-400">
            <p>&copy; <?php echo date('Y'); ?> Cotreball</p>
            <div class="flex flex-wrap items-center justify-center gap-4">
                <a href="/legal/terms" class="hover:text-white transition-colors">
                    Términos y Condiciones</a>
                <a href="/legal/privacy" class="hover:text-white transition-colors">
                    Política de Privacidad</a>
                <span>Desarrollo web: <a href="https://udista.com" target="_blank" rel="noopener"
                    class="hover:text-white transition-colors">udista.com</a></span>
            </div>
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
    document.querySelector('.menu-toggle')?.addEventListener('click', function() {
        document.querySelector('.header-right')?.classList.toggle('active');
    });

    // Cerrar menú al hacer clic en un enlace
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 1024) {
                document.querySelector('.header-right')?.classList.remove('active');
            }
        });
    });

    // Cerrar menú al redimensionar la ventana
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024) {
            document.querySelector('.header-right')?.classList.remove('active');
        }
    });
</script>
</body>
</html>
