<?php
/**
 * Layout principal de la aplicación — Limaro SGC
 * Bootstrap 5 + Bootstrap Icons + DataTables + Chart.js
 */
use App\Core\Auth;
use App\Core\Session;

$authUser  = $authUser  ?? Auth::user() ?? [];
$modulos   = $modulos   ?? Auth::modulos() ?? [];
$emp       = empresa();
$empNombre = $emp['nombre_empresa'] ?: 'SGC';
$empLogo   = empresaLogoUrl();
$pageTitle = $pageTitle ?? $empNombre;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-url" content="<?= e(APP_URL) ?>">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle) ?> — <?= e($empNombre) ?></title>
    <?php if ($empLogo): ?>
        <link rel="icon" type="image/png" href="<?= e($empLogo) ?>">
    <?php endif; ?>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- DataTables Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- DataTables Buttons -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <!-- DataTables RowGroup -->
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.4.1/css/rowGroup.bootstrap5.min.css">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/app.css">
    
    <style>
    /* ── 1. FUENTE GLOBAL Arial ── */
    *, *::before, *::after {
        font-family: Arial, 'Helvetica Neue', sans-serif !important;
    }
    .bi, [class^="bi-"], [class*=" bi-"] {
        font-family: 'Bootstrap Icons' !important;
    }

    /* ── 2. COLOR Y TAMAÑO — solo elementos de contenido ── */
    body { font-size: 14px; color: #000; }
    p, td, th, li, label, .form-label, .form-text,
    .card-title, .card-text, .modal-title,
    h1, h2, h3, h4, h5, h6 { color: #000; }
    h1 { font-size: 22px; }
    h2 { font-size: 18px; }
    h3 { font-size: 16px; }
    h4 { font-size: 14px; }

    /* ── 3. RESPONSIVE font-size ── */
    @media (min-width: 1400px) {
        body { font-size: 15px; }
        td, th { font-size: 15px !important; }
    }
    @media (max-width: 575px) {
        body { font-size: 13px; }
        td, th { font-size: 13px !important; }
    }

    /* ── 4. BOTONES — siempre blanco en fondos sólidos ── */
    .btn-primary, .btn-lim-primary,
    .btn-success, .btn-danger, .btn-warning,
    .btn-info, .btn-dark, .btn-secondary,
    .btn-primary *, .btn-lim-primary *,
    .btn-success *, .btn-danger *, .btn-dark * {
        color: #fff !important;
    }
    .btn-outline-primary { color: #1B3A6B !important; }
    .btn-outline-success { color: #198754 !important; }
    .btn-outline-danger  { color: #dc3545 !important; }
    .btn-outline-info    { color: #0dcaf0 !important; }
    .btn-outline-secondary { color: #6c757d !important; }

    /* ── 5. SIDEBAR — máxima especificidad, color blanco ── */
    #sidebar, .sidebar,
    #sidebar *, .sidebar *,
    #sidebar a, .sidebar a,
    #sidebar span, .sidebar span,
    #sidebar i, .sidebar i,
    .sidebar-link,
    .sidebar-link span,
    .sidebar-link i {
        color: rgba(255,255,255,0.85) !important;
    }
    .sidebar-link.active,
    .sidebar-link.active *,
    .sidebar-link[aria-expanded="true"],
    .sidebar-link[aria-expanded="true"] * {
        color: #fff !important;
    }
    .sidebar-link:hover,
    .sidebar-link:hover * {
        color: #E0F7FC !important;
    }
    .sidebar-brand-text strong { color: #fff !important; font-size: 13px !important; }
    .sidebar-brand-text small  { color: #00B5D8 !important; font-size: 10px !important; }
    /* Cerrar sesión */
    .sidebar .text-danger,
    .sidebar a.text-danger,
    .sidebar .text-danger * { color: #EF4444 !important; }
    </style>
</head>
<body>
<div class="wrapper">

    <!-- ================================================================
         SIDEBAR
         ================================================================ -->
    <nav id="sidebar">
        <!-- Brand -->
       <a class="sidebar-brand" href="<?= e(APP_URL) ?>/inicio">
    <div class="sidebar-logo d-flex align-items-center justify-content-center"
         style="background:#fff;border-radius:8px;overflow:hidden;padding:4px;width:44px;height:44px;flex-shrink:0;">
        <?php if ($empLogo): ?>
            <img src="<?= e($empLogo) ?>" alt="<?= e($empNombre) ?>""
                 alt="<?= e($empNombre) ?>"
                 style="max-width:36px;max-height:36px;object-fit:contain;width:36px;height:36px;"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <!-- Fallback SVG oculto por defecto -->
            <svg class="logo-fallback" style="display:none;" width="36" height="36" viewBox="0 0 36 36" fill="none" aria-label="<?= e($empNombre) ?>">
                <rect width="36" height="36" rx="8" fill="#1e5fbf"/>
                <text x="5" y="27" font-family="sans-serif" font-weight="800" font-size="22" fill="#fff">
                    <?= e(mb_strtoupper(mb_substr($empNombre ?? '', 0, 1), 'UTF-8')) ?>
                </text>
                <circle cx="28" cy="10" r="4" fill="#ff7a00"/>
            </svg>
        <?php else: ?>
            <svg width="36" height="36" viewBox="0 0 36 36" fill="none" aria-label="<?= e($empNombre) ?>">
                <rect width="36" height="36" rx="8" fill="#1e5fbf"/>
                <text x="5" y="27" font-family="sans-serif" font-weight="800" font-size="22" fill="#fff"><?= e(mb_strtoupper(mb_substr($empNombre ?? '', 0, 1), 'UTF-8')) ?></text>
                <circle cx="28" cy="10" r="4" fill="#ff7a00"/>
            </svg>
        <?php endif; ?>
    </div>
    <div class="sidebar-brand-text">
        <strong><?= e($empNombre) ?></strong>
        <!--<small>ISO 9001:2015</small>-->
    </div>
</a>

        <!-- Navegación dinámica desde tabla modulo + rol_modulo -->
        <ul class="sidebar-nav list-unstyled mb-0">
            <?php
            // Construir árbol jerárquico
            $tree = [];
            $byId = [];
            foreach ($modulos as $m) {
                $m['hijos'] = [];
                $byId[$m['id_modulo']] = $m;
            }

            // Construir árbol padre → hijos
            foreach ($byId as &$m) {
                if (($m['id_padre'] ?? null) === null) {
                    $tree[] = &$m;
                }
            }
            unset($m);
            foreach ($byId as &$m) {
                if (!empty($m['id_padre']) && isset($byId[$m['id_padre']])) {
                    $byId[$m['id_padre']]['hijos'][] = &$m;
                }
            }
            unset($m);
            foreach ($byId as &$m) {
                if (!empty($m['hijos'])) {
                    usort($m['hijos'], fn($a,$b) => ($a['orden']??99) <=> ($b['orden']??99));
                }
            }
            unset($m);
            usort($tree, fn($a,$b) => ($a['orden']??99) <=> ($b['orden']??99));

            $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $base = parse_url(APP_URL, PHP_URL_PATH) ?: '';
            if ($base && str_starts_with($currentUri, $base)) {
                $currentUri = substr($currentUri, strlen($base)) ?: '/';
            }

            // Recoger todas las URLs del menú para detectar si la URL actual
            // tiene una coincidencia EXACTA. Si la tiene, no usar prefix-match
            // en otros módulos (evita que /acuerdos/vigentes active /acuerdos).
            $todasUrlsMenu = [];
            foreach ($byId as $m) {
                if (!empty($m['url'])) $todasUrlsMenu[] = $m['url'];
            }
            $hayCoincidenciaExacta = in_array($currentUri, $todasUrlsMenu, true);

            // Helper: determinar si una URL de menú debe marcarse como activa
            $urlActiva = function(string $url) use ($currentUri, $hayCoincidenciaExacta): bool {
                if ($url === $currentUri) return true;
                // Solo usar prefix-match si la URL actual NO coincide exactamente
                // con ningún otro módulo del menú
                if ($hayCoincidenciaExacta) return false;
                return str_starts_with($currentUri, rtrim($url, '/') . '/');
            };

            foreach ($tree as $padre) {
                $hasHijos = !empty($padre['hijos']);
                $isActive = ($padre['url'] && $urlActiva($padre['url']));

                // Verificar si algún hijo/nieto está activo
                $childActive = false;
                if ($hasHijos) {
                    foreach ($padre['hijos'] as $h) {
                        if ($h['url'] && $urlActiva($h['url'])) {
                            $childActive = true;
                        }
                        if (!empty($h['hijos'])) {
                            foreach ($h['hijos'] as $n) {
                                if ($n['url'] && $urlActiva($n['url'])) {
                                    $childActive = true;
                                }
                            }
                        }
                    }
                }

                $collapseId = 'menu-' . $padre['id_modulo'];
                ?>
                <li class="sidebar-item">
                    <?php if (!$hasHijos): ?>
                        <a class="sidebar-link <?= ($isActive || $childActive) ? 'active' : '' ?>"
                           href="<?= e(APP_URL . ($padre['url'] ?? '#')) ?>">
                            <i class="bi <?= e($padre['icono'] ?? 'bi-circle') ?> sidebar-label"></i>
                            <span class="sidebar-label"><?= e($padre['nombre']) ?></span>
                        </a>
                    <?php else: ?>
                        <a class="sidebar-link <?= $childActive ? 'active' : '' ?>"
                           data-bs-toggle="collapse"
                           href="#<?= e($collapseId) ?>"
                           aria-expanded="<?= $childActive ? 'true' : 'false' ?>">
                            <i class="bi <?= e($padre['icono'] ?? 'bi-circle') ?>"></i>
                            <span class="sidebar-label"><?= e($padre['nombre']) ?></span>
                            <i class="bi bi-chevron-right sidebar-arrow sidebar-label"></i>
                        </a>
                        <div class="collapse <?= $childActive ? 'show' : '' ?>" id="<?= e($collapseId) ?>">
                            <ul class="sidebar-submenu list-unstyled">
                                <?php foreach ($padre['hijos'] as $hijo):
                                    $hasNietos  = !empty($hijo['hijos']);
                                    $hijoActive = ($hijo['url'] && $urlActiva($hijo['url']));
                                    $nieto_active = false;
                                    if ($hasNietos) {
                                        foreach ($hijo['hijos'] as $n) {
                                            if ($n['url'] && $urlActiva($n['url'])) {
                                                $nieto_active = true;
                                            }
                                        }
                                    }
                                    $subCollapseId = 'menu-' . $hijo['id_modulo'];
                                ?>
                                    <li class="sidebar-item">
                                        <?php if (!$hasNietos): ?>
                                            <a class="sidebar-link <?= $hijoActive ? 'active' : '' ?>"
                                               href="<?= e(APP_URL . ($hijo['url'] ?? '#')) ?>">
                                                <i class="bi <?= e($hijo['icono'] ?? 'bi-circle') ?>"></i>
                                                <span class="sidebar-label"><?= e($hijo['nombre']) ?></span>
                                            </a>
                                        <?php else: ?>
                                            <a class="sidebar-link <?= ($hijoActive || $nieto_active) ? 'active' : '' ?>"
                                               data-bs-toggle="collapse"
                                               href="#<?= e($subCollapseId) ?>"
                                               aria-expanded="<?= ($hijoActive || $nieto_active) ? 'true' : 'false' ?>">
                                                <i class="bi <?= e($hijo['icono'] ?? 'bi-circle') ?>"></i>
                                                <span class="sidebar-label"><?= e($hijo['nombre']) ?></span>
                                                <i class="bi bi-chevron-right sidebar-arrow sidebar-label" style="font-size:10px;"></i>
                                            </a>
                                            <div class="collapse <?= ($hijoActive || $nieto_active) ? 'show' : '' ?>"
                                                 id="<?= e($subCollapseId) ?>">
                                                <ul class="sidebar-subsubmenu list-unstyled">
                                                    <?php foreach ($hijo['hijos'] as $nieto):
                                                        $nietoActive = ($nieto['url'] && $urlActiva($nieto['url']));
                                                    ?>
                                                        <li>
                                                            <a class="sidebar-link <?= $nietoActive ? 'active' : '' ?>"
                                                               href="<?= e(APP_URL . ($nieto['url'] ?? '#')) ?>">
                                                                <i class="bi <?= e($nieto['icono'] ?? 'bi-dot') ?>"></i>
                                                                <span class="sidebar-label"><?= e($nieto['nombre']) ?></span>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </li>
                <?php
            }
            ?>

            <!-- Perfil y Cerrar Sesión (siempre visibles) -->
            <li class="sidebar-item mt-2" style="border-top:1px solid rgba(255,255,255,.08);padding-top:8px;">
                <a class="sidebar-link" href="<?= e(APP_URL) ?>/perfil">
                    <i class="bi bi-person-circle"></i>
                    <span class="sidebar-label">Mi Perfil</span>
                </a>
                <a class="sidebar-link" href="<?= e(APP_URL) ?>/logout">
                    <i class="bi bi-box-arrow-right" style="color:#f87171;"></i>
                    <span class="sidebar-label" style="color:#f87171;">Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    
    <!-- Logo Limaro Software al fondo del sidebar -->
    <div class="sidebar-footer">
        <img src="<?= e(APP_URL) ?>/assets/img/limaro-logo.png"
             alt="Limaro Software" title="Limaro Software">
        <div class="sidebar-footer-text">Desarrollado por LIMARO</div>
    </div>

</nav>

    <!-- ================================================================
         TOPBAR
         ================================================================ -->
    <header id="topbar">
        <button id="sidebar-toggle" class="topbar-toggle" title="Toggle menú">
            <i class="bi bi-list"></i>
        </button>

        <div class="topbar-breadcrumb">
            <strong><?= e($pageTitle ?? 'Inicio') ?></strong>
        </div>

        <!-- Usuario -->
        <div class="topbar-user">
            <span class="d-none d-md-inline text-muted">
                <?= e($authUser['nombre_completo'] ?? $authUser['usuario'] ?? '') ?>
            </span>
            <div class="dropdown">
                <button class="btn p-0 border-0 bg-transparent" data-bs-toggle="dropdown">
             <img class="topbar-avatar" alt="Foto de perfil"
     src="<?= e(urlFotoPerfil($authUser['img_empleado'] ?? null, Auth::id())) ?>"
     alt="Avatar"
     onerror="this.onerror=null; this.src='<?= e(APP_URL) ?>/assets/img/usuario.png'; this.classList.add('img-error');">
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text fw-bold"><?= e($authUser['usuario'] ?? '') ?></span></li>
                    <li><span class="dropdown-item-text text-muted small"><?= e($authUser['rol'] ?? $authUser['rol_nombre'] ?? '') ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= e(APP_URL) ?>/perfil"><i class="bi bi-person me-2"></i>Mi Perfil</a></li>
                    <li><a class="dropdown-item" href="<?= e(APP_URL) ?>/perfil/cambiar-clave"><i class="bi bi-key me-2"></i>Cambiar Clave</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= e(APP_URL) ?>/logout"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- ================================================================
         CONTENIDO PRINCIPAL
         ================================================================ -->
    <main id="main-content">
        <!-- Flash messages -->
        <?php include APP_ROOT . '/app/views/partials/flash.php'; ?>

        <!-- Vista renderizada -->
        <?= $content ?>
    </main>

</div><!-- /.wrapper -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery (requerido por DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables Bootstrap 5 -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<!-- DataTables RowGroup -->
<script src="https://cdn.datatables.net/rowgroup/1.4.1/js/dataTables.rowGroup.min.js"></script>
<script src="https://cdn.datatables.net/rowgroup/1.4.1/js/rowGroup.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<!-- App JS -->
<script src="<?= e(APP_URL) ?>/assets/js/app.js"></script>

<?php if (isset($scripts)): ?>
    <?= $scripts ?>
<?php endif; ?>

<!-- Footer de la aplicación -->
<footer id="app-footer">
    <span>© <?= date('Y') ?> — Todos los Derechos Reservados</span>
    <span class="footer-sep">|</span>
    <span>Diseñado y Desarrollado por
        <a href="https://limaro.cloud" target="_blank" rel="noopener">LIMARO</a>
    </span>
    <span class="footer-sep">|</span>
    <span><?= defined('APP_VERSION') ? APP_VERSION : 'V1.0' ?></span>
</footer>

<script>
(function() {
    const TIMEOUT_MS    = 10 * 60 * 1000;  // 10 minutos
    const AVISO_MS      = 9  * 60 * 1000;  //  9 minutos — aviso 1 min antes
    const LOGOUT_URL    = '<?= e(APP_URL) ?>/logout';
    let timerAviso, timerLogout, swalAbierto = false;

    function resetTimer() {
        clearTimeout(timerAviso);
        clearTimeout(timerLogout);
        if (swalAbierto && typeof Swal !== 'undefined') {
            Swal.close();
            swalAbierto = false;
        }
        // Aviso a los 9 minutos
        timerAviso = setTimeout(function() {
            if (typeof Swal === 'undefined') { doLogout(); return; }
            swalAbierto = true;
            let segundos = 60;
            Swal.fire({
                icon:              'warning',
                title:             'Sesión por expirar',
                html:              'Su sesión cerrará en <strong id="cuentaReg">60</strong> segundos por inactividad.',
                confirmButtonText: 'Seguir conectado',
                confirmButtonColor:'#1B3A6B',
                showCancelButton:  true,
                cancelButtonText:  'Cerrar sesión',
                cancelButtonColor: '#dc3545',
                allowOutsideClick: false,
                didOpen: function() {
                    const el = document.getElementById('cuentaReg');
                    const intervalo = setInterval(function() {
                        segundos--;
                        if (el) el.textContent = segundos;
                        if (segundos <= 0) clearInterval(intervalo);
                    }, 1000);
                }
            }).then(function(result) {
                swalAbierto = false;
                if (result.isConfirmed) {
                    resetTimer();
                    // Ping al servidor para renovar _last_activity
                    fetch('<?= e(APP_URL) ?>/inicio', { method: 'HEAD', credentials: 'same-origin' });
                } else {
                    doLogout();
                }
            });
        }, AVISO_MS);

        // Logout automático a los 10 minutos
        timerLogout = setTimeout(doLogout, TIMEOUT_MS);
    }

    function doLogout() {
        window.location.href = LOGOUT_URL;
    }

    // Detectar actividad del usuario
    ['mousemove','keydown','mousedown','touchstart','scroll','click'].forEach(function(ev) {
        document.addEventListener(ev, resetTimer, { passive: true });
    });

    // Cerrar sesión si se cierra la pestaña/navegador y no hay otras pestañas abiertas
    window.addEventListener('beforeunload', function() {
        // Usar localStorage para contar pestañas abiertas
        const key = 'limaro_tabs';
        let tabs = parseInt(localStorage.getItem(key) || '0');
        localStorage.setItem(key, Math.max(0, tabs - 1));
    });
    window.addEventListener('load', function() {
        const key = 'limaro_tabs';
        let tabs = parseInt(localStorage.getItem(key) || '0');
        localStorage.setItem(key, tabs + 1);
    });

    resetTimer();
})();
</script>
</body>
</html>
