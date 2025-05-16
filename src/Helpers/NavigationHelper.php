<?php
    namespace AxiumPHP\Helpers;

    class NavigationHelper {
        private const MAX_STACK = 5;

        /**
         * Rastreia a navegação do usuário, mantendo um histórico das páginas visitadas
         * na sessão.
         *
         * Este método estático obtém a URI atual da requisição. Se a URI corresponder
         * a um padrão de API ou chamada AJAX, a função retorna imediatamente sem
         * registrar a navegação.
         *
         * Se a variável de sessão 'navigation_stack' não existir, ela é inicializada
         * como um array vazio.
         *
         * Para evitar duplicatas, verifica se a URI atual é diferente da última URI
         * registrada na pilha de navegação. Se for diferente, e se a pilha atingir
         * um tamanho máximo definido por `self::MAX_STACK`, a URI mais antiga é removida
         * do início da pilha. A URI atual é então adicionada ao final da pilha.
         *
         * A URI atual também é armazenada na variável de sessão 'current_page', e a
         * página anterior (obtida através do método `self::getPreviousPage()`) é
         * armazenada em 'previous_page'.
         *
         * @return void
         *
         * @see self::getPreviousPage()
         */
        public static function trackNavigation(): void {
            $currentUri = $_SERVER['REQUEST_URI'] ?? '/';

            // Ignora chamadas para API ou AJAX
            if (preg_match(pattern: '/\/api\/|\/ajax\//i', subject: $currentUri)) {
                return;
            }

            if (!isset($_SESSION['navigation_stack'])) {
                $_SESSION['navigation_stack'] = [];
            }

            // Evita duplicar a última página
            $last = end($_SESSION['navigation_stack']);
            if ($last !== $currentUri) {
                if (count(value: $_SESSION['navigation_stack']) >= self::MAX_STACK) {
                    array_shift($_SESSION['navigation_stack']);
                }

                $_SESSION['navigation_stack'][] = $currentUri;
            }

            $_SESSION['current_page'] = $currentUri;
            $_SESSION['previous_page'] = self::getPreviousPage();
        }

        /**
         * Obtém a URI da página anterior visitada pelo usuário, com base na pilha
         * de navegação armazenada na sessão.
         *
         * Este método estático acessa a variável de sessão 'navigation_stack'. Se a
         * pilha contiver mais de uma URI, retorna a penúltima URI da pilha, que
         * representa a página visitada imediatamente antes da atual.
         *
         * Se a pilha contiver apenas uma ou nenhuma URI, significa que não há uma
         * página anterior no histórico de navegação da sessão, e o método retorna null.
         *
         * @return string|null A URI da página anterior, ou null se não houver uma.
         */
        public static function getPreviousPage(): ?string {
            $stack = $_SESSION['navigation_stack'] ?? [];

            if (count(value: $stack) > 1) {
                return $stack[count(value: $stack) - 2];
            }

            return null;
        }
    }