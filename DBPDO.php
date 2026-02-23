<?php

namespace A1phanumeric;

use Closure;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class DBPDO
{
    private static array $instances = [];

    public ?PDO $pdo = null;

    private ?string $error = null;
    private string $dbname;
    private string $dbhost;
    private string $dbuser;
    private string $dbpass;
    private bool $sqlserver;
    private array $options;

    /** @var callable|null */
    private $queryLogger = null;

    public static function getInstance(
        string $dbhost,
        string $dbname,
        string $dbuser,
        string $dbpass,
        bool $sqlserver = false,
        array $options = []
    ): self {
        $instanceKey = self::buildInstanceKey($dbhost, $dbname, $dbuser, $sqlserver, $options);

        if (!isset(self::$instances[$instanceKey])) {
            self::$instances[$instanceKey] = new self($dbhost, $dbname, $dbuser, $dbpass, $sqlserver, $options);
        }

        return self::$instances[$instanceKey];
    }

    public function __construct(
        string $dbhost = '',
        string $dbname = '',
        string $dbuser = '',
        string $dbpass = '',
        bool $sqlserver = false,
        array $options = []
    ) {
        $this->dbhost = $dbhost;
        $this->dbname = $dbname;
        $this->dbuser = $dbuser;
        $this->dbpass = $dbpass;
        $this->sqlserver = $sqlserver;
        $this->options = $options;

        $this->connect();
    }

    private static function buildInstanceKey(
        string $dbhost,
        string $dbname,
        string $dbuser,
        bool $sqlserver,
        array $options
    ): string {
        $stableOptions = $options;
        ksort($stableOptions);

        return hash('sha256', implode('|', [
            $dbhost,
            $dbname,
            $dbuser,
            $sqlserver ? 'sqlsrv' : 'mysql',
            json_encode($stableOptions),
        ]));
    }

    // Disallow cloning and unserializing
    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new RuntimeException('Unserializing DBPDO is not allowed.');
    }

    public function prep_query(string $query): PDOStatement
    {
        if ($this->pdo === null && !$this->connect()) {
            throw new RuntimeException('Database connection is not available.');
        }

        return $this->pdo->prepare($query);
    }

    public function connect(): bool
    {
        if ($this->pdo instanceof PDO) {
            return true;
        }

        $dsn = $this->buildDsn();
        $user = $this->dbuser;
        $password = $this->dbpass;

        try {
            $this->pdo = new PDO($dsn, $user, $password, $this->buildPdoAttributes());
            $this->error = null;

            return true;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function table_exists(string $table_name): bool
    {
        if ($this->sqlserver) {
            $stmt = $this->execute(
                'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ? AND TABLE_CATALOG = ?',
                [$table_name, $this->dbname]
            );

            if ($stmt === false) {
                return false;
            }

            return (bool) $stmt->fetchColumn();
        }

        $stmt = $this->prep_query('SHOW TABLES LIKE ?');
        $stmt->execute([$table_name]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param mixed $values
     */
    public function execute(string $query, $values = null, bool $debug = false)
    {
        $params = $this->normalizeValues($values);

        if ($debug) {
            trigger_error('DBPDO::execute($debug=true) is deprecated. Use setQueryLogger() for diagnostics.', E_USER_DEPRECATED);
            echo $query;
            print_r($params);
            return false;
        }

        $start = microtime(true);

        try {
            $stmt = $this->prep_query($query);
            $stmt->execute($params);

            $durationMs = (microtime(true) - $start) * 1000;
            if (is_callable($this->queryLogger)) {
                ($this->queryLogger)($query, $params, $durationMs, null);
            }

            return $stmt;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();

            if (is_callable($this->queryLogger)) {
                ($this->queryLogger)($query, $params, null, $this->error);
            }

            return false;
        }
    }

    /**
     * @param mixed $values
     */
    public function fetch(string $query, $values = null): ?array
    {
        $stmt = $this->execute($query, $values);

        if ($stmt === false) {
            return null;
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result === false ? null : $result;
    }

    /**
     * @param mixed $values
     */
    public function fetchAll(string $query, $values = null, ?string $key = null): array
    {
        $stmt = $this->execute($query, $values);

        if ($stmt === false) {
            return [];
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($key !== null && !empty($results)) {
            $keyed_results = [];

            foreach ($results as $result) {
                if (array_key_exists($key, $result)) {
                    $keyed_results[$result[$key]] = $result;
                }
            }

            return $keyed_results;
        }

        return $results;
    }

    public function beginTransaction(): bool
    {
        if ($this->pdo === null && !$this->connect()) {
            return false;
        }

        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        if ($this->pdo === null) {
            return false;
        }

        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        if ($this->pdo === null || !$this->pdo->inTransaction()) {
            return false;
        }

        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        if ($this->pdo === null) {
            return false;
        }

        return $this->pdo->inTransaction();
    }

    /**
     * @param Closure $callback function(DBPDO $db): mixed
     * @return mixed
     */
    public function transaction(Closure $callback)
    {
        if (!$this->beginTransaction()) {
            throw new RuntimeException('Unable to start database transaction.');
        }

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($this->inTransaction()) {
                $this->rollBack();
            }

            throw $e;
        }
    }

    public function lastInsertId(): string
    {
        if ($this->pdo === null && !$this->connect()) {
            return '0';
        }

        return $this->pdo->lastInsertId();
    }

    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }

    public function getLastError(): ?string
    {
        return $this->error;
    }

    /**
     * @param callable $logger function(string $query, array $params, ?float $durationMs, ?string $error): void
     */
    public function setQueryLogger(callable $logger): self
    {
        $this->queryLogger = $logger;

        return $this;
    }

    private function buildDsn(): string
    {
        if ($this->sqlserver) {
            $encrypt = $this->options['encrypt'] ?? false;
            $trustServerCertificate = $this->options['trust_server_certificate'] ?? false;
            $port = $this->options['port'] ?? null;

            $server = $this->dbhost;
            if ($port !== null) {
                $server .= ',' . $port;
            }

            return sprintf(
                'sqlsrv:Server=%s;Database=%s;Encrypt=%s;TrustServerCertificate=%s',
                $server,
                $this->dbname,
                $encrypt ? 'yes' : 'no',
                $trustServerCertificate ? 'yes' : 'no'
            );
        }

        $charset = $this->options['charset'] ?? 'utf8mb4';
        $port = $this->options['port'] ?? null;

        $dsn = sprintf('mysql:dbname=%s;host=%s;charset=%s', $this->dbname, $this->dbhost, $charset);
        if ($port !== null) {
            $dsn .= ';port=' . (int) $port;
        }

        return $dsn;
    }

    private function buildPdoAttributes(): array
    {
        $persistent = $this->options['persistent'] ?? false;

        $attributes = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => (bool) $persistent,
        ];

        if (!$this->sqlserver) {
            $attributes[PDO::ATTR_EMULATE_PREPARES] = false;
            if (isset($this->options['timeout'])) {
                $attributes[PDO::ATTR_TIMEOUT] = (int) $this->options['timeout'];
            }
        }

        if (isset($this->options['attributes']) && is_array($this->options['attributes'])) {
            $attributes = $this->options['attributes'] + $attributes;
        }

        return $attributes;
    }

    /**
     * @param mixed $values
     */
    private function normalizeValues($values): array
    {
        if ($values === null) {
            return [];
        }

        if (is_array($values)) {
            return $values;
        }

        return [$values];
    }
}
