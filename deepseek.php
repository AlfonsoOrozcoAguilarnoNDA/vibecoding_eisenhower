<?php
/**
 * Script PHP para gestión de pedientes
 * Versión: 1.01
 * Modelo: Deepseek instant - web
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
die ("Inseguro no usar");
// config.php - Configuración de la base de datos
// Incluir este archivo antes de usar $link

// Configuración de la base de datos
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'task_manager';

// Crear conexión
$link = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($link->connect_error) {
    die("Error de conexión: " . $link->connect_error);
}

// Establecer zona horaria de México
date_default_timezone_set('America/Mexico_City');

// Crear tabla si no existe
$create_table = "CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    urgency ENUM('urgente', 'importante', 'urgente_importante', 'importante_no_urgente') NOT NULL,
    subject VARCHAR(40) NOT NULL,
    description VARCHAR(40) NOT NULL,
    comment VARCHAR(80),
    due_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$link->query($create_table);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Control de Pendientes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <style>
        body {
            background-color: #1a1a1a;
            color: #e0e0e0;
            padding-top: 70px;
            padding-bottom: 60px;
        }
        
        .navbar {
            background-color: #2c2c2c;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .navbar-brand, .nav-link {
            color: #e0e0e0 !important;
        }
        
        .nav-link:hover {
            color: #ffc107 !important;
        }
        
        .dropdown-menu {
            background-color: #2c2c2c;
        }
        
        .dropdown-item {
            color: #e0e0e0;
        }
        
        .dropdown-item:hover {
            background-color: #3c3c3c;
            color: #ffc107;
        }
        
        .card {
            background-color: #2c2c2c;
            border: 1px solid #404040;
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: #3c3c3c;
            border-bottom: 1px solid #404040;
            color: #ffc107;
        }
        
        .form-control {
            background-color: #3c3c3c;
            border: 1px solid #505050;
            color: #e0e0e0;
        }
        
        .form-control:focus {
            background-color: #3c3c3c;
            color: #e0e0e0;
            border-color: #ffc107;
        }
        
        .table {
            color: #e0e0e0;
        }
        
        .table thead th {
            border-bottom: 2px solid #404040;
        }
        
        .btn-primary {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #1a1a1a;
        }
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #1a1a1a;
        }
        
        footer {
            background-color: #2c2c2c;
            position: fixed;
            bottom: 0;
            width: 100%;
            padding: 10px 0;
            text-align: center;
            border-top: 1px solid #404040;
        }
        
        .stats-card {
            background-color: #3c3c3c;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .confirm-checkbox {
            margin-top: 10px;
            margin-bottom: 10px;
        }
        
        textarea {
            resize: vertical;
        }
        
        .modal-content {
            background-color: #2c2c2c;
            color: #e0e0e0;
        }
        
        .close {
            color: #e0e0e0;
        }
    </style>
</head>
<body>
    <!-- Barra fija superior -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <a class="navbar-brand" href="#">
            <i class="fa fa-tasks"></i> Modelo Task Manager v1.0
        </a>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <!-- Dropdown de enlaces -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                        <i class="fa fa-link"></i> Enlaces Rápidos
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="https://grok.com" target="_blank">
                            <i class="fa fa-graduation-cap"></i> Grok
                        </a>
                        <a class="dropdown-item" href="https://google.com" target="_blank">
                            <i class="fa fa-google"></i> Google
                        </a>
                        <a class="dropdown-item" href="https://janice.eveindustry.com" target="_blank">
                            <i class="fa fa-industry"></i> Janice
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="https://google.com" target="_blank">
                            <i class="fa fa-search"></i> Buscador de Precios
                        </a>
                    </div>
                </li>
                
                <!-- Botón Salir -->
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="confirmLogout()">
                        <i class="fa fa-sign-out"></i> Salir
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <!-- Formulario CRUD -->
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fa fa-plus-circle"></i> <?php echo isset($_GET['edit']) ? 'Editar Tarea' : 'Nueva Tarea'; ?></h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="taskForm">
                            <input type="hidden" name="task_id" id="task_id" value="<?php echo isset($_GET['edit']) ? intval($_GET['edit']) : ''; ?>">
                            
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Categoría <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="category" id="category" required maxlength="100" 
                                           value="<?php echo isset($edit_task['category']) ? htmlspecialchars($edit_task['category']) : ''; ?>">
                                </div>
                                
                                <div class="form-group col-md-4">
                                    <label>Urgencia <span class="text-danger">*</span></label>
                                    <select class="form-control" name="urgency" id="urgency" required>
                                        <option value="">Seleccione...</option>
                                        <option value="urgente" <?php echo (isset($edit_task['urgency']) && $edit_task['urgency'] == 'urgente') ? 'selected' : ''; ?>>Urgente</option>
                                        <option value="importante" <?php echo (isset($edit_task['urgency']) && $edit_task['urgency'] == 'importante') ? 'selected' : ''; ?>>Importante</option>
                                        <option value="urgente_importante" <?php echo (isset($edit_task['urgency']) && $edit_task['urgency'] == 'urgente_importante') ? 'selected' : ''; ?>>Urgente Importante</option>
                                        <option value="importante_no_urgente" <?php echo (isset($edit_task['urgency']) && $edit_task['urgency'] == 'importante_no_urgente') ? 'selected' : ''; ?>>Importante No Urgente</option>
                                    </select>
                                </div>
                                
                                <div class="form-group col-md-4">
                                    <label>Asunto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="subject" id="subject" required maxlength="40"
                                           value="<?php echo isset($edit_task['subject']) ? htmlspecialchars($edit_task['subject']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Descripción (40 caracteres máx) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="description" id="description" required maxlength="40"
                                           value="<?php echo isset($edit_task['description']) ? htmlspecialchars($edit_task['description']) : ''; ?>">
                                </div>
                                
                                <div class="form-group col-md-6">
                                    <label>Comentario (80 caracteres máx)</label>
                                    <textarea class="form-control" name="comment" id="comment" maxlength="80" rows="2"><?php echo isset($edit_task['comment']) ? htmlspecialchars($edit_task['comment']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Fecha de Realización <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="due_date" id="due_date" required
                                           value="<?php echo isset($edit_task['due_date']) ? date('Y-m-d', strtotime($edit_task['due_date'])) : ''; ?>">
                                </div>
                            </div>
                            
                            <button type="submit" name="save_task" class="btn btn-primary">
                                <i class="fa fa-save"></i> Guardar Tarea
                            </button>
                            
                            <?php if(isset($_GET['edit'])): ?>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
                                    <i class="fa fa-times"></i> Cancelar
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Tabla de tareas -->
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fa fa-list"></i> Lista de Tareas Pendientes</h4>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Categoría</th>
                                    <th>Urgencia</th>
                                    <th>Asunto</th>
                                    <th>Descripción</th>
                                    <th>Comentario</th>
                                    <th>Fecha Límite</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT * FROM tasks ORDER BY due_date ASC";
                                $result = $link->query($query);
                                
                                if($result->num_rows > 0):
                                    while($row = $result->fetch_assoc()):
                                        $urgency_class = '';
                                        switch($row['urgency']) {
                                            case 'urgente':
                                                $urgency_class = 'danger';
                                                break;
                                            case 'importante':
                                                $urgency_class = 'warning';
                                                break;
                                            case 'urgente_importante':
                                                $urgency_class = 'danger';
                                                break;
                                            case 'importante_no_urgente':
                                                $urgency_class = 'info';
                                                break;
                                        }
                                ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td><span class="badge badge-<?php echo $urgency_class; ?>"><?php echo str_replace('_', ' ', $row['urgency']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td><?php echo htmlspecialchars($row['comment']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['due_date'])); ?></td>
                                    <td>
                                        <a href="<?php echo $_SERVER['PHP_SELF'] . '?edit=' . $row['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="8" class="text-center">No hay tareas registradas</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Estadísticas -->
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fa fa-chart-bar"></i> Estadísticas</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            // Días de la tarea más antigua
                            $oldest_query = "SELECT MIN(due_date) as oldest_date FROM tasks";
                            $oldest_result = $link->query($oldest_query);
                            $oldest_row = $oldest_result->fetch_assoc();
                            $oldest_date = $oldest_row['oldest_date'];
                            
                            if($oldest_date) {
                                $oldest_datetime = new DateTime($oldest_date);
                                $now = new DateTime();
                                $diff = $now->diff($oldest_datetime);
                                $days_oldest = $diff->days;
                            } else {
                                $days_oldest = 0;
                            }
                            ?>
                            
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <h5><i class="fa fa-calendar"></i> Días Tarea Más Antigua</h5>
                                    <h2><?php echo $days_oldest; ?></h2>
                                    <small>días desde la tarea más antigua</small>
                                </div>
                            </div>
                            
                            <?php
                            // Estadísticas por categoría de urgencia
                            $total_query = "SELECT COUNT(*) as total FROM tasks";
                            $total_result = $link->query($total_query);
                            $total_tasks = $total_result->fetch_assoc()['total'];
                            
                            $urgency_stats = [];
                            $urgencies = ['urgente', 'importante', 'urgente_importante', 'importante_no_urgente'];
                            
                            foreach($urgencies as $urgency) {
                                $count_query = "SELECT COUNT(*) as count FROM tasks WHERE urgency = '$urgency'";
                                $count_result = $link->query($count_query);
                                $count = $count_result->fetch_assoc()['count'];
                                
                                $percentage = $total_tasks > 0 ? round(($count / $total_tasks) * 100, 1) : 0;
                                $urgency_stats[$urgency] = ['count' => $count, 'percentage' => $percentage];
                            }
                            ?>
                            
                            <div class="col-md-9">
                                <div class="row">
                                    <?php foreach($urgency_stats as $urgency => $stats): ?>
                                    <div class="col-md-3">
                                        <div class="stats-card">
                                            <h5><?php echo ucfirst(str_replace('_', ' ', $urgency)); ?></h5>
                                            <h3><?php echo $stats['count']; ?></h3>
                                            <small><?php echo $stats['percentage']; ?>% del total</small>
                                            <div class="progress mt-2">
                                                <div class="progress-bar" style="width: <?php echo $stats['percentage']; ?>%"></div>
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
    </div>
    
    <!-- Footer fijo -->
    <footer>
        <div class="container">
            <span>PHP: <?php echo phpversion(); ?> | MySQLi: <?php echo $link->server_info; ?> | IP: <?php echo $_SERVER['SERVER_ADDR']; ?></span>
        </div>
    </footer>
    
    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar esta tarea?</p>
                    <div class="form-check confirm-checkbox">
                        <input type="checkbox" class="form-check-input" id="confirmDeleteCheck">
                        <label class="form-check-label" for="confirmDeleteCheck">
                            Confirmo que deseo eliminar esta tarea permanentemente
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let taskToDelete = null;
        
        // Confirmar eliminación con checkbox
        function confirmDelete(taskId) {
            taskToDelete = taskId;
            $('#deleteModal').modal('show');
            $('#confirmDeleteCheck').prop('checked', false);
            $('#confirmDeleteBtn').prop('disabled', true);
        }
        
        // Habilitar botón de eliminar cuando se checkea el checkbox
        $('#confirmDeleteCheck').change(function() {
            $('#confirmDeleteBtn').prop('disabled', !$(this).is(':checked'));
        });
        
        // Eliminar tarea
        $('#confirmDeleteBtn').click(function() {
            if(taskToDelete) {
                window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?delete=' + taskToDelete;
            }
        });
        
        // Confirmar salida
        function confirmLogout() {
            if(confirm('¿Está seguro que desea salir del sistema?')) {
                window.location.href = 'https://www.google.com';
            }
        }
        
        // Validar formulario antes de enviar
        $('#taskForm').submit(function(e) {
            let isValid = true;
            let errorMessage = '';
            
            // Validar campos requeridos
            if(!$('#category').val().trim()) {
                errorMessage += '- La categoría es obligatoria\n';
                isValid = false;
            }
            
            if(!$('#urgency').val()) {
                errorMessage += '- La urgencia es obligatoria\n';
                isValid = false;
            }
            
            if(!$('#subject').val().trim()) {
                errorMessage += '- El asunto es obligatorio\n';
                isValid = false;
            }
            
            if(!$('#description').val().trim()) {
                errorMessage += '- La descripción es obligatoria\n';
                isValid = false;
            }
            
            if(!$('#due_date').val()) {
                errorMessage += '- La fecha de realización es obligatoria\n';
                isValid = false;
            }
            
            if(!isValid) {
                alert('Por favor corrija los siguientes errores:\n' + errorMessage);
                e.preventDefault();
                return false;
            }
            
            // Trim a todos los campos de texto
            $('#category').val($('#category').val().trim());
            $('#subject').val($('#subject').val().trim());
            $('#description').val($('#description').val().trim());
            if($('#comment').val()) {
                $('#comment').val($('#comment').val().trim());
            }
        });
    </script>
</body>
</html>

<?php
// Procesar formulario CRUD
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_task'])) {
    $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
    $category = trim($_POST['category']);
    $urgency = $_POST['urgency'];
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $due_date = $_POST['due_date'] . ' 23:59:59'; // Hora de México
    
    // Validar campos obligatorios
    if(!empty($category) && !empty($urgency) && !empty($subject) && !empty($description) && !empty($due_date)) {
        if($task_id > 0) {
            // Actualizar tarea
            $update_query = "UPDATE tasks SET 
                            category = '$category',
                            urgency = '$urgency',
                            subject = '$subject',
                            description = '$description',
                            comment = '$comment',
                            due_date = '$due_date'
                            WHERE id = $task_id";
            
            if($link->query($update_query)) {
                echo "<script>alert('Tarea actualizada correctamente'); window.location.href='{$_SERVER['PHP_SELF']}';</script>";
            } else {
                echo "<script>alert('Error al actualizar la tarea: {$link->error}');</script>";
            }
        } else {
            // Insertar nueva tarea
            $insert_query = "INSERT INTO tasks (category, urgency, subject, description, comment, due_date) 
                            VALUES ('$category', '$urgency', '$subject', '$description', '$comment', '$due_date')";
            
            if($link->query($insert_query)) {
                echo "<script>alert('Tarea guardada correctamente'); window.location.href='{$_SERVER['PHP_SELF']}';</script>";
            } else {
                echo "<script>alert('Error al guardar la tarea: {$link->error}');</script>";
            }
        }
    } else {
        echo "<script>alert('Por favor complete todos los campos obligatorios');</script>";
    }
}

// Procesar eliminación
if(isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $delete_query = "DELETE FROM tasks WHERE id = $delete_id";
    
    if($link->query($delete_query)) {
        echo "<script>alert('Tarea eliminada correctamente'); window.location.href='{$_SERVER['PHP_SELF']}';</script>";
    } else {
        echo "<script>alert('Error al eliminar la tarea: {$link->error}');</script>";
    }
}

// Cargar datos para edición
if(isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_query = "SELECT * FROM tasks WHERE id = $edit_id";
    $edit_result = $link->query($edit_query);
    
    if($edit_result->num_rows > 0) {
        $edit_task = $edit_result->fetch_assoc();
    }
}
?>
