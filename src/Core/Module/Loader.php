<?php
    namespace AxiumPHP\Core\Module;

    use Exception;

    class Loader {
        private $configFilePath;
        private $configData;
        private $startedModules = [];
        private static array $loadedShortcuts = [];
        private static array $loadedModules = [];
        private array $requiredConstants = [
            'MODULE_PATH',
        ];

        /**
         * Construtor da classe.
         *
         * Inicializa o objeto com o caminho do arquivo de configuração e carrega as configurações.
         *
         * @param string $configFileName O caminho do arquivo de configuração (opcional).
         */
        public function __construct(?string $configFileName = "system-ini.json") {
            $this->configFilePath = INI_SYSTEM_PATH . "/{$configFileName}";
            $this->loadConfig();
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
         * Carrega as configurações do arquivo JSON.
         *
         * Este método lê o conteúdo do arquivo de configuração especificado em
         * `$this->configFilePath`, decodifica-o de JSON e armazena os dados
         * no atributo `$this->configData`.
         *
         * @throws Exception Se o arquivo de configuração não for encontrado.
         *
         * @return void
         */
        private function loadConfig():void {
            $configPath = $this->configFilePath;

            // Verifica se o arquivo de configuração existe
            if (file_exists(filename: $configPath)) {
                $jsonContent = file_get_contents(filename: $configPath);
                $this->configData = json_decode(json: $jsonContent, associative: true);
            } else {
                throw new Exception(message: "Arquivo de inicialização não encontrado: {$configPath}");
            }
        }

        /**
         * Carrega os módulos essenciais definidos na configuração.
         *
         * Este método carrega os módulos listados na seção "essentials" do arquivo
         * de configuração, utilizando o método `startModule`.
         *
         * @return void
         */
        public function loadEssentialModules():void {
            $this->startModule(modules: $this->configData["Modules"]["essentials"]);
        }

        /**
         * Carrega e inicializa todos os módulos ativos da aplicação, conforme
         * definido na configuração.
         *
         * Este método acessa a propriedade `$configData`, especificamente a seção
         * ["Modules"]["active"], que se espera ser um array contendo os
         * identificadores (nomes ou objetos) dos módulos que devem ser carregados
         * e inicializados. Em seguida, chama o método `startModule`, passando este
         * array de módulos ativos para iniciar o processo de carregamento e
         * inicialização de cada um deles.
         *
         * @return void
         *
         * @see startModule()
         * @property array $configData Propriedade da classe que contém os dados de
         * configuração da aplicação. Espera-se que possua uma chave "Modules" com
         * uma sub-chave "active" contendo um array de módulos.
         */
        public function loadActiveModules():void {
            $this->startModule(modules: $this->configData["Modules"]["active"]);
        }

         /**
         * Carrega e inicializa um único módulo da aplicação.
         *
         * Este método recebe um identificador de módulo (que pode ser uma string
         * com o nome do módulo ou um objeto representando o módulo) e o passa para
         * o método `startModule` para iniciar o processo de carregamento e
         * inicialização desse módulo específico.
         *
         * @param mixed $module O identificador do módulo a ser carregado. Pode ser
         * uma string contendo o nome do módulo ou um objeto de módulo já instanciado.
         * O tipo exato depende da implementação do sistema de módulos.
         *
         * @return void
         *
         * @see startModule()
         */
        public function loadModule(mixed $module) {
            $this->startModule(modules: [$module]);
        }

        /**
         * Retorna os atalhos carregados para um slug de módulo específico.
         *
         * Este método estático permite acessar atalhos que foram previamente carregados
         * e armazenados na propriedade estática `self::$loadedShortcuts`. Ele espera
         * o slug (identificador amigável) de um módulo como entrada e retorna o array
         * de atalhos associado a ele.
         *
         * @param string $moduleSlug O slug do módulo para o qual os atalhos devem ser retornados.
         * @return array|null Um array contendo os atalhos para o módulo especificado, ou `null`
         * se nenhum atalho tiver sido carregado para aquele slug.
         */
        public static function getShortcuts(string $moduleSlug): ?array {
            if(isset(self::$loadedShortcuts[$moduleSlug]['shortcuts'])) {
                return self::$loadedShortcuts[$moduleSlug]['shortcuts'];
            }

            return null;
        }

        /**
         * Retorna os slugs dos módulos que foram carregados.
         *
         * Este método estático fornece acesso à lista de identificadores (slugs)
         * de todos os módulos que foram previamente carregados e armazenados na
         * propriedade estática `self::$loadedModules`. É útil para verificar
         * quais módulos estão ativos ou disponíveis no contexto atual da aplicação.
         *
         * @return array|null Um array contendo os slugs dos módulos carregados, ou `null`
         * se nenhum módulo tiver sido carregado ou se a propriedade estiver vazia.
         */
        public static function getSlugLoadedModules(): ?array {
            return self::$loadedModules;
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
        private function getRealFolderName(string $basePath, string $targetName): ?string {
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
         * Inicia os módulos especificados.
         *
         * Este método itera sobre a lista de módulos fornecida, carrega seus manifestos,
         * verifica a compatibilidade da versão, carrega as dependências e inclui o
         * arquivo de bootstrap do módulo.
         *
         * @param array $modules Um array de strings representando os módulos a serem iniciados.
         * Cada string deve estar no formato "nome_do_modulo@versao".
         *
         * @throws Exception Se o manifesto do módulo não for encontrado, se houver um erro ao decodificar
         * o manifesto, se a versão do módulo for incompatível ou se o bootstrap
         * do módulo não for encontrado.
         *
         * @return void
         */
        private function startModule(array $modules): void {
            foreach ($modules as $module) {
                // Identifica o módulo requisitado e sua versão
                [$moduleName, $version] = explode(separator: '@', string: $module);
        
                // Pega o nome real da pasta do módulo
                $realModuleFolder = $this->getRealFolderName(basePath: MODULE_PATH, targetName: $moduleName);
                if (!$realModuleFolder) {
                    throw new Exception(message: "Pasta do módulo '{$moduleName}' não encontrada.");
                }
        
                // Caminho do manifesto usando o nome real
                $manifestPath = MODULE_PATH . "/{$realModuleFolder}/manifest.json";
                if (!file_exists(filename: $manifestPath)) {
                    throw new Exception(message: "Manifesto do módulo '{$moduleName}' não encontrado.");
                }
        
                $moduleManifest = json_decode(json: file_get_contents(filename: $manifestPath), associative: true);
                if (!$moduleManifest) {
                    throw new Exception(message: "Erro ao decodificar o manifesto do módulo '{$moduleName}'.");
                }
        
                // Evita carregar o mesmo módulo mais de uma vez
                if (in_array(needle: $moduleManifest["uuid"], haystack: $this->startedModules)) {
                    continue;
                }
        
                // Verifica se a versão é compatível
                if ($moduleManifest['version'] !== $version) {
                    throw new Exception(message: "Versão do módulo '{$moduleName}' é incompatível. Versão requerida: {$version}. Versão instalada: {$moduleManifest['version']}");
                }
                
                // Procura a pasta Routes com o case correto
                $realRoutesFolder = $this->getRealFolderName(basePath: MODULE_PATH . "/{$realModuleFolder}", targetName: 'Routes');
                if ($realRoutesFolder) {
                    $routesFile = MODULE_PATH . "/{$realModuleFolder}/{$realRoutesFolder}/Routes.php";
                    if (file_exists(filename: $routesFile)) {
                        require_once $routesFile;
                    }

                    // Procura arquivo com atalhos de rotas
                    $shortcutsFile = MODULE_PATH . "/{$realModuleFolder}/{$realRoutesFolder}/shortcuts.json";
                    if (file_exists(filename: $shortcutsFile)) {
                        $shortcuts = json_decode(json: file_get_contents(filename: $shortcutsFile), associative: true);
                        self::$loadedShortcuts[$moduleManifest["slug"]] = $shortcuts;
                    }
                }

                // Marca como carregado
                $this->startedModules[] = $moduleManifest["uuid"];
                self::$loadedShortcuts[$moduleManifest["slug"]];

                // Carrega dependências, se existirem
                if (!empty($moduleManifest['dependencies'])) {
                    $this->startModule(modules: $moduleManifest['dependencies']);
                }
            }
        }
    }