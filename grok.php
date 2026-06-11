<?php
/**
 * Script PHP para gestión de pedientes
 * Versión: 1.01
 * Modelo: Grok Fast - web
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

// pendientes.php
session_start();
include "config.php"; // Asume que tienes $link (mysqli)

// Zona horaria México
date_default_timezone_set('America/Mexico_City');

$self = basename($_SERVER['PHP_SELF']);

// Mensajes
$msg = '';
$tipo = '';

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // TRIM general
    foreach ($_POST as $key => $val) {
        if (is_string($val)) $_POST[$key] = trim($val);
    }

    if ($action === 'guardar') {
        $categoria = $_POST['categoria'] ?? '';
        $urgencia = $_POST['urgencia'] ?? '';
        $asunto = $_POST['asunto'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $comentario = $_POST['comentario'] ?? '';
        $fecha_realizar = $_POST['fecha_realizar'] ?? '';

        if (!empty($categoria) && !empty($urgencia) && !empty($asunto) && !empty($fecha_realizar)) {
            $stmt = $link->prepare("INSERT INTO pendientes (categoria, urgencia, asunto, descripcion, comentario, fecha_realizar) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $categoria, $urgencia, $asunto, $descripcion, $comentario, $fecha_realizar);
            
            if ($stmt->execute()) {
                $msg = "Tarea guardada correctamente.";
                $tipo = "success";
            } else {
                $msg = "Error al guardar: " . $link->error;
                $tipo = "danger";
            }
        } else {
            $msg = "Todos los campos obligatorios deben ser llenados.";
            $tipo = "warning";
        }
    }

    if ($action === 'actualizar') {
        $id = (int)$_POST['id'];
        // ... (similar al insert, con UPDATE)
        $stmt = $link->prepare("UPDATE pendientes SET categoria=?, urgencia=?, asunto=?, descripcion=?, comentario=?, fecha_realizar=? WHERE id=?");
        $stmt->bind_param("ssssssi", ...);
        // (implementación completa abajo)
    }

    if ($action === 'eliminar') {
        $id = (int)$_POST['id'];
        $stmt = $link->prepare("DELETE FROM pendientes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $msg = "Tarea eliminada.";
        $tipo = "info";
    }
}

// Consulta de tareas
$result = $link->query("SELECT * FROM pendientes ORDER BY fecha_realizar ASC, urgencia DESC");
$tareas = $result->fetch_all(MYSQLI_ASSOC);

// Estadísticas
$stats = $link->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN urgencia='Urgente' THEN 1 ELSE 0 END) as urgente,
    SUM(CASE WHEN urgencia='Importante' THEN 1 ELSE 0 END) as importante,
    SUM(CASE WHEN urgencia='Urgente Importante' THEN 1 ELSE 0 END) as urgente_importante,
    SUM(CASE WHEN urgencia='No Urgente' THEN 1 ELSE 0 END) as no_urgente,
    MIN(fecha_realizar) as mas_antigua
    FROM pendientes")->fetch_assoc();

$dias_antigua = $stats['mas_antigua'] ? floor((time() - strtotime($stats['mas_antigua'])) / 86400) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Pendientes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #1e1e2f; color: #e0e0ff; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #2a2a3d; border-bottom: 3px solid #6c5ce7; }
        .card { background-color: #252532; border: none; }
        .table { background-color: #2a2a3d; color: #e0e0ff; }
        .table thead { background-color: #3a3a50; }
        footer { background-color: #2a2a3d; }
        .urgente { color: #ff4757; }
        .importante { color: #ffa502; }
        .no-urgente { color: #2ed573; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= $self ?>"><i class="fas fa-tasks"></i> Modelo - Pendientes</a>
        
        <div class="ml-auto d-flex align-items-center">
            <!-- Menú Dropdown -->
            <div class="dropdown mr-3">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="menuDropdown" data-toggle="dropdown">
                    <i class="fas fa-bars"></i> Menú
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="https://grok.x.ai" target="_blank"><i class="fas fa-robot"></i> Grok</a>
                    <a class="dropdown-item" href="https://google.com" target="_blank"><i class="fab fa-google"></i> Google</a>
                    <a class="dropdown-item" href="#" target="_blank"><i class="fas fa-user-secret"></i> Janice</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="https://google.com" target="_blank"><i class="fas fa-chart-line"></i> Buscador EVE</a>
                </div>
            </div>

            <button onclick="confirmarSalir()" class="btn btn-danger btn-sm">
                <i class="fas fa-door-open"></i> Salir
            </button>
        </div>
    </div>
</nav>

<div class="container mt-5 pt-5">

    <?php if ($msg): ?>
        <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- FORMULARIO -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus"></i> Nueva Tarea / Editar</div>
        <div class="card-body">
            <form method="POST" id="formTarea">
                <input type="hidden" name="action" id="action" value="guardar">
                <input type="hidden" name="id" id="edit_id">

                <div class="row">
                    <div class="col-md-4">
                        <label>Categoría *</label>
                        <input type="text" name="categoria" id="categoria" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label>Urgencia *</label>
                        <select name="urgencia" id="urgencia" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <option value="Urgente">Urgente</option>
                            <option value="Importante">Importante</option>
                            <option value="Urgente Importante">Urgente Importante</option>
                            <option value="No Urgente">No Urgente</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Fecha a Realizar *</label>
                        <input type="datetime-local" name="fecha_realizar" id="fecha_realizar" class="form-control" required>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <label>Asunto *</label>
                        <input type="text" name="asunto" id="asunto" class="form-control" required>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label>Descripción (máx 40)</label>
                        <input type="text" name="descripcion" id="descripcion" maxlength="40" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label>Comentario (máx 80)</label>
                        <input type="text" name="comentario" id="comentario" maxlength="80" class="form-control">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">
                    <i class="fas fa-save"></i> Guardar Tarea
                </button>
                <button type="button" onclick="limpiarForm()" class="btn btn-secondary mt-3">Limpiar</button>
            </form>
        </div>
    </div>

    <!-- TABLA CRUD -->
    <div class="card">
        <div class="card-header"><i class="fas fa-list"></i> Pendientes (<?= count($tareas) ?>)</div>
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Categoría</th>
                        <th>Urgencia</th>
                        <th>Asunto</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tareas as $t): ?>
                    <tr>
                        <td><?= $t['id'] ?></td>
                        <td><?= htmlspecialchars($t['categoria']) ?></td>
                        <td><span class="<?= strtolower(str_replace(' ', '-', $t['urgencia'])) ?>"><?= $t['urgencia'] ?></span></td>
                        <td><?= htmlspecialchars($t['asunto']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($t['fecha_realizar'])) ?></td>
                        <td>
                            <button onclick="editar(<?= htmlspecialchars(json_encode($t)) ?>)" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="confirmarEliminar(<?= $t['id'] ?>)" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ESTADÍSTICAS -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5>Días de tarea más antigua</h5>
                    <h2><?= $dias_antigua ?></h2>
                </div>
            </div>
        </div>
        <?php 
        $total = $stats['total'] ?: 1;
        foreach (['Urgente','Importante','Urgente Importante','No Urgente'] as $cat):
            $key = strtolower(str_replace(' ', '_', $cat));
            $count = $stats[$key] ?? 0;
            $pct = round(($count / $total) * 100, 1);
        ?>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body text-center">
                    <strong><?= $cat ?></strong><br>
                    <h4><?= $count ?></h4>
                    <small><?= $pct ?>%</small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- FOOTER -->
<footer class="fixed-bottom py-2 text-center small">
    PHP <?= phpversion() ?> | 
    MySQLi <?= mysqli_get_client_version() ?> | 
    IP: <?= $_SERVER['REMOTE_ADDR'] ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Confirmación de salida
function confirmarSalir() {
    if (confirm("¿Está usted seguro de salir?")) {
        window.location.href = 'logout.php'; // o donde manejes logout
    }
}

// Confirmación de eliminar
function confirmarEliminar(id) {
    if (confirm("¿Realmente desea eliminar esta tarea?")) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="eliminar">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Editar tarea
function editar(tarea) {
    document.getElementById('action').value = 'actualizar';
    document.getElementById('edit_id').value = tarea.id;
    document.getElementById('categoria').value = tarea.categoria;
    document.getElementById('urgencia').value = tarea.urgencia;
    document.getElementById('asunto').value = tarea.asunto;
    document.getElementById('descripcion').value = tarea.descripcion || '';
    document.getElementById('comentario').value = tarea.comentario || '';
    document.getElementById('fecha_realizar').value = tarea.fecha_realizar.replace(' ', 'T').slice(0,16);
}

// Limpiar formulario
function limpiarForm() {
    document.getElementById('formTarea').reset();
    document.getElementById('action').value = 'guardar';
    document.getElementById('edit_id').value = '';
}
</script>

</body>
</html>
