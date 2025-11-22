<?php
/**
 * =====================================================
 * ARCHIVO: conexion.php
 * DESCRIPCIÓN: Conexión a la base de datos MySQL
 * PROYECTO: Tienda Deportiva 420
 * =====================================================
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'tienda_deportiva_420');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración de zona horaria
date_default_timezone_set('America/Bogota');

/**
 * Clase para manejar la conexión a la base de datos
 */
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch(PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            die(json_encode([
                'success' => false,
                'message' => 'Error al conectar con la base de datos: ' . $e->getMessage()
            ]));
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    private function __clone() {}
    
    public function __wakeup() {
        throw new Exception("No se puede deserializar un singleton.");
    }
}

function getDB() {
    return Database::getInstance()->getConnection();
}

function ejecutarConsulta($sql, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Error en consulta: " . $e->getMessage());
        return false;
    }
}

function obtenerUno($sql, $params = []) {
    $stmt = ejecutarConsulta($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

function obtenerTodos($sql, $params = []) {
    $stmt = ejecutarConsulta($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function insertar($sql, $params = []) {
    $stmt = ejecutarConsulta($sql, $params);
    if ($stmt) {
        return getDB()->lastInsertId();
    }
    return false;
}

function limpiarEntrada($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verificarPassword($password, $hash) {
    return password_verify($password, $hash);
}

function respuestaJSON($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function registrarLog($mensaje, $tipo = 'info') {
    $fecha = date('Y-m-d H:i:s');
    $log = "[{$fecha}] [{$tipo}] {$mensaje}" . PHP_EOL;
    
    $archivo = __DIR__ . '/logs/app_' . date('Y-m-d') . '.log';
    
    if (!file_exists(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    file_put_contents($archivo, $log, FILE_APPEND);
}

function iniciarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function sesionActiva() {
    iniciarSesion();
    return isset($_SESSION['usuario_id']);
}

function obtenerUsuarioSesion() {
    if (!sesionActiva()) {
        return false;
    }
    
    $sql = "SELECT id, nombre, email, puntos, nivel FROM usuarios WHERE id = ?";
    return obtenerUno($sql, [$_SESSION['usuario_id']]);
}

function cerrarSesion() {
    iniciarSesion();
    session_unset();
    session_destroy();
}

function requerirAutenticacion() {
    if (!sesionActiva()) {
        header('Location: ../login.html');
        exit;
    }
}

function crearNotificacion($usuario_id, $tipo, $titulo, $mensaje) {
    $sql = "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES (?, ?, ?, ?)";
    return ejecutarConsulta($sql, [$usuario_id, $tipo, $titulo, $mensaje]) !== false;
}

function obtenerNotificacionesNoLeidas($usuario_id) {
    $sql = "SELECT * FROM notificaciones WHERE usuario_id = ? AND leida = 0 ORDER BY fecha_creacion DESC LIMIT 10";
    return obtenerTodos($sql, [$usuario_id]);
}

function marcarNotificacionLeida($notificacion_id) {
    $sql = "UPDATE notificaciones SET leida = 1 WHERE id = ?";
    return ejecutarConsulta($sql, [$notificacion_id]) !== false;
}

// Configuración de errores para desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>