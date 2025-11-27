<?php
    namespace AxiumPHP\Core;

    use Exception;

    class View {
        private array $requiredConstants = [
            'VIEW_PATH',
        ];

        /**
         * Construtor que vai garantir que as constantes necessárias estejam definidas antes de
         * instanciar a view.
         */
        public function __construct() {
            // Verificar as constantes no momento da criação da instância
            $this->checkRequiredConstants();
        }

        /**
         * Verifica se todas as constantes necessárias estão definidas.
         *
         * @throws Exception Se alguma constante necessária não estiver definida.
         */
        private function checkRequiredConstants(): void {
            foreach ($this->requiredConstants as $constant) {
                if (!defined(constant_name: $constant)) {
                    throw new Exception(message: "Constante '{$constant}' não definida.");
                }
            }
        }

        /**
         * Busca o nome real de uma subpasta dentro de um diretório base,
         * ignorando a diferença entre maiúsculas e minúsculas.
         *
         * Este método privado recebe um `$basePath` (o diretório onde procurar)
         * e um `$targetName` (o nome da pasta desejada). Primeiro, verifica se o
         * `$basePath` é um diretório válido. Se não for, retorna null. Em seguida,
         * lê todos os arquivos e pastas dentro do `$basePath`. Para cada entrada,
         * compara o nome da entrada com o `$targetName` de forma case-insensitive.
         * Se encontrar uma entrada que corresponda ao `$targetName` (ignorando o case)
         * e que seja um diretório, retorna o nome da entrada com o seu case real.
         * Se após verificar todas as entradas nenhuma pasta correspondente for
         * encontrada, retorna null.
         *
         * @param string $basePath O caminho para o diretório base onde a subpasta será procurada.
         * @param string $targetName O nome da subpasta a ser encontrada (a comparação é case-insensitive).
         * @return string|null O nome real da pasta (com o case correto) se encontrada, ou null caso contrário.
         */
        private static function getRealFolderName(string $basePath, string $targetName): ?string {
            if (!is_dir(filename: $basePath)) return null;
        
            $entries = scandir(directory: $basePath);
            foreach ($entries as $entry) {
                if (strcasecmp(string1: $entry, string2: $targetName) === 0 && is_dir(filename: $basePath . '/' . $entry)) {
                    return $entry; // Nome com o case real
                }
            }
        
            return null;
        }  

        /**
         * Renderiza uma view dentro de um layout, com suporte a módulos.
         *
         * Este método estático inclui o arquivo de view especificado, permitindo a
         * passagem de dados para a view através do array `$data`. As variáveis
         * do array `$data` são extraídas para uso dentro da view usando `extract()`.
         * O método também permite especificar um layout e um módulo para a view.
         *
         * @param string $view   O caminho para o arquivo de view, relativo ao diretório
         *                       `views` ou `modules/{$module}/views` (ex:
         *                       'usuarios/listar', 'index').
         * @param array  $data   Um array associativo contendo os dados a serem passados
         *                       para a view. As chaves do array se tornarão variáveis
         *                       disponíveis dentro da view.
         * @param string $layout O caminho para o arquivo de layout, relativo ao
         *                       diretório `views` (ex: 'layouts/main').
         * @param string $module O nome do módulo ao qual a view pertence (opcional).
         *
         * @return void
         */
        public static function render(string $view, array $data = [], ?string $layout = null, ?string $module = null): void {
            $viewPath = VIEW_PATH . "/{$view}.php";
    
            // Se for módulo, resolve o nome da pasta do módulo e da pasta Views
            if ($module) {
                $realModule = self::getRealFolderName(basePath: MODULE_PATH, targetName: $module);
                if (!$realModule) {
                    http_response_code(response_code: 404);
                    die("Módulo '{$module}' não encontrado.");
                }
    
                $realViews = self::getRealFolderName(basePath: MODULE_PATH . "/{$realModule}", targetName: 'Views');
                if (!$realViews) {
                    http_response_code(response_code: 404);
                    die("Pasta 'Views' do módulo '{$module}' não encontrada.");
                }
    
                $moduleViewPath = MODULE_PATH . "/{$realModule}/{$realViews}/{$view}.php";
                if (file_exists(filename: $moduleViewPath)) {
                    $viewPath = $moduleViewPath;
                }
            }
    
            if (!file_exists(filename: $viewPath)) {
                http_response_code(response_code: 404);
                die("View '{$view}' não encontrada.");
            }
    
            if (!empty($data)) {
                extract($data, EXTR_SKIP);
            }
    
            ob_start();
            require_once $viewPath;
            $content = ob_get_clean();
    
            if ($layout) {
                if ($module) {
                    // Mesmo esquema pra layout dentro de módulo
                    $realModule = self::getRealFolderName(basePath: MODULE_PATH, targetName: $module);
                    $realViews = self::getRealFolderName(basePath: MODULE_PATH . "/{$realModule}", targetName: 'Views');
                    $layoutPath = MODULE_PATH . "/{$realModule}/{$realViews}/{$layout}.php";
                } else {
                    $layoutPath = VIEW_PATH . "/{$layout}.php";
                }
    
                if (file_exists(filename: $layoutPath)) {
                    require_once $layoutPath;
                } else {
                    http_response_code(response_code: 404);
                    die("Layout '{$layout}' não encontrado.");
                }
            } else {
                echo $content;
            }
        }
    }