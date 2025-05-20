<?php
    namespace AxiumPHP\Core;

    use PDO;
    use PDOStatement;
    use PDOException;
    use RuntimeException;

    class Database {
        public static array $connections = [];

        public static function connect(string $connectionName = 'default'): void {
            // Transforma o nome da conexão em maiúsculo
            $connectionName = strtoupper(string: $connectionName);

            // Faz a conexão com o banco de dados
            if(!isset(self::$connections[$connectionName])) {
                try {
                    $driver = $_ENV["{$connectionName}_DATABASE_DRIVER"];
                    $host = $_ENV["{$connectionName}_DATABASE_HOST"];
                    $dbschema = $_ENV["{$connectionName}_DATABASE_SCHEMA"];
                    $username = $_ENV["{$connectionName}_DATABASE_USERNAME"];
                    $password = $_ENV["{$connectionName}_DATABASE_PASSWORD"];
                    $port = $_ENV["{$connectionName}_DATABASE_PORT"];
                    $charset = $_ENV["{$connectionName}_DATABASE_CHARSET"];

                    $dsn = "{$driver}:host={$host};port={$port};dbname={$dbschema};charset={$charset}";
                    $options = [
                        PDO::ATTR_PERSISTENT => true,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ];

                    self::$connections[$connectionName] = new PDO(dsn: $dsn, username: $username, password: $password, options: $options);
                } catch (PDOException $e) {
                    throw new RuntimeException(message: "Erro na conexão ({$connectionName}): {$e->getMessage()}");
                }
            }
        }

        /**
         * Desconecta uma conexão de banco de dados específica.
         *
         * Este método estático remove a referência a uma conexão de banco de dados
         * existente, efetivamente desconectando-a. Se nenhum nome de conexão for
         * fornecido, a conexão padrão ('default') será desconectada.
         *
         * @param string $connectionName O nome da conexão a ser desconectada.
         * O valor padrão é 'default'.
         * @return void
         */
        public static function disconnect(string $connectionName = 'default'): void {
            self::$connections[$connectionName] = null;
        }

        /**
         * Obtém uma instância da conexão PDO para um nome de conexão específico.
         *
         * Este método estático primeiro garante que a conexão com o banco de dados
         * especificado (ou a conexão padrão, se nenhum nome for fornecido) esteja
         * estabelecida, chamando o método `connect`. Em seguida, retorna a instância
         * PDO armazenada no array de conexões estáticas.
         *
         * @param string $connectionName O nome da conexão a ser obtida. O valor
         * padrão é 'default'.
         * @return PDO A instância PDO da conexão solicitada.
         */
        public static function getConnection(string $connectionName = 'default'): PDO {
            self::connect(connectionName: $connectionName);
            return self::$connections[$connectionName];
        }

        /**
         * Prepara uma instrução SQL para execução usando uma conexão específica.
         *
         * Este método estático obtém uma instância da conexão PDO (através de
         * `getConnection`) para o nome fornecido (ou a conexão padrão) e, em seguida,
         * chama o método `prepare` dessa instância PDO para preparar a query SQL
         * fornecida. Retorna um objeto PDOStatement.
         *
         * @param string $sql A string SQL a ser preparada.
         * @param string $connectionName O nome da conexão a ser usada. O valor
         * padrão é 'default'.
         * @return PDOStatement O objeto PDOStatement resultante da preparação da query.
         */
        public static function prepare(string $sql, string $connectionName = 'default'): PDOStatement {
            return self::getConnection(connectionName: $connectionName)->prepare(query: $sql);
        }

        /**
         * Prepara e executa uma instrução SQL com parâmetros opcionais.
         *
         * Este método estático utiliza `self::prepare` para preparar a instrução SQL
         * fornecida, usando a conexão especificada (ou a padrão). Em seguida, executa
         * a instrução preparada com os parâmetros fornecidos. Retorna true em caso de
         * sucesso na execução e false em caso de falha.
         *
         * @param string $sql A string SQL a ser preparada e executada.
         * @param array $params Um array associativo de parâmetros a serem vinculados
         * à instrução SQL. O padrão é um array vazio.
         * @param string $connectionName O nome da conexão a ser usada. O valor
         * padrão é 'default'.
         * @return bool Retorna true se a execução da query foi bem-sucedida, false caso contrário.
         */
        public static function execute(string $sql, array $params = [], string $connectionName = 'default'): bool {
            $stmt = self::prepare(sql: $sql, connectionName: $connectionName);
            return $stmt->execute(params: $params);
        }

        /**
         * Prepara, executa uma instrução SQL e retorna a primeira linha do resultado
         * como um array associativo.
         *
         * Este método estático utiliza `self::prepare` para preparar a instrução SQL
         * fornecida, usando a conexão especificada (ou a padrão). Em seguida, executa
         * a instrução preparada com os parâmetros fornecidos e busca a primeira linha
         * do resultado como um array associativo. Se não houver resultados, retorna null.
         *
         * @param string $sql A string SQL a ser preparada e executada.
         * @param array $params Um array associativo de parâmetros a serem vinculados
         * à instrução SQL. O padrão é um array vazio.
         * @param string $connectionName O nome da conexão a ser usada. O valor
         * padrão é 'default'.
         * @return ?array A primeira linha do resultado como um array associativo,
         * ou null se não houver resultados.
         */
        public static function fetchOne(string $sql, array $params = [], string $connectionName = 'default'): ?array {
            $stmt = self::prepare(sql: $sql, connectionName: $connectionName);
            $stmt->execute(params: $params);
            $result = $stmt->fetch(mode: PDO::FETCH_ASSOC);
            return $result ?: null;
        }

        /**
         * Prepara, executa uma instrução SQL e retorna todas as linhas do resultado
         * como um array de arrays associativos.
         *
         * Este método estático utiliza `self::prepare` para preparar a instrução SQL
         * fornecida, usando a conexão especificada (ou a padrão). Em seguida, executa
         * a instrução preparada com os parâmetros fornecidos e busca todas as linhas
         * do resultado como um array de arrays associativos. Se não houver resultados,
         * retorna um array vazio.
         *
         * @param string $sql A string SQL a ser preparada e executada.
         * @param array $params Um array associativo de parâmetros a serem vinculados
         * à instrução SQL. O padrão é um array vazio.
         * @param string $connectionName O nome da conexão a ser usada. O valor
         * padrão é 'default'.
         * @return array Um array de arrays associativos contendo todas as linhas
         * do resultado. Retorna um array vazio se não houver resultados.
         */
        public static function fetchAll(string $sql, array $params = [], string $connectionName = 'default'): array {
            $stmt = self::prepare(sql: $sql, connectionName: $connectionName);
            $stmt->execute(params: $params);
            return $stmt->fetchAll(mode: PDO::FETCH_ASSOC);
        }

        /**
         * Retorna o ID da última linha inserida na conexão especificada.
         *
         * Este método estático obtém a instância PDO da conexão fornecida (ou a
         * padrão) e chama o método `lastInsertId()` dessa instância para recuperar
         * o ID da última linha inserida no banco de dados.
         *
         * @param string $connectionName O nome da conexão a ser usada. O valor
         * padrão é 'default'.
         * @return string O ID da última linha inserida como uma string.
         */
        public static function lastInsertId(string $connectionName = 'default'): string {
            return self::getConnection(connectionName: $connectionName)->lastInsertId();
        }

        /**
         * Inicia uma transação na conexão de banco de dados especificada.
         *
         * Este método estático obtém a instância PDO da conexão fornecida (ou a
         * padrão) e inicia uma transação, caso nenhuma já esteja ativa nessa conexão.
         * Isso garante que múltiplas operações de banco de dados possam ser agrupadas
         * e tratadas como uma única unidade atômica.
         *
         * @param string $connectionName O nome da conexão onde a transação deve ser
         * iniciada. O valor padrão é 'default'.
         * @return void
         */
        public static function beginTransaction(string $connectionName = 'default'): void {
            $conn = self::getConnection(connectionName: $connectionName);
            if (!$conn->inTransaction()) {
                $conn->beginTransaction();
            }
        }

        /**
         * Confirma (comita) a transação ativa na conexão de banco de dados especificada.
         *
         * Este método estático obtém a instância PDO da conexão fornecida (ou a
         * padrão) e, se uma transação estiver ativa nessa conexão, a confirma. Isso
         * torna permanentes todas as alterações realizadas no banco de dados dentro
         * da transação.
         *
         * @param string $connectionName O nome da conexão onde a transação deve ser
         * comitada. O valor padrão é 'default'.
         * @return void
         */
        public static function commit(string $connectionName = 'default'): void {
            $conn = self::getConnection(connectionName: $connectionName);
            if ($conn->inTransaction()) {
                $conn->commit();
            }
        }

        /**
         * Desfaz (rollback) a transação ativa na conexão de banco de dados especificada.
         *
         * Este método estático obtém a instância PDO da conexão fornecida (ou a
         * padrão) e, se uma transação estiver ativa nessa conexão, a desfaz. Isso
         * reverte todas as alterações realizadas no banco de dados dentro da transação
         * desde o seu início.
         *
         * @param string $connectionName O nome da conexão onde a transação deve ser
         * desfeita. O valor padrão é 'default'.
         * @return void
         */
        public static function rollback(string $connectionName = 'default'): void {
            $conn = self::getConnection(connectionName: $connectionName);
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
        }
    }