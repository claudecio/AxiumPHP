<?php
    namespace AxiumPHP\Core;

    use DateTime;
    use Exception;
    use AxiumPHP\Core\Database;

    class LoggerService {
        public const DRIVER_FILE = 'FILE';
        public const DRIVER_DATABASE = 'DATABASE';

        private static string $driver = self::DRIVER_FILE;
        private static string $logDir;
        private static bool $initialized = false;

        public static function init(string $driver = self::DRIVER_FILE, ?string $logDir = null): void {
            self::$driver = strtoupper($driver);

            if (!defined('STORAGE_FOLDER_PATH')) {
                throw new Exception("Constante 'STORAGE_FOLDER_PATH' não foi definida.");
            }

            $baseDir = STORAGE_FOLDER_PATH;
            self::$logDir = $logDir ? "{$baseDir}/{$logDir}" : "{$baseDir}/logs";

            if (self::$driver === self::DRIVER_DATABASE) {
                Database::connect();
            }

            if (self::$driver === self::DRIVER_FILE && !is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0775, true);
            }

            self::$initialized = true;
        }

        public static function log(string $message, string $level = 'INFO', array $context = []): void {
            if (!self::$initialized) {
                throw new Exception("LoggerService não foi inicializado. Chame LoggerService::init() antes.");
            }

            switch (self::$driver) {
                case self::DRIVER_FILE:
                    self::logToFile($message, $level);
                    break;
                case self::DRIVER_DATABASE:
                    self::logToDatabase($message, $level, $context);
                    break;
            }
        }

        public static function info(string $message, array $context = []): void {
            self::log($message, 'INFO', $context);
        }

        public static function warning(string $message, array $context = []): void {
            self::log($message, 'WARNING', $context);
        }

        public static function error(string $message, array $context = []): void {
            self::log($message, 'ERROR', $context);
        }

        public static function debug(string $message, array $context = []): void {
            self::log($message, 'DEBUG', $context);
        }

        private static function logToFile(string $message, string $level): void {
            $date = (new DateTime)->format('Y-m-d');
            $now = (new DateTime)->format('Y-m-d H:i:s');
            $filename = self::$logDir . "/app-{$date}.log";
            $logMessage = "[$now][$level] $message" . PHP_EOL;
            file_put_contents($filename, $logMessage, FILE_APPEND);
        }

        private static function logToDatabase(string $message, string $level, array $context = []): void {
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
                )";

            $stmt = Database::prepare($sql);
            $stmt->execute([
                ':level' => $level,
                ':message' => $message,
                ':context' => json_encode($context),
                ':created_at' => (new DateTime)->format('Y-m-d H:i:s')
            ]);
        }
    }