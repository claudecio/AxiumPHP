<?php
    namespace AxiumPHP\Core;

    use Throwable;
    use AxiumPHP\Core\LoggerService;

    class ErrorHandler {
        private LoggerService $logger;
        private bool $displayErrors;

        public function __construct(bool $displayErrors = false, ?string $logDir = null) {
            $this->displayErrors = $displayErrors;
            $this->logger = new LoggerService(driver: LoggerService::DRIVER_FILE, logDir: $logDir); // troca pra DATABASE depois se quiser

            set_error_handler(callback: [$this, 'handleError']);
            set_exception_handler(callback: [$this, 'handleException']);
            register_shutdown_function(callback: [$this, 'handleShutdown']);
        }

        public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool {
            $msg = "Erro [$errno]: $errstr em $errfile na linha $errline";

            $this->logger->error($msg, [
                'file' => $errfile,
                'line' => $errline,
                'code' => $errno
            ]);

            if ($this->displayErrors) {
                $this->outputError("Erro: $errstr", $errno, $errfile, $errline);
            }

            return true; // evita que o PHP trate
        }

        public function handleException(Throwable $exception): void {
            $msg = "Exceção: {$exception->getMessage()} em {$exception->getFile()} na linha {$exception->getLine()}";

            $this->logger->error($msg, [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'code' => $exception->getCode()
            ]);

            if ($this->displayErrors) {
                $this->outputError(
                    $exception->getMessage(),
                    $exception->getCode(),
                    $exception->getFile(),
                    $exception->getLine(),
                    $exception->getTraceAsString()
                );
            } else {
                $this->outputGenericError();
            }
        }

        public function handleShutdown(): void {
            $error = error_get_last();

            if ($error !== null) {
                $msg = "Fatal error: {$error['message']} em {$error['file']} na linha {$error['line']}";

                $this->logger->error($msg, [
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'type' => $error['type']
                ]);

                if ($this->displayErrors) {
                    $this->outputError($error['message'], $error['type'], $error['file'], $error['line']);
                } else {
                    $this->outputGenericError();
                }
            }
        }

        private function outputError(string $message, int $code, string $file, int $line, ?string $trace = null): void {
            $mode = Router::getMode();
            http_response_code(500);

            if ($mode === 'JSON') {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => true,
                    'message' => $message,
                    'code' => $code,
                    'file' => $file,
                    'line' => $line,
                    'trace' => $trace
                ]);
            } else {
                echo "<h2>Erro</h2>";
                echo "<p><strong>Mensagem:</strong> {$message}</p>";
                echo "<p><strong>Arquivo:</strong> {$file}</p>";
                echo "<p><strong>Linha:</strong> {$line}</p>";
                if ($trace) {
                    echo "<pre>{$trace}</pre>";
                }
            }
        }

        private function outputGenericError(): void {
            $mode = Router::getMode();
            http_response_code(500);

            if ($mode === 'JSON') {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => true,
                    'message' => 'Erro interno no servidor.'
                ]);
            } else {
                echo "Erro interno no servidor.";
            }
        }
    }