<?php
    namespace AxiumPHP\Core;

    use Throwable;

    class ErrorHandler {
        private bool $displayErrors;

        public function __construct(bool $displayErrors = false) {
            $this->displayErrors = $displayErrors;

            set_error_handler(callback: [$this, 'handleError']);
            set_exception_handler(callback: [$this, 'handleException']);
            register_shutdown_function(callback: [$this, 'handleShutdown']);
        }

        public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool {
            $msg = "Erro [$errno]: $errstr em $errfile na linha $errline";

            LoggerService::error(message: $msg, context: [
                'file' => $errfile,
                'line' => $errline,
                'code' => $errno
            ]);

            if ($this->displayErrors) {
                $this->outputError(message: "Erro: $errstr", code: $errno, file: $errfile, line: $errline);
            }

            return true; // evita que o PHP trate
        }

        public function handleException(Throwable $exception): void {
            $msg = "Exceção: {$exception->getMessage()} em {$exception->getFile()} na linha {$exception->getLine()}";

            LoggerService::error(message: $msg, context: [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'code' => $exception->getCode()
            ]);

            $code = is_numeric(value: $exception->getCode()) ? (int) $exception->getCode() : 500;

            if ($this->displayErrors) {
                $this->outputError(
                    message: $exception->getMessage(),
                    code: $code,
                    file: $exception->getFile(),
                    line: $exception->getLine(),
                    trace: $exception->getTraceAsString()
                );
            } else {
                $this->outputGenericError();
            }
        }

        public function handleShutdown(): void {
            $error = error_get_last();

            if ($error !== null) {
                $msg = "Fatal error: {$error['message']} em {$error['file']} na linha {$error['line']}";

                LoggerService::error(message: $msg, context: [
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'type' => $error['type']
                ]);

                $code = is_numeric(value: $error['type']) ? (int) $error['type'] : 500;

                if ($this->displayErrors) {
                    $this->outputError(message: $error['message'], code: $code, file: $error['file'], line: $error['line']);
                } else {
                    $this->outputGenericError();
                }
            }
        }

        private function outputError(string $message, int $code, string $file, int $line, ?string $trace = null): void {
            $mode = Router::getMode();
            http_response_code(response_code: 500);

            if ($mode === 'JSON') {
                header(header: 'Content-Type: application/json');
                echo json_encode(value: [
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
            http_response_code(response_code: 500);

            if ($mode === 'JSON') {
                header(header: 'Content-Type: application/json');
                echo json_encode(value: [
                    'error' => true,
                    'message' => 'Erro interno no servidor.'
                ]);
            } else {
                echo "Erro interno no servidor.";
            }
        }
    }