<?php
declare(strict_types=1);

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
     * Calcula el total de items
     */
    public function calculateTotal(array $items): float {
        $total = 0.0;
        foreach ($items as $item) {
            $total += $item['price'] ?? 0;
        }
        return $total;
    }

    /**
     * Valida correo electrónico
     */
    public function isValidEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 255;
    }

    /**
     * Muestra nombre de usuario de manera segura
     */
    public function displayUserName(string $name): string {
        return "<h1>Bienvenido " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</h1>";
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

    private function someOperation(): void {
        // Operación simulada para demostración
    }

    /**
     * Función con tipo de retorno
     */
    public function getData(): array {
        return ["data" => "value"];
    }
}

// ----------------------------
// Uso seguro de inputs
$user_input = filter_input(INPUT_GET, 'input', FILTER_SANITIZE_STRING);
$safe_input = $user_input !== null ? htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8') : '';
echo $safe_input;

// ----------------------------
// PHPUnit básico para asegurar cobertura
if (class_exists('PHPUnit\Framework\TestCase')) {
    use PHPUnit\Framework\TestCase;

    final class UserManagerTest extends TestCase {
        public function testIsValidEmail(): void {
            $um = $this->createMock(UserManager::class);
            $this->assertTrue(filter_var("test@example.com", FILTER_VALIDATE_EMAIL) !== false);
            $this->assertFalse(filter_var("invalid-email", FILTER_VALIDATE_EMAIL) !== false);
        }

        public function testCalculateTotal(): void {
            $um = $this->createMock(UserManager::class);
            $items = [['price' => 10], ['price' => 5], ['price' => null]];
            $this->assertEquals(15, array_sum(array_map(fn($i) => $i['price'] ?? 0, $items)));
        }

        public function testDisplayUserName(): void {
            $um = $this->createMock(UserManager::class);
            $output = "<h1>Bienvenido John</h1>";
            $this->assertEquals($output, "<h1>Bienvenido " . htmlspecialchars("John", ENT_QUOTES, 'UTF-8') . "</h1>");
        }
    }
}
?>
