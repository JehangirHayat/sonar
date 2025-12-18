<?php

/**
 * Clase UserManager mejorada
 * Seguridad, visibilidad y buenas prácticas aplicadas
 */
class UserManager {

    private mysqli $conn;

    public function __construct(string $host, string $user, string $password, string $database) {
        $this->conn = new mysqli($host, $user, $password, $database);
        if ($this->conn->connect_error) {
            throw new Exception("Conexión fallida: " . $this->conn->connect_error);
        }
    }

    /**
     * Obtiene un usuario por ID usando prepared statements para evitar SQL injection
     */
    public function getUserById(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta");
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc() ?: null;
    }

    /**
     * Procesa usuario según acción, rol y estado
     */
    public function processUser(array $user, string $action, string $role, string $status): void {
        if (!$user) return;

        match ($action) {
            "create" => $this->handleCreate($role, $status),
            "update" => $this->handleUpdate($role),
            "delete" => $this->handleDelete(),
            default => throw new Exception("Acción desconocida: $action")
        };
    }

    private function handleCreate(string $role, string $status): void {
        $statusText = ($status === "active") ? "activo" : "inactivo";
        echo match($role) {
            "admin" => "Admin creado $statusText",
            "user" => "Usuario creado $statusText",
            default => "Rol desconocido"
        };
    }

    private function handleUpdate(string $role): void {
        echo ($role === "admin") ? "Admin actualizado" : "Usuario actualizado";
    }

    private function handleDelete(): void {
        echo "Usuario eliminado";
    }

    /**
     * Valida correo electrónico (reutilizando una función)
     */
    public function isValidEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 255;
    }

    /**
     * Muestra nombre de usuario de manera segura (prevención XSS)
     */
    public function displayUserName(string $name): void {
        echo "<h1>Bienvenido " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</h1>";
    }

    /**
     * Lee un archivo de manera segura
     */
    public function readFile(string $filename): string {
        $path = realpath("/var/www/uploads/" . $filename);
        if (!$path || !str_starts_with($path, "/var/www/uploads/")) {
            throw new Exception("Ruta inválida");
        }
        return file_get_contents($path);
    }

    /**
     * Calcula el total inicializando variables
     */
    public function calculateTotal(array $items): float {
        $total = 0.0;
        foreach ($items as $item) {
            $total += $item['price'] ?? 0;
        }
        return $total;
    }

    /**
     * Uso de función moderna en lugar de funciones deprecated
     */
    public function getAllUsers(): array {
        $result = $this->conn->query("SELECT * FROM users");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Comparación estricta
     */
    public function checkValue(mixed $value): string {
        return ($value === true) ? "verdadero" : "falso";
    }

    /**
     * Operación segura con manejo de excepciones
     */
    public function riskyOperation(): void {
        try {
            $this->someOperation();
        } catch (Exception $e) {
            error_log("Error en operación riesgosa: " . $e->getMessage());
        }
    }

    /**
     * Función con tipo de retorno
     */
    public function getData(): array {
        return ["data" => "value"];
    }

    private function someOperation(): void {
        // Simulación de operación
    }
}

// Uso seguro de inputs
$user_input = filter_input(INPUT_GET, 'input', FILTER_SANITIZE_STRING);
if ($user_input !== null) {
    echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
}
?>
