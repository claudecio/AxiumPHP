<?php
    namespace AxiumPHP\Core;

    use Dotenv\Dotenv;
    use RuntimeException;

    class EnvLoader {
        /**
         * Carrega as variáveis de ambiente de um arquivo .env.
         *
         * Este método estático verifica se o arquivo especificado no `$path` existe.
         * Se o arquivo não for encontrado, ele interrompe a execução do script com
         * uma mensagem de erro. Caso contrário, utiliza a biblioteca Dotenv para
         * criar uma instância imutável e carregar as variáveis de ambiente definidas
         * no arquivo .env, tornando-as acessíveis através da função `getenv()` ou
         * da superglobal `$_ENV`.
         *
         * @param string $path O caminho completo para o diretório que contém o
         * arquivo .env (e.g., __DIR__, dirname(__FILE__)). A biblioteca Dotenv
         * procurará pelo arquivo '.env' dentro deste diretório.
         *
         * @return void
         */
        public static function load(string $path): void {
            if(!file_exists(filename: $path)) {
                throw new RuntimeException(message: "Arquivo '.env' não encontrado.");
            }

            $dotenv = Dotenv::createImmutable(paths: $path);
            $dotenv->load();
        }
    }