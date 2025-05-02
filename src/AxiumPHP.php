<?php
    namespace AxiumPHP;

    use Exception;
    
    class AxiumPHP {
    
        private array $requiredConstants = [
            'VIEW_PATH',
            'MODULE_PATH',
            'INI_SYSTEM_PATH',
        ];
    
        /**
         * Construtor que vai garantir que as constantes necessárias estejam definidas antes de
         * instanciar o AxiumPHP.
         */
        public function __construct() {
            // Verificar as constantes no momento da criação da instância
            $this->checkRequiredConstants();
    
            // Qualquer outra inicialização do AxiumPHP pode ser feita aqui
            echo "AxiumPHP inicializado com sucesso!";
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
    }
    