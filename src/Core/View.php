<?php
    namespace AxiumPHP\Core;

    class View {
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
            $viewPath = VIEW_PATH . "{$view}.php";
        
            // Verifica se o módulo foi passado e se ele possui a view
            if ($module) {
                $moduleViewPath = MODULE_PATH . "{$module}/Views/{$view}.php";
                if (file_exists(filename: $moduleViewPath)) {
                    $viewPath = $moduleViewPath;
                }
            }
        
            if (!file_exists(filename: $viewPath)) {
                http_response_code(response_code: 404);
                die("View '{$view}' não encontrada.");
            }
        
            // Extraindo variáveis para uso na view
            if (!empty($data)) {
                extract($data, EXTR_SKIP);
            }
        
            // Inclui a view e armazena o conteúdo em $content
            ob_start(); // Inicia o buffer de saída
            require_once $viewPath;
            $content = ob_get_clean(); // Obtém o conteúdo do buffer e limpa o buffer
        
            // Verifica se um layout foi passado
            if ($layout && $module) {
                $layoutPath = MODULE_PATH . "{$module}/Views/{$layout}.php";
                if (file_exists(filename: $layoutPath)) {
                    require_once $layoutPath; // Inclui o layout
                } else {
                    http_response_code(response_code: 404);
                    die("Layout '{$layout}' não encontrado.");
                }
            } elseif($layout) {
                $layoutPath = VIEW_PATH . "{$layout}.php";
                if (file_exists(filename: $layoutPath)) {
                    require_once $layoutPath; // Inclui o layout
                } else {
                    http_response_code(response_code: 404);
                    die("Layout '{$layout}' não encontrado.");
                }
            } else {
                // Se não houver layout, exibe apenas o conteúdo
                echo $content;
            }
        }        
    }