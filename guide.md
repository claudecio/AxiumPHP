# AxiumPHP - Guia de Primeiro Uso

O **AxiumPHP** é um micro-framework PHP modular no padrão MVC, ideal para criar sistemas organizados e escaláveis sem depender de frameworks pesados.  
Ele já vem pronto para trabalhar com módulos independentes, carregamento automático de rotas, tratamento global de erros e suporte a APIs (JSON) ou páginas HTML.

---

## 🚀 Instalação e Configuração

### 1. Clonar o repositório
```bash
git clone https://github.com/claudecio/AxiumPHP.git
```

### 2. Instalar as dependências via Composer
```bash
composer install
```

### 3. Configurar o ambiente
Copie o arquivo `.env.example` para `.env`:
```bash
cp .env.example .env
```
Depois, ajuste as variáveis conforme seu ambiente (conexão com banco, fuso horário, URL do frontend, etc.).

### 4. Configurar o servidor
**Usando PHP embutido:**
```bash
php -S localhost:8000 -t public
```

**Usando Apache:**  
Aponte o `DocumentRoot` para a pasta `public/`.

---

## 📂 Estrutura do Projeto
```
app/
 ├── Common/          # Serviços e controladores compartilhados
 ├── Module/          # Módulos independentes (Controllers, Models, Views, Routes, bootstrap.php)
 └── Core/            # Núcleo do framework
public/               # Arquivos públicos (index.php, assets)
vendor/               # Dependências do Composer
.env                  # Configurações do ambiente
```

---

## 📌 Entendendo o index.php
O arquivo `public/index.php` é o ponto de entrada da aplicação.  
Nele, além do autoload do Composer, algumas constantes precisam ser definidas:

- **ROUTER_MODE** → define o modo de resposta do roteador (`JSON` para APIs, `VIEW` para views).
- **APP_SYS_MODE** → define o modo de sistema, PROD para produção ou DEV para desenvolvimento.
- **ROUTER_ALLOWED_ORIGINS** → array onde é definido os dominios permitidos para usar o CORS.
- **INI_SYSTEM_PATH** → caminho absoluto para a pasta `app` do projeto.
- **MODULE_PATH** → caminho absoluto para a pasta `Module`, onde ficam os módulos do sistema.
- **STORAGE_FOLDER_PATH** → caminho absoluto para a pasta `Storage`, utilizada para arquivos e logs.

**Exemplo:**
```php
const ROUTER_MODE = 'JSON';
const APP_SYS_MODE = 'DEV' # PROD = Production // DEV = Development
// Case Router Mode in JSON
const ROUTER_ALLOWED_ORIGINS = [
	'*',
	'http://app.localhost:8000',
];
define('INI_SYSTEM_PATH', realpath(__DIR__ . "/../app"));
define('MODULE_PATH', realpath(__DIR__ . "/../app/Module"));
define('STORAGE_FOLDER_PATH', realpath(__DIR__ . "/../app/Storage"));
```

Além disso, o `index.php`:
- Carrega configurações globais (`Config`);
- Inicializa o `LoggerService`;
- Ativa o `ErrorHandler`;
- Define o fuso horário (`SYSTEM_TIMEZONE` do `.env`);
- Inicia sessão, se necessário;
- Configura CORS;
- Carrega os módulos iniciais.

---

## 🛠 Criando e Registrando um Módulo

### 1. Criar a pasta do módulo
```bash
mkdir -p app/Module/Hello
```

### 2. Criar o Controller  
Arquivo: `app/Module/Hello/Controllers/HelloController.php`
```php
<?php
namespace App\Module\Hello\Controllers;

class HelloController {
	public function index() {
		echo "Hello, AxiumPHP!";
	}
}
```

### 3. Criar a rota  
Arquivo: `app/Module/Hello/Routes/web.php`
```php
<?php
use AxiumPHP\Core\Router;
use App\Module\Hello\Controllers\HelloController;

Router::GET('/hello', [HelloController::class, 'index']);
Router::PUT('/hello/{id}', [HelloController::class, 'update']);
Router::POST('/createHello', [HelloController::class, 'create']);
Router::DELETE('/hello/{id}', [HelloController::class, 'delete']);
```

### 4. Criar o bootstrap do módulo  
Arquivo: `app/Module/Hello/bootstrap.php`
```php
<?php
require_once __DIR__ . '/Routes/web.php';
```

### 5. Registrar o módulo no index.php
```php
<?php
require_once realpath(__DIR__ . "/../app/Module/Hello/bootstrap.php");
```

---

## 💡 Dicas Importantes

**LoggerService** → configure antes do `ErrorHandler`:
```php
LoggerService::init(
    driver: LoggerService::DRIVER_FILE,
    logDir: 'logs'
);
```

**CORS** → já configurado no `index.php` usando `SYSTEM_FRONTEND_URL` do `.env`.
**ROUTER_MODE** → altere para `VIEW` se quiser trabalhar com renderização de views.
**ErrorHandler** → use o `SYSTEM_ENVIRONMENT_ID` do `.env` para decidir se exibe ou oculta erros em produção.

---

## 📜 Licença
Este projeto está licenciado sob a **MIT License**.