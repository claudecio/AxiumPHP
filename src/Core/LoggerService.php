<?php
    namespace AxiumPHP\Services;

    use PDO;
    use DateTime;
    use Exception;
    use AxiumPHP\Core\Database;

    class LoggerService {
        public const DRIVER_FILE = 'FILE';
        public const DRIVER_DATABASE = 'DATABASE';

        private string $driver;
        private string $logDir;
        private string $completeLogDir;

        public function __construct(string $driver = self::DRIVER_FILE, ?string $logDir = null) {
            $this->driver = strtoupper(string: $driver);

            if (!defined(constant_name: 'STORAGE_FOLDER_PATH')) {
                throw new Exception(message: "Constante 'STORAGE_FOLDER_PATH' não foi definida.");
            }

            $this->completeLogDir = STORAGE_FOLDER_PATH;
            $this->logDir = $logDir ? "{$this->completeLogDir}/{$logDir}" : "{$this->completeLogDir}/logs";

            if ($this->driver === self::DRIVER_DATABASE) {
                Database::connect();
            }

            if ($this->driver === self::DRIVER_FILE && !is_dir(filename: $this->logDir)) {
                mkdir(directory: $this->logDir, permissions: 0775, recursive: true);
            }
        }

        public function log(string $message, string $level = 'INFO', array $context = []): void {
            switch ($this->driver) {
                case self::DRIVER_FILE:
                    $this->logToFile(message: $message, level: $level);
                break;

                case self::DRIVER_DATABASE:
                    $this->logToDatabase(message: $message, level: $level, context: $context);
                break;
            }
        }

        public function info(string $message, array $context = []): void {
            $this->log(message: $message, level: 'INFO', context: $context);
        }

        public function warning(string $message, array $context = []): void {
            $this->log(message: $message, level: 'WARNING', context: $context);
        }

        public function error(string $message, array $context = []): void {
            $this->log(message: $message, level: 'ERROR', context: $context);
        }

        public function debug(string $message, array $context = []): void {
            $this->log(message: $message, level: 'DEBUG', context: $context);
        }

        private function logToFile(string $message, string $level): void {
            $date = (new DateTime)->format(format: 'Y-m-d');
            $now = (new DateTime)->format(format: 'Y-m-d H:i:s');
            $filename = "{$this->logDir}/app-{$date}.log";
            $logMessage = "[$now][$level] $message" . PHP_EOL;
            file_put_contents(filename: $filename, data: $logMessage, flags: FILE_APPEND);
        }

        private function logToDatabase(string $message, string $level, array $context = []): void {
            $sql = 
            "INSERT INTO logs (
                    level,
                    message,
                    context,
                    created_at
                ) VALUES (
                    :level, 
                    :message, 
                    :context, 
                    :created_at
                )
            ";

            $stmt = Database::prepare(sql: $sql);
            $stmt->execute(params: [
                ':level' => $level,
                ':message' => $message,
                ':context' => json_encode(value: $context),
                ':created_at' => (new DateTime)->format(format: 'Y-m-d H:i:s')
            ]);
        }
    }