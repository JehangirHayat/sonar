<?php

/**
 * Clase de ejemplo con múltiples problemas de calidad
 * que SonarQube debería detectar
 */
class UserManager {
    
    // Variable no utilizada
    private $unusedVariable = "no se usa";
    
    // Falta especificar visibilidad
    var $oldStyleVariable;
    
    private $conn;
    
    public function __construct() {
        // Credenciales hardcodeadas (security hotspot)
        $this->conn = mysqli_connect("localhost", "root", "password123", "database");
    }
    
    /**
     * Método con SQL injection vulnerable
     */
    public function getUserById($id) {
        // SQL Injection - no se usa prepared statement
        $query = "SELECT * FROM users WHERE id = " . $id;
        $result = mysqli_query($this->conn, $query);
        return mysqli_fetch_assoc($result);
    }
    
    /**
     * Método con complejidad ciclomática alta
     */
    public function processUser($user, $action, $role, $status) {
        // Demasiados niveles de anidamiento
        if ($user != null) {
            if ($action == "create") {
                if ($role == "admin") {
                    if ($status == "active") {
                        echo "Admin creado activo";
                    } else {
                        echo "Admin creado inactivo";
                    }
                } else if ($role == "user") {
                    if ($status == "active") {
                        echo "Usuario creado activo";
                    } else {
                        echo "Usuario creado inactivo";
                    }
                }
            } else if ($action == "update") {
                if ($role == "admin") {
                    echo "Admin actualizado";
                } else {
                    echo "Usuario actualizado";
                }
            } else if ($action == "delete") {
                echo "Usuario eliminado";
            }
        }
    }
    
    /**
     * Función con código duplicado
     */
    public function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (strlen($email) > 255) {
            return false;
        }
        return true;
    }
    
    /**
     * Función con código duplicado (casi idéntica a la anterior)
     */
    public function checkEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (strlen($email) > 255) {
            return false;
        }
        return true;
    }
    
    /**
     * XSS vulnerability - no sanitiza output
     */
    public function displayUserName($name) {
        echo "<h1>Bienvenido " . $name . "</h1>";
    }
    
    /**
     * Método que no maneja excepciones
     */
    public function readFile($filename) {
        // Path traversal vulnerability
        $content = file_get_contents("/var/www/uploads/" . $filename);
        return $content;
    }
    
    /**
     * Variable no inicializada
     */
    public function calculateTotal($items) {
        // $total no está inicializada
        foreach ($items as $item) {
            $total += $item['price'];
        }
        return $total;
    }
    
    /**
     * Uso de funciones deprecadas
     */
    public function oldFunction() {
        // mysql_* está deprecado
        $result = mysql_query("SELECT * FROM users");
        return mysql_fetch_array($result);
    }
    
    /**
     * Comparación débil cuando debería ser estricta
     */
    public function checkValue($value) {
        if ($value == true) { // Debería usar ===
            return "verdadero";
        }
        return "falso";
    }
    
    /**
     * Bloque catch vacío (code smell)
     */
    public function riskyOperation() {
        try {
            $result = $this->someOperation();
        } catch (Exception $e) {
            // Catch vacío - mala práctica
        }
    }
    
    /**
     * Función sin return type hint
     */
    public function getData() {
        return ["data" => "value"];
    }
    
    // Constante no utilizada
    const UNUSED_CONSTANT = "nunca usado";
}

// Código en el scope global (code smell)
$globalVar = "variable global";

// Variable superglobal sin sanitizar
$user_input = $_GET['input'];
echo $user_input;

?>