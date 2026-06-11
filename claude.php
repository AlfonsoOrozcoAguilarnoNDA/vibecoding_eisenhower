<?php
/**
 * Script PHP para gestión de pedientes
 * Versión: 1
 * Modelo: Claude Sonnet 4.6 - web
 * Licencia: MIT
 * Razonamiento : https://vibecodingmexico.com/tres-pruebas-para-grok/
 *
 * Copyright (c) 2026 Alfonso Orozco Aguilar 11 junio 2026
 *
 * Se otorga permiso, de forma gratuita, a cualquier persona que obtenga una copia
 * de este software y los archivos de documentación asociados (el "Software"), para
 * tratar en el Software sin restricción, incluyendo sin limitación los derechos
 * de usar, copiar, modificar, fusionar, publicar, distribuir, sublicenciar, y/o
 * vender copias del Software, y para permitir a las personas a las que se les
 * proporcione el Software a hacerlo, sujeto a las siguientes condiciones:
 *
 * El aviso de copyright anterior y este aviso de permiso se incluirán en todas
 * las copias o partes sustanciales del Software.
 *
 * EL SOFTWARE SE PROPORCIONA "TAL CUAL", SIN GARANTÍA DE NINGÚN TIPO, EXPRESA O
 * IMPLÍCITA, INCLUYENDO PERO NO LIMITADO A LAS GARANTÍAS DE COMERCIABILIDAD,
 * IDONEIDAD PARA UN PROPÓSITO PARTICULAR Y NO INFRACCIÓN. EN NINGÚN CASO LOS
 * AUTORES O TITULARES DEL COPYRIGHT SERÁN RESPONSABLES DE NINGUNA RECLAMACIÓN,
 * DAÑOS U OTRAS RESPONSABILIDADES, YA SEA EN UNA ACCIÓN DE CONTRATO, AGRAVIO O
 * CUALQUIER OTRO MOTIVO, DERIVADAS DE, FUERA DE O EN CONEXIÓN CON EL SOFTWARE
 * O EL USO U OTROS TRATOS EN EL SOFTWARE.
 */

// ============================================================
// PENDIENTES - Cuadrante de Eisenhower
// Modelo: Claude Sonnet 4.6 (Anthropic)
// Stack: PHP 8.x procedural | Bootstrap 4.6 | Font Awesome 5.15
// ============================================================

// --- Zona horaria México Centro ---
date_default_timezone_set('America/Mexico_City');

// --- Conexión BD ---
include "config.php";
// Se asume que $link es un objeto mysqli activo

// --- Auto-referencia ---
$self = basename($_SERVER['PHP_SELF']);

// --- CRUD Operations ---
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // INSERT
    if ($action === 'insert') {
        $categoria  = trim($_POST['categoria']  ?? '');
        $urgencia   = trim($_POST['urgencia']   ?? '');
        $asunto     = trim($_POST['asunto']     ?? '');
        $descripcion= trim($_POST['descripcion']?? '');
        $comentario = trim($_POST['comentario'] ?? '');
        $fecha_realizar = trim($_POST['fecha_realizar'] ?? '');

        if ($categoria && $urgencia && $asunto && $descripcion && $comentario && $fecha_realizar) {
            $fecha_crear = date('Y-m-d H:i:s');
            $stmt = $link->prepare("INSERT INTO pendientes (categoria, urgencia, asunto, descripcion, comentario, fecha_realizar, fecha_crear) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssss', $categoria, $urgencia, $asunto, $descripcion, $comentario, $fecha_realizar, $fecha_crear);
            $stmt->execute();
            $stmt->close();
            $msg = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-2"></i>Tarea guardada correctamente.<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
        } else {
            $msg = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle mr-2"></i>Todos los campos son obligatorios.<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
        }
    }

    // UPDATE
    if ($action === 'update') {
        $id         = (int)($_POST['id'] ?? 0);
        $categoria  = trim($_POST['categoria']  ?? '');
        $urgencia   = trim($_POST['urgencia']   ?? '');
        $asunto     = trim($_POST['asunto']     ?? '');
        $descripcion= trim($_POST['descripcion']?? '');
        $comentario = trim($_POST['comentario'] ?? '');
        $fecha_realizar = trim($_POST['fecha_realizar'] ?? '');

        if ($id && $categoria && $urgencia && $asunto && $descripcion && $comentario && $fecha_realizar) {
            $stmt = $link->prepare("UPDATE pendientes SET categoria=?, urgencia=?, asunto=?, descripcion=?, comentario=?, fecha_realizar=? WHERE id=?");
            $stmt->bind_param('ssssssi', $categoria, $urgencia, $asunto, $descripcion, $comentario, $fecha_realizar, $id);
            $stmt->execute();
            $stmt->close();
            $msg = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-2"></i>Tarea actualizada.<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
        } else {
            $msg = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle mr-2"></i>Todos los campos son obligatorios.<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
        }
    }

    // DELETE
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $confirm = $_POST['confirm_delete'] ?? '';
        if ($id && $confirm === '1') {
            $stmt = $link->prepare("DELETE FROM pendientes WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $msg = '<div class="alert alert-warning alert-dismissible fade show"><i class="fas fa-trash mr-2"></i>Tarea eliminada.<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
        } else {
            $msg = '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle mr-2"></i>Debes confirmar la eliminación.<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
        }
    }
}

// --- Cargar registro para edición ---
$edit = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $res = $link->query("SELECT * FROM pendientes WHERE id=$eid");
    if ($res && $res->num_rows) $edit = $res->fetch_assoc();
}

// --- Datos para la tabla ---
$rows = [];
$result = $link->query("SELECT * FROM pendientes ORDER BY fecha_realizar ASC");
if ($result) {
    while ($r = $result->fetch_assoc()) $rows[] = $r;
}

// --- Estadísticas ---
$total = count($rows);
$dias_mas_antigua = 0;
$conteo = ['urgente_importante'=>0,'urgente_no_importante'=>0,'importante_no_urgente'=>0,'no_urgente_no_importante'=>0];

if ($total > 0) {
    // Tarea más antigua por fecha_crear
    $oldest_q = $link->query("SELECT MIN(fecha_crear) as oldest FROM pendientes");
    if ($oldest_q) {
        $oldest_row = $oldest_q->fetch_assoc();
        if ($oldest_row['oldest']) {
            $oldest_dt = new DateTime($oldest_row['oldest']);
            $now_dt    = new DateTime();
            $dias_mas_antigua = (int)$now_dt->diff($oldest_dt)->days;
        }
    }
    foreach ($rows as $r) {
        $u = $r['urgencia'] ?? '';
        if (isset($conteo[$u])) $conteo[$u]++;
    }
}

// Porcentajes
$pct = [];
foreach ($conteo as $k => $v) {
    $pct[$k] = $total > 0 ? round(($v/$total)*100, 1) : 0;
}

// --- Footer info ---
$php_ver   = phpversion();
$mysqli_ver = $link->server_info ?? 'N/A';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';

// Etiquetas legibles de urgencia
$urgencia_labels = [
    'urgente_importante'       => 'Urgente e Importante',
    'urgente_no_importante'    => 'Urgente, No Importante',
    'importante_no_urgente'    => 'Importante, No Urgente',
    'no_urgente_no_importante' => 'No Urgente, No Importante',
];

// Colores para el cuadrante
$urgencia_colors = [
    'urgente_importante'       => 'danger',
    'urgente_no_importante'    => 'warning',
    'importante_no_urgente'    => 'info',
    'no_urgente_no_importante' => 'secondary',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pendientes · Eisenhower</title>
<!-- Bootstrap 4.6 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<!-- Font Awesome 5.15.4 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
<style>
  :root {
    --bg-body:    #1a1a2e;
    --bg-nav:     #16213e;
    --bg-card:    #0f3460;
    --bg-table:   #1a1a2e;
    --bg-row-alt: #16213e;
    --accent:     #e94560;
    --accent2:    #533483;
    --text:       #e0e0e0;
    --text-muted: #9e9e9e;
    --border:     #2a2a4a;
    --footer-bg:  #0d0d1a;
  }

  html, body {
    height: 100%;
    background: var(--bg-body);
    color: var(--text);
    font-family: 'Segoe UI', system-ui, sans-serif;
  }

  /* ---- Layout pegajoso ---- */
  body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    padding-top: 60px;
    padding-bottom: 52px;
  }
  main { flex: 1; }

  /* ---- Navbar ---- */
  .navbar {
    background: var(--bg-nav) !important;
    border-bottom: 2px solid var(--accent);
    height: 60px;
  }
  .navbar-brand {
    font-weight: 700;
    color: var(--accent) !important;
    letter-spacing: 1px;
    font-size: 1rem;
  }
  .navbar .nav-link { color: var(--text) !important; font-size: .87rem; }
  .navbar .nav-link:hover { color: var(--accent) !important; }
  .navbar .dropdown-menu {
    background: var(--bg-card);
    border: 1px solid var(--border);
  }
  .navbar .dropdown-item { color: var(--text); font-size: .87rem; }
  .navbar .dropdown-item:hover { background: var(--accent2); color: #fff; }

  .btn-exit {
    background: transparent;
    border: 1px solid var(--accent);
    color: var(--accent);
    font-size: .87rem;
    border-radius: 4px;
    padding: 4px 10px;
  }
  .btn-exit:hover { background: var(--accent); color: #fff; }

  /* ---- Footer ---- */
  footer {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    background: var(--footer-bg);
    border-top: 1px solid var(--border);
    color: var(--text-muted);
    font-size: .78rem;
    height: 52px;
    display: flex;
    align-items: center;
    padding: 0 1rem;
    z-index: 1000;
  }

  /* ---- Cards ---- */
  .card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 8px;
  }
  .card-header {
    background: rgba(0,0,0,.25);
    border-bottom: 1px solid var(--border);
    color: var(--accent);
    font-weight: 600;
    font-size: .95rem;
  }

  /* ---- Formulario ---- */
  .form-control, .custom-select {
    background: #0d1b2a;
    border: 1px solid var(--border);
    color: var(--text);
    font-size: .88rem;
  }
  .form-control:focus, .custom-select:focus {
    background: #0d1b2a;
    border-color: var(--accent);
    color: var(--text);
    box-shadow: 0 0 0 .15rem rgba(233,69,96,.25);
  }
  label { font-size: .82rem; color: var(--text-muted); margin-bottom: 2px; }

  /* ---- Tabla ---- */
  .table { color: var(--text); font-size: .84rem; }
  .table thead th {
    background: var(--bg-nav);
    border-color: var(--border);
    color: var(--accent);
    font-size: .8rem;
    text-transform: uppercase;
    letter-spacing: .05em;
  }
  .table tbody tr { border-color: var(--border); }
  .table tbody tr:nth-child(even) td { background: var(--bg-row-alt); }
  .table tbody tr:nth-child(odd) td  { background: var(--bg-table); }
  .table td { border-color: var(--border); vertical-align: middle; }

  /* ---- Badges de urgencia ---- */
  .badge-urgente_importante       { background: #dc3545; color:#fff; }
  .badge-urgente_no_importante    { background: #ffc107; color:#212529; }
  .badge-importante_no_urgente    { background: #17a2b8; color:#fff; }
  .badge-no_urgente_no_importante { background: #6c757d; color:#fff; }

  /* ---- Estadísticas / Cuadrante ---- */
  .stat-card {
    background: rgba(0,0,0,.3);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
  }
  .stat-card .stat-num { font-size: 1.8rem; font-weight: 700; }
  .stat-card .stat-label { font-size: .75rem; color: var(--text-muted); }
  .stat-days { font-size: 2.4rem; font-weight: 800; color: var(--accent); }

  /* ---- Progress ---- */
  .progress { background: #0d0d1a; height: 10px; border-radius: 5px; }

  /* ---- Botones ---- */
  .btn-sm { font-size: .78rem; }

  /* ---- Checkbox confirmación ---- */
  .confirm-row { display: none; }
  .confirm-row.show { display: flex; }

  /* ---- Alerts ---- */
  .alert { font-size: .88rem; }
</style>
</head>
<body>

<!-- ================================================================ NAVBAR -->
<nav class="navbar navbar-expand fixed-top">
  <a class="navbar-brand" href="<?= htmlspecialchars($self) ?>">
    <i class="fas fa-tasks mr-1"></i> Pendientes <small class="text-muted ml-1" style="font-size:.7rem">Eisenhower</small>
  </a>
  <div class="ml-auto d-flex align-items-center">
    <!-- Modelo badge -->
    <span class="badge badge-secondary mr-3 d-none d-md-inline" style="font-size:.68rem">
      <i class="fas fa-robot mr-1"></i>Claude Sonnet 4.6
    </span>
    <!-- Menú enlaces -->
    <ul class="navbar-nav mr-2">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="linksMenu" data-toggle="dropdown">
          <i class="fas fa-globe mr-1"></i>Links
        </a>
        <div class="dropdown-menu dropdown-menu-right">
          <a class="dropdown-item" href="https://grok.x.ai" target="_blank">
            <i class="fas fa-robot mr-2"></i>Grok
          </a>
          <a class="dropdown-item" href="https://www.google.com" target="_blank">
            <i class="fab fa-google mr-2"></i>Google
          </a>
          <a class="dropdown-item" href="https://janice.e-351.com" target="_blank">
            <i class="fas fa-search-dollar mr-2"></i>Janice (EVE Precios)
          </a>
        </div>
      </li>
    </ul>
    <!-- Botón salir -->
    <button class="btn-exit" onclick="confirmExit()">
      <i class="fas fa-door-open mr-1"></i>Salir
    </button>
  </div>
</nav>

<!-- ================================================================ MAIN -->
<main class="container-fluid py-3 px-3 px-md-4">

  <?= $msg ?>

  <div class="row">

    <!-- ---- FORMULARIO ---- -->
    <div class="col-12 col-lg-4 mb-3">
      <div class="card h-100">
        <div class="card-header">
          <i class="fas fa-<?= $edit ? 'edit' : 'plus-circle' ?> mr-2"></i>
          <?= $edit ? 'Editar Tarea' : 'Nueva Tarea' ?>
        </div>
        <div class="card-body p-3">
          <form method="POST" action="<?= htmlspecialchars($self) ?>">
            <input type="hidden" name="action" value="<?= $edit ? 'update' : 'insert' ?>">
            <?php if ($edit): ?>
              <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
            <?php endif; ?>

            <div class="form-group mb-2">
              <label>Categoría <span class="text-danger">*</span></label>
              <input type="text" name="categoria" class="form-control form-control-sm"
                     maxlength="80" required
                     value="<?= htmlspecialchars($edit['categoria'] ?? '') ?>">
            </div>

            <div class="form-group mb-2">
              <label>Urgencia <span class="text-danger">*</span></label>
              <select name="urgencia" class="custom-select custom-select-sm" required>
                <option value="">— seleccione —</option>
                <?php foreach ($urgencia_labels as $val => $lbl): ?>
                  <option value="<?= $val ?>"
                    <?= (($edit['urgencia'] ?? '') === $val) ? 'selected' : '' ?>>
                    <?= $lbl ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group mb-2">
              <label>Asunto <span class="text-danger">*</span></label>
              <input type="text" name="asunto" class="form-control form-control-sm"
                     maxlength="120" required
                     value="<?= htmlspecialchars($edit['asunto'] ?? '') ?>">
            </div>

            <div class="form-group mb-2">
              <label>Descripción <span class="text-danger">*</span> <small class="text-muted">(máx. 40 car.)</small></label>
              <input type="text" name="descripcion" class="form-control form-control-sm"
                     maxlength="40" required
                     value="<?= htmlspecialchars($edit['descripcion'] ?? '') ?>">
            </div>

            <div class="form-group mb-2">
              <label>Comentario <span class="text-danger">*</span> <small class="text-muted">(máx. 80 car.)</small></label>
              <input type="text" name="comentario" class="form-control form-control-sm"
                     maxlength="80" required
                     value="<?= htmlspecialchars($edit['comentario'] ?? '') ?>">
            </div>

            <div class="form-group mb-3">
              <label>Fecha a realizar <span class="text-danger">*</span></label>
              <input type="date" name="fecha_realizar" class="form-control form-control-sm"
                     required
                     value="<?= htmlspecialchars($edit['fecha_realizar'] ?? '') ?>">
            </div>

            <div class="d-flex">
              <button type="submit" class="btn btn-danger btn-sm mr-2">
                <i class="fas fa-save mr-1"></i><?= $edit ? 'Actualizar' : 'Guardar' ?>
              </button>
              <?php if ($edit): ?>
                <a href="<?= htmlspecialchars($self) ?>" class="btn btn-secondary btn-sm">
                  <i class="fas fa-times mr-1"></i>Cancelar
                </a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ---- TABLA ---- -->
    <div class="col-12 col-lg-8 mb-3">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="fas fa-list mr-2"></i>Tareas Pendientes</span>
          <span class="badge badge-secondary"><?= $total ?> registros</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Categoría</th>
                  <th>Urgencia</th>
                  <th>Asunto</th>
                  <th>Descripción</th>
                  <th>Comentario</th>
                  <th>Realizar</th>
                  <th>Creada</th>
                  <th style="min-width:100px">Acciones</th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($rows)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">
                  <i class="fas fa-inbox fa-2x mb-2 d-block"></i>Sin tareas registradas
                </td></tr>
              <?php else: ?>
                <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= (int)$r['id'] ?></td>
                  <td><?= htmlspecialchars($r['categoria']) ?></td>
                  <td>
                    <span class="badge badge-<?= htmlspecialchars($r['urgencia']) ?> badge-pill" style="font-size:.7rem;white-space:normal">
                      <?= htmlspecialchars($urgencia_labels[$r['urgencia']] ?? $r['urgencia']) ?>
                    </span>
                  </td>
                  <td><?= htmlspecialchars($r['asunto']) ?></td>
                  <td><?= htmlspecialchars($r['descripcion']) ?></td>
                  <td><?= htmlspecialchars($r['comentario']) ?></td>
                  <td><?= htmlspecialchars($r['fecha_realizar']) ?></td>
                  <td><?= htmlspecialchars($r['fecha_crear']) ?></td>
                  <td>
                    <a href="<?= htmlspecialchars($self) ?>?edit=<?= (int)$r['id'] ?>"
                       class="btn btn-info btn-sm mb-1">
                      <i class="fas fa-edit"></i>
                    </a>
                    <!-- Botón borrar con confirmación checkbox -->
                    <form method="POST" action="<?= htmlspecialchars($self) ?>" class="d-inline" id="del-form-<?= (int)$r['id'] ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <input type="hidden" name="confirm_delete" id="conf-<?= (int)$r['id'] ?>" value="0">
                      <button type="button" class="btn btn-danger btn-sm mb-1"
                        onclick="toggleDeleteConfirm(<?= (int)$r['id'] ?>)">
                        <i class="fas fa-trash"></i>
                      </button>
                      <!-- Fila de confirmación inline -->
                      <div class="confirm-row align-items-center mt-1" id="confirm-row-<?= (int)$r['id'] ?>">
                        <div class="custom-control custom-checkbox mr-2">
                          <input type="checkbox" class="custom-control-input"
                                 id="chk-<?= (int)$r['id'] ?>"
                                 onchange="enableDelete(<?= (int)$r['id'] ?>, this.checked)">
                          <label class="custom-control-label text-warning" for="chk-<?= (int)$r['id'] ?>"
                                 style="font-size:.75rem">Confirmar</label>
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm" id="btn-del-<?= (int)$r['id'] ?>" disabled>
                          <i class="fas fa-check"></i>
                        </button>
                      </div>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ---- ESTADÍSTICAS ---- -->
      <div class="card mt-3">
        <div class="card-header">
          <i class="fas fa-chart-pie mr-2"></i>Resumen · Cuadrante de Eisenhower
        </div>
        <div class="card-body p-3">
          <div class="row align-items-start">
            <!-- Días tarea más antigua -->
            <div class="col-12 col-sm-3 mb-3 text-center">
              <div class="stat-card">
                <div class="stat-days"><?= $dias_mas_antigua ?></div>
                <div class="stat-label">días desde la tarea<br>más antigua</div>
              </div>
            </div>
            <!-- Cuadrante -->
            <div class="col-12 col-sm-9">
              <?php foreach ($urgencia_labels as $key => $lbl): ?>
              <div class="mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <small>
                    <span class="badge badge-<?= $key ?> mr-1" style="font-size:.7rem">
                      <?= $lbl ?>
                    </span>
                  </small>
                  <small class="text-muted">
                    <?= $conteo[$key] ?> tareas — <?= $pct[$key] ?>%
                  </small>
                </div>
                <div class="progress">
                  <div class="progress-bar bg-<?= $urgencia_colors[$key] ?>"
                       style="width:<?= $pct[$key] ?>%"></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /col -->
  </div><!-- /row -->
</main>

<!-- ================================================================ FOOTER -->
<footer>
  <i class="fas fa-server mr-2 text-muted"></i>
  PHP <?= htmlspecialchars($php_ver) ?>
  &nbsp;·&nbsp;
  <i class="fas fa-database mr-1 text-muted"></i>MySQLi <?= htmlspecialchars($mysqli_ver) ?>
  &nbsp;·&nbsp;
  <i class="fas fa-network-wired mr-1 text-muted"></i><?= htmlspecialchars($ip) ?>
  <span class="ml-auto text-muted" style="font-size:.72rem">
    <?= date('Y-m-d H:i') ?> · México
  </span>
</footer>

<!-- ================================================================ JS -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- Confirmar salida ---
function confirmExit() {
  if (confirm('¿Está usted seguro de que desea salir?')) {
    window.location.href = '/';
  }
}

// --- Toggle fila de confirmación de borrado ---
function toggleDeleteConfirm(id) {
  var row = document.getElementById('confirm-row-' + id);
  row.classList.toggle('show');
  // Reset si se oculta
  if (!row.classList.contains('show')) {
    document.getElementById('chk-' + id).checked = false;
    document.getElementById('conf-' + id).value = '0';
    document.getElementById('btn-del-' + id).disabled = true;
  }
}

// --- Habilitar botón de borrado cuando checkbox marcado ---
function enableDelete(id, checked) {
  document.getElementById('conf-' + id).value = checked ? '1' : '0';
  document.getElementById('btn-del-' + id).disabled = !checked;
}

// --- Límite de caracteres dinámico en campos de texto ---
document.addEventListener('DOMContentLoaded', function() {
  var limits = { descripcion: 40, comentario: 80 };
  Object.keys(limits).forEach(function(name) {
    var el = document.querySelector('[name="' + name + '"]');
    if (!el) return;
    el.addEventListener('input', function() {
      if (this.value.length > limits[name]) {
        this.value = this.value.slice(0, limits[name]);
      }
    });
  });
});
</script>

</body>
</html>
