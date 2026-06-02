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
$empNombre = $emp['nombre_empresa'] ?: 'Limaro SGC';
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
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/app.css">
    
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
         style="background:var(--lim-blue);border-radius:8px;overflow:hidden;">
        <?php if ($empLogo): ?>
            <img src="<?= e($empLogo) ?>"
                 alt="<?= e($empNombre) ?>"
                 style="max-width:36px;max-height:36px;object-fit:contain;"
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
        <small>ISO 9001:2015</small>
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

            // Cargar módulos padre que falten (contenedores sin permisos propios)
            $idsPadreNecesarios = [];
            foreach ($byId as $m) {
                if ($m['id_padre'] !== null && !isset($byId[$m['id_padre']])) {
                    $idsPadreNecesarios[] = (int)$m['id_padre'];
                }
            }
            if (!empty($idsPadreNecesarios)) {
                try {
                    $db  = \App\Core\Database::getInstance();
                    $ph  = implode(',', array_fill(0, count($idsPadreNecesarios), '?'));
                    $stm = $db->prepare("SELECT * FROM modulo WHERE id_modulo IN ($ph) AND estado='ACTIVO'");
                    $stm->execute($idsPadreNecesarios);
                    foreach ($stm->fetchAll() as $p) {
                        $p['hijos'] = [];
                        $byId[$p['id_modulo']] = $p;
                    }
                } catch (\Throwable $e) { /* silenciar en menú */ }
            }

            foreach ($byId as &$m) {
                if (($m['id_padre'] ?? null) === null) {
                    $tree[] = &$m;
                }
            }
            unset($m);
            // Hijar al árbol
            foreach ($byId as &$m) {
                if (!empty($m['id_padre']) && isset($byId[$m['id_padre']])) {
                    $byId[$m['id_padre']]['hijos'][] = &$m;
                }
            }
            unset($m);
            // Ordenar hijos por campo orden
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
             <img class="topbar-avatar"
     src="<?= e(urlFotoPerfil($authUser['img_empleado'] ?? null)) ?>"
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
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<!-- App JS -->
<script src="<?= e(APP_URL) ?>/assets/js/app.js"></script>

<?php if (isset($scripts)): ?>
    <?= $scripts ?>
<?php endif; ?>
</body>
</html>
