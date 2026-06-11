<?php
/**
 * Script PHP para gestión de pedientes
 * Versión: 1.01
 * Modelo: Kimi 2.6
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
// Modelo: Implementación de Referencia
// Stack: PHP 8.x procedural | Bootstrap 4.6.2 | Font Awesome 5.15.4
// ============================================================

session_start();

// --- Zona horaria México ---
date_default_timezone_set('America/Mexico_City');

// --- Conexión BD ---
include "config.php";
// Se asume que $link es un objeto mysqli activo

// --- Auto-referencia ---
$self = basename($_SERVER['PHP_SELF']);

// --- Mensajes ---
$msg = '';
$tipo = '';

// --- Función de validación de longitud portable ---
function len($str) {
    return function_exists('mb_strlen') ? mb_strlen($str, 'UTF-8') : strlen($str);
}

// --- CRUD Operations ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // TRIM general a todos los campos POST
    foreach ($_POST as $key => $val) {
        if (is_string($val)) {
            $_POST[$key] = trim($val);
        }
    }

    // INSERT
    if ($action === 'guardar') {
        $categoria   = $_POST['categoria']   ?? '';
        $urgencia    = $_POST['urgencia']    ?? '';
        $asunto      = $_POST['asunto']      ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $comentario  = $_POST['comentario']  ?? '';
        $fecha_realizar = $_POST['fecha_realizar'] ?? '';

        // Validaciones
        if ($categoria === '' || $urgencia === '' || $asunto === '' || $fecha_realizar === '') {
            $msg = 'Los campos Categoría, Urgencia, Asunto y Fecha son obligatorios.';
            $tipo = 'danger';
        } elseif (len($descripcion) > 40) {
            $msg = 'La descripción no puede exceder 40 caracteres.';
            $tipo = 'danger';
        } elseif (len($comentario) > 80) {
            $msg = 'El comentario no puede exceder 80 caracteres.';
            $tipo = 'danger';
        } else {
            $stmt = $link->prepare("INSERT INTO pendientes 
                (categoria, urgencia, asunto, descripcion, comentario, fecha_realizar) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssss', $categoria, $urgencia, $asunto, $descripcion, $comentario, $fecha_realizar);

            if ($stmt->execute()) {
                $msg = 'Tarea guardada correctamente.';
                $tipo = 'success';
            } else {
                $msg = 'Error al guardar: ' . htmlspecialchars($stmt->error);
                $tipo = 'danger';
            }
            $stmt->close();
        }
    }

    // UPDATE
    if ($action === 'actualizar') {
        $id          = (int)($_POST['id'] ?? 0);
        $categoria   = $_POST['categoria']   ?? '';
        $urgencia    = $_POST['urgencia']    ?? '';
        $asunto      = $_POST['asunto']      ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $comentario  = $_POST['comentario']  ?? '';
        $fecha_realizar = $_POST['fecha_realizar'] ?? '';

        if ($id <= 0 || $categoria === '' || $urgencia === '' || $asunto === '' || $fecha_realizar === '') {
            $msg = 'Los campos obligatorios deben ser completados.';
            $tipo = 'danger';
        } elseif (len($descripcion) > 40) {
            $msg = 'La descripción no puede exceder 40 caracteres.';
            $tipo = 'danger';
        } elseif (len($comentario) > 80) {
            $msg = 'El comentario no puede exceder 80 caracteres.';
            $tipo = 'danger';
        } else {
            $stmt = $link->prepare("UPDATE pendientes 
                SET categoria=?, urgencia=?, asunto=?, descripcion=?, comentario=?, fecha_realizar=? 
                WHERE id=?");
            $stmt->bind_param('ssssssi', $categoria, $urgencia, $asunto, $descripcion, $comentario, $fecha_realizar, $id);

            if ($stmt->execute()) {
                $msg = 'Tarea actualizada correctamente.';
                $tipo = 'success';
            } else {
                $msg = 'Error al actualizar: ' . htmlspecialchars($stmt->error);
                $tipo = 'danger';
            }
            $stmt->close();
        }
    }

    // DELETE con checkbox de confirmación
    if ($action === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        $confirmar = isset($_POST['confirmar_borrado']) ? true : false;

        if ($id <= 0) {
            $msg = 'ID de tarea inválido.';
            $tipo = 'danger';
        } elseif (!$confirmar) {
            $msg = 'Debe marcar el checkbox de confirmación para eliminar.';
            $tipo = 'warning';
        } else {
            $stmt = $link->prepare("DELETE FROM pendientes WHERE id = ?");
            $stmt->bind_param('i', $id);

            if ($stmt->execute()) {
                $msg = 'Tarea eliminada correctamente.';
                $tipo = 'success';
            } else {
                $msg = 'Error al eliminar: ' . htmlspecialchars($stmt->error);
                $tipo = 'danger';
            }
            $stmt->close();
        }
    }
}

// --- Cargar registro para edición ---
$edit = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $link->prepare("SELECT * FROM pendientes WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $edit = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- Consulta de tareas ---
$rows = [];
$result = $link->query("SELECT * FROM pendientes ORDER BY fecha_realizar ASC");
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }
}

// --- Estadísticas ---
$total = count($rows);
$dias_antigua = 0;

$conteo = [
    'Urgente'             => 0,
    'Importante'          => 0,
    'Urgente Importante'  => 0,
    'Importante No Urgente' => 0
];

if ($total > 0) {
    // Días desde la tarea más antigua (por fecha_realizar)
    $stmt = $link->prepare("SELECT MIN(fecha_realizar) as mas_antigua FROM pendientes");
    $stmt->execute();
    $res_antigua = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res_antigua['mas_antigua']) {
        $fecha_antigua = new DateTime($res_antigua['mas_antigua']);
        $ahora = new DateTime();
        $dias_antigua = (int)$ahora->diff($fecha_antigua)->format('%r%a');
        if ($dias_antigua < 0) $dias_antigua = 0;
    }

    // Conteo por categoría
    foreach ($rows as $r) {
        $u = $r['urgencia'] ?? '';
        if (isset($conteo[$u])) {
            $conteo[$u]++;
        }
    }
}

// Porcentajes
$pct = [];
foreach ($conteo as $k => $v) {
    $pct[$k] = $total > 0 ? round(($v / $total) * 100, 1) : 0;
}

// --- Footer info ---
$php_ver    = phpversion();
$mysqli_ver = $link->server_info ?? 'N/A';
$ip_cliente = $_SERVER['REMOTE_ADDR'] ?? 'N/A';

// Valores del cuadrante exactos al prompt
$urgencias = [
    'Urgente'               => 'Urgente',
    'Importante'            => 'Importante',
    'Urgente Importante'    => 'Urgente Importante',
    'Importante No Urgente' => 'Importante No Urgente'
];

// Colores Bootstrap para cada categoría
$urgencia_colores = [
    'Urgente'               => 'danger',
    'Importante'            => 'warning',
    'Urgente Importante'    => 'danger',
    'Importante No Urgente' => 'info'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Control de Pendientes</title>
<!-- Bootstrap 4.6.2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<!-- Font Awesome 5.15.4 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
<style>
  :root {
    --bg-body:    #1a1a2e;
    --bg-nav:     #16213e;
    --bg-card:    #0f3460;
    --bg-footer:  #0d0d1a;
    --accent:     #e94560;
    --text:       #e0e0e0;
    --text-muted: #9e9e9e;
    --border:     #2a2a4a;
  }

  html, body {
    height: 100%;
    background: var(--bg-body);
    color: var(--text);
  }

  body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    padding-top: 56px;
    padding-bottom: 48px;
  }

  main { flex: 1; }

  /* Navbar */
  .navbar {
    background: var(--bg-nav) !important;
    border-bottom: 2px solid var(--accent);
  }
  .navbar-brand {
    font-weight: 700;
    color: var(--accent) !important;
  }
  .navbar .dropdown-menu {
    background: var(--bg-card);
    border: 1px solid var(--border);
  }
  .navbar .dropdown-item {
    color: var(--text);
  }
  .navbar .dropdown-item:hover {
    background: var(--accent);
    color: #fff;
  }

  /* Cards */
  .card {
    background: var(--bg-card);
    border: 1px solid var(--border);
  }
  .card-header {
    background: rgba(0,0,0,.25);
    border-bottom: 1px solid var(--border);
    color: var(--accent);
    font-weight: 600;
  }

  /* Formularios */
  .form-control, .custom-select {
    background: #0d1b2a;
    border: 1px solid var(--border);
    color: var(--text);
  }
  .form-control:focus, .custom-select:focus {
    background: #0d1b2a;
    border-color: var(--accent);
    color: var(--text);
    box-shadow: 0 0 0 .15rem rgba(233,69,96,.25);
  }
  label { color: var(--text-muted); font-size: .85rem; }

  /* Tabla */
  .table { color: var(--text); }
  .table thead th {
    background: var(--bg-nav);
    border-color: var(--border);
    color: var(--accent);
    font-size: .8rem;
    text-transform: uppercase;
  }
  .table td { border-color: var(--border); vertical-align: middle; }

  /* Footer */
  footer {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    background: var(--bg-footer);
    border-top: 1px solid var(--border);
    color: var(--text-muted);
    font-size: .8rem;
    height: 48px;
    display: flex;
    align-items: center;
    z-index: 1000;
  }

  /* Stats */
  .stat-card {
    background: rgba(0,0,0,.3);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
  }
  .stat-days {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--accent);
  }

  /* Checkbox de confirmación */
  .confirm-delete {
    display: none;
    align-items: center;
    gap: .5rem;
    margin-top: .25rem;
  }
  .confirm-delete.show { display: flex; }

  /* Badges */
  .badge-urgente { background: #dc3545; }
  .badge-importante { background: #ffc107; color: #212529; }
  .badge-urgente-importante { background: #dc3545; }
  .badge-importante-no-urgente { background: #17a2b8; }
</style>
</head>
<body>

<!-- ================================================================ NAVBAR -->
<nav class="navbar navbar-expand fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= htmlspecialchars($self) ?>">
      <i class="fas fa-robot mr-2"></i>Modelo - Control de Pendientes
    </a>
    <div class="ml-auto d-flex align-items-center">
      <!-- Menú Dropdown -->
      <div class="dropdown mr-3">
        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
          <i class="fas fa-bars mr-1"></i>Menú
        </button>
        <div class="dropdown-menu dropdown-menu-right">
          <a class="dropdown-item" href="https://grok.x.ai" target="_blank">
            <i class="fas fa-robot mr-2"></i>Grok
          </a>
          <a class="dropdown-item" href="https://google.com" target="_blank">
            <i class="fab fa-google mr-2"></i>Google
          </a>
          <a class="dropdown-item" href="https://janice.everef.net" target="_blank">
            <i class="fas fa-search-dollar mr-2"></i>Janice (EVE Prices)
          </a>
          <div class="dropdown-divider" style="border-color: var(--border)"></div>
          <a class="dropdown-item" href="https://google.com" target="_blank">
            <i class="fas fa-chart-line mr-2"></i>Buscador de Precios EVE
          </a>
        </div>
      </div>
      <!-- Botón Salir -->
      <button class="btn btn-danger btn-sm" onclick="confirmarSalir()">
        <i class="fas fa-door-open mr-1"></i>Salir
      </button>
    </div>
  </div>
</nav>

<!-- ================================================================ MAIN -->
<main class="container-fluid py-3 px-3 px-md-4">

  <?php if ($msg): ?>
    <div class="alert alert-<?= $tipo ?> alert-dismissible fade show">
      <?= htmlspecialchars($msg) ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>

  <div class="row">
    <!-- FORMULARIO -->
    <div class="col-12 col-lg-4 mb-3">
      <div class="card">
        <div class="card-header">
          <i class="fas fa-<?= $edit ? 'edit' : 'plus-circle' ?> mr-2"></i>
          <?= $edit ? 'Editar Tarea' : 'Nueva Tarea' ?>
        </div>
        <div class="card-body">
          <form method="POST" action="<?= htmlspecialchars($self) ?>" id="formTarea">
            <input type="hidden" name="action" value="<?= $edit ? 'actualizar' : 'guardar' ?>">
            <?php if ($edit): ?>
              <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
              <label>Categoría <span class="text-danger">*</span></label>
              <input type="text" name="categoria" class="form-control form-control-sm" required
                     value="<?= htmlspecialchars($edit['categoria'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label>Urgencia <span class="text-danger">*</span></label>
              <select name="urgencia" class="custom-select custom-select-sm" required>
                <option value="">— Seleccione —</option>
                <?php foreach ($urgencias as $val => $lbl): ?>
                  <option value="<?= htmlspecialchars($val) ?>"
                    <?= (($edit['urgencia'] ?? '') === $val) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($lbl) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Asunto <span class="text-danger">*</span></label>
              <input type="text" name="asunto" class="form-control form-control-sm" required
                     value="<?= htmlspecialchars($edit['asunto'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label>Descripción <small class="text-muted">(máx. 40 car.)</small></label>
              <input type="text" name="descripcion" class="form-control form-control-sm"
                     maxlength="40"
                     value="<?= htmlspecialchars($edit['descripcion'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label>Comentario <small class="text-muted">(máx. 80 car.)</small></label>
              <input type="text" name="comentario" class="form-control form-control-sm"
                     maxlength="80"
                     value="<?= htmlspecialchars($edit['comentario'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label>Fecha a Realizar <span class="text-danger">*</span></label>
              <input type="datetime-local" name="fecha_realizar" class="form-control form-control-sm" required
                     value="<?= isset($edit['fecha_realizar']) ? date('Y-m-d\TH:i', strtotime($edit['fecha_realizar'])) : '' ?>">
            </div>

            <div class="d-flex">
              <button type="submit" class="btn btn-primary btn-sm mr-2">
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

    <!-- TABLA CRUD -->
    <div class="col-12 col-lg-8 mb-3">
      <div class="card">
        <div class="card-header d-flex justify-content-between">
          <span><i class="fas fa-list mr-2"></i>Tareas Pendientes</span>
          <span class="badge badge-secondary"><?= $total ?> registros</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Categoría</th>
                  <th>Urgencia</th>
                  <th>Asunto</th>
                  <th>Descripción</th>
                  <th>Comentario</th>
                  <th>Realizar</th>
                  <th style="min-width:120px">Acciones</th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($rows)): ?>
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>Sin tareas registradas
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= (int)$r['id'] ?></td>
                  <td><?= htmlspecialchars($r['categoria']) ?></td>
                  <td>
                    <span class="badge badge-<?= str_replace(' ', '-', strtolower($r['urgencia'])) ?>">
                      <?= htmlspecialchars($r['urgencia']) ?>
                    </span>
                  </td>
                  <td><?= htmlspecialchars($r['asunto']) ?></td>
                  <td><?= htmlspecialchars($r['descripcion']) ?></td>
                  <td><?= htmlspecialchars($r['comentario']) ?></td>
                  <td><?= date('d/m/Y H:i', strtotime($r['fecha_realizar'])) ?></td>
                  <td>
                    <a href="<?= htmlspecialchars($self) ?>?edit=<?= (int)$r['id'] ?>"
                       class="btn btn-sm btn-warning mb-1" title="Editar">
                      <i class="fas fa-edit"></i>
                    </a>

                    <!-- Botón borrar con confirmación checkbox -->
                    <button type="button" class="btn btn-sm btn-danger mb-1"
                            onclick="toggleConfirm(<?= (int)$r['id'] ?>)" title="Eliminar">
                      <i class="fas fa-trash"></i>
                    </button>

                    <form method="POST" action="<?= htmlspecialchars($self) ?>" 
                          class="confirm-delete" id="confirm-<?= (int)$r['id'] ?>">
                      <input type="hidden" name="action" value="eliminar">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="confirmar_borrado" 
                               class="custom-control-input" id="chk-<?= (int)$r['id'] ?>" required>
                        <label class="custom-control-label text-warning" 
                               for="chk-<?= (int)$r['id'] ?>" style="font-size:.75rem">Confirmar</label>
                      </div>
                      <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-check"></i>
                      </button>
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

      <!-- ESTADÍSTICAS -->
      <div class="card mt-3">
        <div class="card-header">
          <i class="fas fa-chart-pie mr-2"></i>Resumen - Cuadrante de Eisenhower
        </div>
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-12 col-sm-3 mb-3">
              <div class="stat-card">
                <div class="stat-days"><?= $dias_antigua ?></div>
                <div style="font-size:.8rem; color:var(--text-muted)">
                  días desde la tarea<br>más antigua
                </div>
              </div>
            </div>
            <div class="col-12 col-sm-9">
              <div class="row">
                <?php foreach ($urgencias as $key => $lbl): 
                  $count = $conteo[$key];
                  $porcentaje = $pct[$key];
                ?>
                <div class="col-6 col-md-3 mb-2">
                  <div class="stat-card" style="padding:.75rem">
                    <span class="badge badge-<?= str_replace(' ', '-', strtolower($key)) ?> mb-1">
                      <?= htmlspecialchars($lbl) ?>
                    </span>
                    <div class="h4 font-weight-bold mb-0"><?= $count ?></div>
                    <small style="color:var(--text-muted)"><?= $porcentaje ?>%</small>
                    <div class="progress mt-1" style="height:6px; background:#0d0d1a">
                      <div class="progress-bar bg-<?= $urgencia_colores[$key] ?>" 
                           style="width:<?= $porcentaje ?>%"></div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- ================================================================ FOOTER -->
<footer>
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <span>
      <i class="fas fa-code mr-1 text-muted"></i>PHP <?= htmlspecialchars($php_ver) ?>
    </span>
    <span>
      <i class="fas fa-database mr-1 text-muted"></i>MySQLi <?= htmlspecialchars($mysqli_ver) ?>
    </span>
    <span>
      <i class="fas fa-network-wired mr-1 text-muted"></i><?= htmlspecialchars($ip_cliente) ?>
    </span>
    <span class="text-muted" style="font-size:.7rem">
      <?= date('Y-m-d H:i') ?> · México
    </span>
  </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Confirmación de salida
function confirmarSalir() {
  if (confirm('¿Está usted seguro de salir?')) {
    window.location.href = 'https://google.com';
  }
}

// Toggle checkbox de confirmación para borrar
function toggleConfirm(id) {
  var el = document.getElementById('confirm-' + id);
  // Cerrar otros
  document.querySelectorAll('.confirm-delete').forEach(function(div) {
    if (div.id !== 'confirm-' + id) div.classList.remove('show');
  });
  el.classList.toggle('show');
}
</script>

</body>
</html>
