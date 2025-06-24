<?php
    namespace AxiumPHP\Core;

    use Exception;

    class Router {
        private static $routes = [];
        private static $params = [];    
        private static $currentGroupPrefix = '';
        private static $currentGroupMiddlewares = [];
        private array $requiredConstants = [
            'ROUTER_MODE'
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
                    http_response_code(response_code: 500);
                    header(header: "Content-Type: application/json, charset=utf-8");
                    echo json_encode(value: [
                        "success" => false,
                        "message" => "Constante '{$constant}' não definida.",
                    ]);
                    exit;
                }
            }
        }

        /**
         * Adiciona uma rota à lista de rotas da aplicação.
         *
         * Este método estático armazena informações sobre uma rota (método HTTP,
         * caminho, controlador, ação e middlewares) em um array interno `$routes`
         * para posterior processamento pelo roteador.
         *
         * @param string $method      O método HTTP da rota (ex: 'GET', 'POST', 'PUT', 'DELETE').
         * @param string $path        O caminho da rota (ex: '/usuarios', '/produtos/:id').
         * @param array  $handler     Um array contendo o nome do controlador e o nome da ação
         *                             que devem ser executados quando a rota for
         *                             corresponder (ex: ['UsuarioController', 'index']).
         * @param array  $middlewares Um array opcional contendo os nomes dos middlewares que
         *                             devem ser executados antes do handler da rota.
         *
         * @return void
         */
        public static function addRoute(string $method, string $path, array $handler, array $middlewares = []): void {
            self::$routes[] = [
                'method' => strtoupper(string: $method),
                'path' => '/' . trim(string: self::$currentGroupPrefix . '/' . trim(string: $path, characters: '/'), characters: '/'),
                'controller' => $handler[0],
                'action' => $handler[1],
                'middlewares' => array_merge(self::$currentGroupMiddlewares, $middlewares)
            ];
        }

        /**
         * Adiciona uma rota com método GET à lista de rotas da aplicação.
         *
         * Este método é um atalho para adicionar rotas com o método HTTP GET. Ele
         * chama o método `addRoute` internamente, passando os parâmetros
         * fornecidos e o método 'GET'.
         *
         * @param string $path        O caminho da rota (ex: '/usuarios', '/produtos').
         * @param array  $handler     Um array contendo o nome do controlador e o nome da ação
         *                             que devem ser executados quando a rota for
         *                             corresponder (ex: ['UsuarioController', 'index']).
         * @param array  $middlewares Um array opcional contendo os nomes dos middlewares que
         *                             devem ser executados antes do handler da rota.
         *
         * @return void
         */
        public static function GET(string $path, array $handler, array $middlewares = []): void {
            self::addRoute(method: "GET", path: $path, handler: $handler, middlewares: $middlewares);
        }

        /**
         * Adiciona uma rota com método POST à lista de rotas da aplicação.
         *
         * Este método é um atalho para adicionar rotas com o método HTTP POST. Ele
         * chama o método `addRoute` internamente, passando os parâmetros
         * fornecidos e o método 'POST'.
         *
         * @param string $path        O caminho da rota (ex: '/usuarios', '/produtos').
         * @param array  $handler     Um array contendo o nome do controlador e o nome da ação
         *                             que devem ser executados quando a rota for
         *                             corresponder (ex: ['UsuarioController', 'salvar']).
         * @param array  $middlewares Um array opcional contendo os nomes dos middlewares que
         *                             devem ser executados antes do handler da rota.
         *
         * @return void
         */
        public static function POST(string $path, array $handler, array $middlewares = []): void {
            self::addRoute(method: "POST", path: $path, handler: $handler, middlewares: $middlewares);
        }

        /**
         * Adiciona uma rota com método PUT à lista de rotas da aplicação.
         *
         * Este método é um atalho para adicionar rotas com o método HTTP PUT. Ele
         * chama o método `addRoute` internamente, passando os parâmetros
         * fornecidos e o método 'PUT'.
         *
         * @param string $path        O caminho da rota (ex: '/usuarios', '/produtos').
         * @param array  $handler     Um array contendo o nome do controlador e o nome da ação
         *                             que devem ser executados quando a rota for
         *                             corresponder (ex: ['UsuarioController', 'salvar']).
         * @param array  $middlewares Um array opcional contendo os nomes dos middlewares que
         *                             devem ser executados antes do handler da rota.
         *
         * @return void
         */
        public static function PUT(string $path, array $handler, array $middlewares = []): void {
            self::addRoute(method: "PUT", path: $path, handler: $handler, middlewares: $middlewares);
        }

        /**
         * Adiciona uma rota com método DELETE à lista de rotas da aplicação.
         *
         * Este método é um atalho para adicionar rotas com o método HTTP DELETE. Ele
         * chama o método `addRoute` internamente, passando os parâmetros
         * fornecidos e o método 'DELETE'.
         *
         * @param string $path        O caminho da rota (ex: '/usuarios', '/produtos').
         * @param array  $handler     Um array contendo o nome do controlador e o nome da ação
         *                             que devem ser executados quando a rota for
         *                             corresponder (ex: ['UsuarioController', 'salvar']).
         * @param array  $middlewares Um array opcional contendo os nomes dos middlewares que
         *                             devem ser executados antes do handler da rota.
         *
         * @return void
         */
        public static function DELETE(string $path, array $handler, array $middlewares = []): void {
            self::addRoute(method: "DELETE", path: $path, handler: $handler, middlewares: $middlewares);
        }

        /**
         * Verifica se um caminho de rota corresponde a um caminho de requisição.
         *
         * Este método estático compara um caminho de rota definido (ex: '/usuarios/:id')
         * com um caminho de requisição (ex: '/usuarios/123'). Ele suporta parâmetros
         * de rota definidos entre chaves (ex: ':id', ':nome'). Os parâmetros
         * correspondentes do caminho de requisição são armazenados no array estático
         * `$params` da classe.
         *
         * @param string $routePath   O caminho da rota a ser comparado.
         * @param string $requestPath O caminho da requisição a ser comparado.
         *
         * @return bool True se o caminho da requisição corresponder ao caminho da
         *              rota, false caso contrário.
         */
        private static function matchPath($routePath, $requestPath): bool {
            // Limpa os parâmetros antes de capturar novos
            self::$params = [];  // Certifica que a cada nova tentativa, a lista de parâmetros começa vazia

            $routeParts = explode(separator: '/', string: trim(string: $routePath, characters: '/'));
            $requestParts = explode(separator: '/', string: trim(string: $requestPath, characters: '/'));

            if (count(value: $routeParts) !== count(value: $requestParts)) {
                return false;
            }

            foreach ($routeParts as $i => $part) {
                if (preg_match(pattern: '/^{\w+}$/', subject: $part)) {
                    self::$params[] = $requestParts[$i];
                } elseif ($part !== $requestParts[$i]) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Agrupa rotas sob um prefixo e middlewares.
         *
         * Este método estático permite agrupar rotas que compartilham um prefixo de
         * caminho e/ou middlewares. O prefixo e os middlewares definidos dentro do
         * grupo serão aplicados a todas as rotas definidas dentro da função de
         * callback.
         *
         * @param string   $prefix      O prefixo a ser adicionado aos caminhos das rotas
         *                              dentro do grupo (ex: '/admin', '/api/v1').
         * @param callable $callback    Uma função anônima (callback) que define as rotas
         *                              que pertencem a este grupo.
         * @param array    $middlewares Um array opcional contendo os middlewares que devem
         *                              ser aplicados a todas as rotas dentro do
         *                              grupo.
         *
         * @return void
         */
        public static function group(string $prefix, callable $callback, array $middlewares = []): void {
            $previousPrefix = self::$currentGroupPrefix ?? '';
            $previousMiddlewares = self::$currentGroupMiddlewares ?? [];
    
            self::$currentGroupPrefix = $previousPrefix . $prefix;
            self::$currentGroupMiddlewares = array_merge($previousMiddlewares, $middlewares);
    
            call_user_func($callback);
    
            self::$currentGroupPrefix = $previousPrefix;
            self::$currentGroupMiddlewares = $previousMiddlewares;
        }

        /**
         * Processa dados de requisição PUT.
         *
         * Este método estático lê os dados da requisição PUT do `php://input`,
         * decodifica-os (JSON ou form-urlencoded) e retorna os dados em um array
         * associativo.
         *
         * @return array Os dados da requisição PUT processados. Retorna um array
         *               vazio se não houver dados ou se a requisição não for PUT.
         * @throws Exception Se houver um erro ao decodificar JSON.
         */
        private static function processPutDeleteRequest(string $method): array {
            $inputData = file_get_contents(filename: 'php://input');
            $data = [];
        
            if ($method === 'PUT' || $method === 'DELETE') {
                if (!empty($inputData)) {
                    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                    if (strpos(haystack: $contentType, needle: 'application/json') !== false) {
                        $data = json_decode(json: $inputData, associative: true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            http_response_code(response_code: 500);
                            header(header: "Content-Type: application/json, charset=utf-8");
                            echo json_encode(value: [
                                "success" => false,
                                "message" => "Erro ao decodificar JSON: " . json_last_error_msg()
                            ]);
                            exit;
                        }
                    } else {
                        parse_str(string: $inputData, result: $data);
                    }
                }
            }

            unset($data['_method']);
            return $data;
        }

        /**
         * Executa os middlewares.
         *
         * Este método estático itera sobre um array de middlewares e executa cada um
         * deles. Os middlewares podem ser especificados como 'Classe::metodo' ou
         * 'Classe::metodo:argumento1:argumento2' para passar argumentos para o
         * método do middleware. Se um middleware retornar `false`, a execução é
         * interrompida e a função retorna `false`.
         *
         * @param array $middlewares Um array contendo os middlewares a serem executados.
         *
         * @return bool True se todos os middlewares forem executados com sucesso,
         *              false se algum middleware falhar.
         * @throws Exception Se o formato do middleware for inválido ou se o método
         *                   do middleware não existir.
         */
        public static function runMiddlewares(array $middlewares): bool {
            foreach ($middlewares as $middleware) {
                if (strpos(haystack: $middleware, needle: '::') !== false) {
                    [$middlewareClass, $methodWithArgs] = explode(separator: '::', string: $middleware);
        
                    // Suporte a argumentos no middleware (exemplo: Middleware::Permission:ADMINISTRADOR)
                    $methodParts = explode(separator: ':', string: $methodWithArgs);
                    $method = $methodParts[0];
                    $args = array_slice(array: $methodParts, offset: 1); // Argumentos adicionais
        
                    if (method_exists(object_or_class: $middlewareClass, method: $method)) {
                        // Chama o middleware com os argumentos
                        $result = call_user_func_array(callback: [$middlewareClass, $method], args: $args);
                        if ($result === false) {
                            return false; // Middleware falhou, interrompe a execução
                        }
                    } else {
                        // Método do middleware não encontrado
                        http_response_code(response_code: 500);
                        header(header: "Content-Type: application/json, charset=utf-8");
                        echo json_encode(value: [
                            "success" => false,
                            "message" => "Método {$method} não existe na classe {$middlewareClass}"
                        ]);
                        exit;
                    }
                } else {
                    http_response_code(response_code: 500);
                    header(header: "Content-Type: application/json, charset=utf-8");
                    echo json_encode(value: [
                        "success" => false,
                        "message" =>  "Formato inválido do middleware: {$middleware}"
                    ]);
                    exit;
                }
            }
        
            return true; // Todos os middlewares passaram
        }

        /**
         * Verifica se uma rota corresponde à requisição.
         *
         * Este método verifica se o método HTTP e o caminho da requisição correspondem
         * aos da rota fornecida.
         *
         * @param string $method O método HTTP da requisição.
         * @param string $path O caminho da requisição.
         * @param array $route Um array associativo contendo os dados da rota.
         *
         * @return bool Retorna true se a rota corresponder, false caso contrário.
         */
        private static function matchRoute(string $method, string $path, array $route) {
            // Verifica se o método HTTP da rota corresponde ao da requisição
            if ($route['method'] !== $method) {
                return false;
            }
        
            // Verifica se o caminho da requisição corresponde ao caminho da rota
            return self::matchPath(routePath: $route['path'], requestPath: $path);
        }

        /**
         * Prepara os parâmetros para um método de requisição.
         *
         * Este método combina os parâmetros da rota, os parâmetros GET e, para
         * requisições PUT ou DELETE, os parâmetros fornecidos, retornando um
         * array de parâmetros preparados.
         *
         * @param string $method O método HTTP da requisição (GET, POST, PUT, DELETE).
         * @param array|null $params Um array opcional de parâmetros adicionais.
         *
         * @return array Um array contendo os parâmetros preparados.
         */
        private static function prepareMethodParameters(string $method, ?array $params = []) {
            // Definição de váriavel;
            $atuallParams = self::$params;

            // Adiciona os dados de PUT/DELETE como um array no final
            if ($method === 'PUT' || $method === 'DELETE') {
                // Adiciona as informações da requisição no final dos parâmetros
                self::$params = array_merge($atuallParams, $params);
            }

            // Parâmetros da rota e GET
            $preparedParams = array_values(self::$params);
        
            return $preparedParams;
        }

        /**
         * Despacha a requisição para o controlador e ação correspondentes.
         *
         * Este método estático analisa a requisição (método HTTP e caminho), encontra
         * a rota correspondente na lista de rotas definidas, executa os middlewares
         * da rota (se houver), instancia o controlador e chama a ação (método)
         * especificada na rota, passando os parâmetros da requisição (parâmetros da
         * rota, parâmetros GET e dados de PUT/DELETE). Se nenhuma rota
         * corresponder, um erro 404 é enviado.
         *
         * @return void
         */
        public static function dispatch():  void {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url(url: $_SERVER['REQUEST_URI'], component: PHP_URL_PATH);
            $path = trim(string: rtrim(string: $path, characters: '/'), characters: '/');
        
            // Verifica se o método HTTP é POST e se existe o campo '_method' no corpo da requisição
            if ($method === 'POST' && isset($_POST['_method'])) {
                $method = strtoupper(string: $_POST['_method']);
            }
        
            // Processa os dados de PUT e DELETE
            $requestData = match($method) {
                'PUT' => self::processPutDeleteRequest(method: $method),
                'DELETE' => self::processPutDeleteRequest(method: $method),
                default => null
            };
        
            // Loop unificado para processar as rotas
            foreach (self::$routes as $route) {
                // Verifica se a rota corresponde
                if (self::matchRoute(method: $method, path: $path, route: $route)) {
                    // Executa os middlewares se houver
                    if (!empty($route['middlewares']) && !self::runMiddlewares(middlewares: $route['middlewares'])) {
                        return; // Middleware falhou, interrompe a execução
                    }
        
                    $controller = new $route['controller']();
                    $action = $route['action'];
                    $params = self::prepareMethodParameters(method: $method, params: [$requestData]);

                    switch (ROUTER_MODE) {
                        case 'view':
                        case 'VIEW':
                            if (method_exists(object_or_class: $controller, method: $action)) {
                                http_response_code(response_code: 200);
                                call_user_func_array(callback: [$controller, $action], args: $params);
                                exit;
                            }
                        break;

                        case 'json':
                        case 'JSON':
                            if (method_exists(object_or_class: $controller, method: $action)) {
                                http_response_code(response_code: 200);
                                header(header: 'Content-Type: application/json, charset=utf-8');
                                call_user_func_array(callback: [$controller, $action], args: $params);
                                exit;
                            }
                        break;
                    }
                }
            }
        
            // Se nenhuma rota compatível for encontrada, envia erro 404
            self::pageNotFound();
            exit;
        }

        /**
         * Exibe a página de erro 404 (Página não encontrada).
         *
         * Este método estático define o código de resposta HTTP como 404 e renderiza
         * a view "/Errors/404" para exibir a página de erro. Após a renderização,
         * o script é encerrado.
         *
         * @return void
         */
        private static function pageNotFound(): void {
            switch (ROUTER_MODE) {
                case 'view':
                case 'VIEW':
                    // Notifica erro em caso constante não definida
                    if(!defined(constant_name: 'ERROR_404_VIEW_PATH')) {
                        http_response_code(response_code: 500);
                        header(header: 'Content-Type: application/json, charset=utf-8');
                        echo json_encode( value: [
                            "success" => false,
                            "message" => "Constante 'ERROR_404_VIEW_PATH' não foi definida.",
                        ]);
                        exit;
                    }

                    // Caso o arquivo da constante não exista, notifica erro
                    if(!file_exists(filename: ERROR_404_VIEW_PATH)) {
                        http_response_code(response_code: 500);
                        header(header: 'Content-Type: application/json, charset=utf-8');
                        echo json_encode( value: [
                            "success" => false,
                            "message" => "Arquivo da constante 'ERROR_404_VIEW_PATH' não foi encontrado.",
                        ]);
                        exit;
                    }

                    http_response_code(response_code: 404);
                    require_once ERROR_404_VIEW_PATH;
                break;

                case 'json':
                case 'JSON':
                    http_response_code(response_code: 404);
                    header(header: 'Content-Type: application/json, charset=utf-8');
                    echo json_encode( value: [
                        "success" => false,
                        "message" => "Página não encontrada.",
                    ]);
                break;
            }
        }
    }