<header>
    <h1><a href="/" class="logo">Cotreball</a></h1>
    <button class="menu-toggle" aria-label="Menú">
        <i class="fas fa-bars"></i>
    </button>
    <div class="search-container">
        <form action="/index.php" method="GET" id="searchForm">
            <input type="text" 
                   name="search" 
                   id="searchInput" 
                   placeholder="Buscar por ciudad o provincia..." 
                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
            >
            <button type="submit" aria-label="Buscar">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    <div class="header-right">
        <nav class="main-nav">
            <a href="/" class="nav-link">Inicio</a>
            <a href="/about.php" class="nav-link">Sobre Cotreball</a>

                <a href="/admin/spaces/create.php" class="nav-link">Crear Espacio</a>
                <?php if (isset($_SESSION['user_id'])): ?>    
                <?php if ($_SESSION['is_admin']): ?>
                    <a href="/admin" class="nav-link">Panel Admin</a>
                <?php else: ?>
                    <a href="/user/spaces.php" class="nav-link">Mis Espacios</a>
                <?php endif; ?>
                
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
document.getElementById('searchForm').addEventListener('submit', function(event) {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput.value.trim()) {
        event.preventDefault();
        return false;
    }
});
</script> 