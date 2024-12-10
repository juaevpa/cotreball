<header>
    <h1><a href="/" class="logo">Cotreball</a></h1>
    <button class="menu-toggle" aria-label="Menú">
        <i class="fas fa-bars"></i>
    </button>
    <div class="search-container">
    <form action="/index.php" method="GET">
        <input type="text" name="search" id="searchInput" placeholder="Buscar por ciudad o provincia..." required>
        <button type="submit" ></button>
    </form>
</div>
    <div class="header-right">
        <nav class="main-nav">
            <a href="/" class="nav-link">Inicio</a>
            <a href="/about.php" class="nav-link">Sobre Cotreball</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['is_admin']): ?>
                    <a href="/admin" class="nav-link">Panel Admin</a>
                <?php endif; ?>
                <a href="/admin/spaces/create.php" class="nav-link">Crear Espacio</a>
                <a href="/auth/logout.php" class="nav-link icon-link" title="Cerrar Sesión">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            <?php else: ?>
                <a href="/auth/login.php" class="nav-link icon-link" title="Iniciar Sesión">
                    <i class="fas fa-sign-in-alt"></i>
                </a>
                <a href="/auth/register.php" class="nav-link icon-link" title="Registrarse">
                    <i class="fas fa-user-plus"></i>
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<script>
document.getElementById('searchInput').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        const searchQuery = encodeURIComponent(this.value);
        window.location.href = '/index.php?search=' + searchQuery;
    }
});
</script> 