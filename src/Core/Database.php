<?php
    namespace AxiumPHP\Core;

    use PDO;
    use PDOException;
    use PDOStatement;
    use AxiumPHP\Core\EnvLoader;

    class Database {
        public static array $connections = [];

        public static function connect(string $connectionName = 'default'): void {
            if(!isset(self::$connections[$connectionName])) {
                try {
                    $driver = $_ENV[strtoupper(string: "{$connectionName}_DATABASE_DRIVER")];
                    $host = $_ENV[strtoupper(string: "{$connectionName}_DATABASE_HOST")];
                    $dbschema = $_ENV[strtoupper(string: "{$connectionName}_DATABASE_SCHEMA")];
                    $username = $_ENV[strtoupper(string: "{$connectionName}_DATABASE_USERNAME")];
                    $password = $_ENV[strtoupper(string: "{$connectionName}_DATABASE_PASSWORD")];
                    $port = $_ENV[strtoupper(string: "{$connectionName}_DATABASE_PORT")];
                    $charset = $_ENV[strtoupper(string: "{$connectionName}_DATABASE_CHARSET")];

                    $dsn = "{$driver}:host={$host};port={$port};dbname={$dbschema};charset={$charset}";
                    $options = [
                        PDO::ATTR_PERSISTENT => true,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ];

                    self::$connections[$connectionName] = new PDO(dsn: $dsn, username: $username, password: $password, options: $options);
                } catch (PDOException $e) {
                    die("Erro na conexão ({$connectionName}): {$e->getMessage()}");
                }
            }
        }

        public static function getConnection(string $connectionName = 'default'): PDO {
            self::connect(connectionName: $connectionName);
            return self::$connections[$connectionName];
        }

        public static function prepare(string $sql, string $connectionName = 'default'): PDOStatement {
            return self::getConnection(connectionName: $connectionName)->prepare(query: $sql);
        }

        public static function execute(string $sql, array $params = [], string $connectionName = 'default'): bool {
            $stmt = self::prepare(sql: $sql, connectionName: $connectionName);
            return $stmt->execute(params: $params);
        }

        public static function fetchOne(string $sql, array $params = [], string $connectionName = 'default'): ?array {
            $stmt = self::prepare(sql: $sql, connectionName: $connectionName);
            $stmt->execute(params: $params);
            $result = $stmt->fetch(mode: PDO::FETCH_ASSOC);
            return $result ?: null;
        }

        public static function fetchAll(string $sql, array $params = [], string $connectionName = 'default'): array {
            $stmt = self::prepare(sql: $sql, connectionName: $connectionName);
            $stmt->execute(params: $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public static function lastInsertId(string $connectionName = 'default'): string {
            return self::getConnection(connectionName: $connectionName)->lastInsertId();
        }

        public static function beginTransaction(string $connectionName = 'default'): void {
            $conn = self::getConnection(connectionName: $connectionName);
            if (!$conn->inTransaction()) {
                $conn->beginTransaction();
            }
        }

        public static function commit(string $connectionName = 'default'): void {
            $conn = self::getConnection(connectionName: $connectionName);
            if ($conn->inTransaction()) {
                $conn->commit();
            }
        }

        public static function rollback(string $connectionName = 'default'): void {
            $conn = self::getConnection(connectionName: $connectionName);
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
        }
    }