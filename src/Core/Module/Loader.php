<?php
    namespace AxiumPHP\Core\Module;

    use Exception;

    class Loader {
        private $configFilePath;
        private $configData;
        private $startedModules = [];

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
        private function startModule(array $modules):void {
            foreach ($modules as $module) {
                // Identifica o módulo requisitado e sua versão
                [$moduleName, $version] = explode(separator: '@', string: $module);
        
                // Carrega manifesto do módulo
                $manifestPath = MODULE_PATH . "/{$moduleName}/manifest.json";
                if (!file_exists(filename: $manifestPath)) {
                    throw new Exception(message: "Manifesto do módulo {$moduleName} não encontrado.");
                }
        
                $moduleManifest = json_decode(json: file_get_contents(filename: $manifestPath), associative: true);
                if (!$moduleManifest) {
                    throw new Exception(message: "Erro ao decodificar o manifesto do módulo {$moduleName}.");
                }
        
                // Verifica se o módulo já foi carregado
                if (in_array(needle: $moduleManifest["uuid"], haystack: $this->startedModules)) {
                    continue; // Pula o módulo se já foi carregado
                }
        
                // Verifica se a versão do módulo é compatível
                if ($moduleManifest['version'] !== $version) {
                    throw new Exception(message: "Versão do módulo {$moduleName} é incompatível. Versão requerida: {$version}. Versão instalada: {$moduleManifest['version']}");
                }
        
                // Verifica dependências do módulo
                if (isset($moduleManifest['dependencies']) && count(value: $moduleManifest['dependencies']) > 0) {
                    // Carrega as dependências recursivamente
                    $this->startModule(modules: $moduleManifest['dependencies']);
                }
        
                // Adiciona o módulo à lista de módulos carregados
                $this->startedModules[] = $moduleManifest["uuid"];
        
                // Carrega o manifesto do módulo
                $moduleSlug = strtolower(string: $moduleManifest['slug']);
                $moduleRoutesManifest = MODULE_PATH . "/{$moduleSlug}/manifest.json";

                // Carrega o arquivo das rotas de um módulo quando existe
                if ($moduleRoutesManifest && is_file(filename: $moduleRoutesManifest)) {
                    require_once $moduleRoutesManifest;
                }
            }
        }
    }