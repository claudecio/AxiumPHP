<?php
    namespace AxiumPHP\Helpers;

    class SearchHelper {
        /**
         * Calcula o valor de OFFSET para consultas de paginação SQL.
         *
         * Este método estático recebe o número da página desejada (`$page`) e o
         * número de itens por página (`$limit`). Ele calcula o valor de OFFSET
         * que deve ser usado em uma consulta SQL para buscar os registros corretos
         * para a página especificada. O OFFSET é calculado como `($page - 1) * $limit`.
         * A função `max(0, ...)` garante que o OFFSET nunca seja negativo, o que
         * pode acontecer se um valor de página menor que 1 for passado.
         *
         * @param int $page O número da página para a qual se deseja calcular o OFFSET (a primeira página é 1).
         * @param int $limit O número de itens a serem exibidos por página.
         * @return int O valor de OFFSET a ser utilizado na cláusula LIMIT de uma consulta SQL.
         */
        public static function getOffset(int $page, int $limit): int {
            return max(0, ($page - 1) * $limit);
        }

        /**
         * Gera HTML para um componente de paginação.
         *
         * Este método estático recebe a página atual, o total de registros e um limite
         * de registros por página para gerar uma navegação de paginação em HTML,
         * utilizando classes do Bootstrap para estilização.
         *
         * Primeiro, calcula o número total de páginas. Em seguida, constrói a query
         * string da URL atual, removendo os parâmetros 'page' e 'url' para evitar
         * duplicações nos links de paginação.
         *
         * Limita o número máximo de botões de página exibidos e calcula o início e
         * o fim da janela de botões a serem mostrados, ajustando essa janela para
         * garantir que o número máximo de botões seja exibido, dentro dos limites
         * do total de páginas.
         *
         * Se o total de registros for maior que o limite por página, o HTML da
         * paginação é gerado, incluindo botões "Anterior" (desabilitado na primeira
         * página), os números das páginas dentro da janela calculada (com a página
         * atual marcada como ativa) e um botão "Próximo" (desabilitado na última
         * página). Se o total de registros não for maior que o limite, uma string
         * vazia é retornada.
         *
         * @param int $current_page O número da página atualmente visualizada.
         * @param int $total_rows O número total de registros disponíveis.
         * @param int $limit O número máximo de registros a serem exibidos por página (padrão: 20).
         *
         * @return string O HTML da navegação de paginação, estilizado com classes do Bootstrap,
         * ou uma string vazia se não houver necessidade de paginação.
         */
        public static function generatePaginationHtml(int $current_page, int $total_rows, int $limit = 20): string {
            $current_page ??= 1;
            $total_rows ??= 0;

            // Calcula o total de páginas
            $total_paginas = ceil(num: $total_rows / $limit);

            // Construir a query string com os parâmetros atuais, exceto 'page'
            $query_params = $_GET;
            unset($query_params['page']); // Remove 'page' para evitar duplicação
            unset($query_params['url']); // Remove 'url' para evitar duplicação
            $query_string = http_build_query(data: $query_params);

            // Limitar a quantidade máxima de botões a serem exibidos
            $max_botoes = 10;
            $inicio = max(1, $current_page - intval(value: $max_botoes / 2));
            $fim = min($total_paginas, $inicio + $max_botoes - 1);

            // Ajustar a janela de exibição se atingir o limite inferior ou superior
            if ($fim - $inicio + 1 < $max_botoes) {
                $inicio = max(1, $fim - $max_botoes + 1);
            }

            // Validação das paginações
            if($total_rows > $limit){
                // Inicia a criação do HTML da paginação
                $html = "<nav aria-label='Page navigation'>";
                $html .= "<ul class='pagination justify-content-center'>";
        
                // Botão Anterior (desabilitado na primeira página)
                if ($current_page > 1) {
                    $anterior = $current_page - 1;
                    $html .= "<li class='page-item'>";
                    $html .= "<a class='page-link' href='?{$query_string}&page={$anterior}'>Anterior</a>";
                    $html .= "</li>";
                } else {
                    $html .= "<li class='page-item disabled'>";
                    $html .= "<a class='page-link'>Anterior</a>";
                    $html .= "</li>";
                }
        
                // Geração dos links de cada página dentro da janela definida
                for ($i = $inicio; $i <= $fim; $i++) {
                    if ($i == $current_page) {
                        $html .= "<li class='page-item active'>";
                        $html .= "<a class='page-link' href='?{$query_string}&page={$i}'>{$i}</a>";
                        $html .= "</li>";
                    } else {
                        $html .= '<li class="page-item">';
                        $html .= "<a class='page-link' href='?{$query_string}&page={$i}'>{$i}</a>";
                        $html .= "</li>";
                    }
                }
        
                // Botão Próximo (desabilitado na última página)
                if ($current_page < $total_paginas) {
                    $proxima = $current_page + 1;
                    $html .= "<li class='page-item'>";
                    $html .= "<a class='page-link' href='?{$query_string}&page={$proxima}'>Próximo</a>";
                    $html .= "</li>";
                } else {
                    $html .= "<li class='page-item disabled'>";
                    $html .= "<a class='page-link'>Próximo</a>";
                    $html .= "</li>";
                }
        
                $html .= "</ul>";
                $html .= "</nav>";
            } else {
                $html = "";
            }
        
            return $html;
        }
    }