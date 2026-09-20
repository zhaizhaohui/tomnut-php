<?php

namespace Core;

use PDO;
use PDOException;

class Database
{
    protected static ?PDO $pdo = null;

    /** 当前查询的构建状态 */
    protected string $table      = '';
    protected array  $wheres     = [];   // ['sql' => 'id = ?', 'bind' => [1]]
    protected array  $bindings   = [];
    protected string $orderBy    = '';
    protected string $limit      = '';
    protected array  $columns    = ['*'];

    // ---------- 初始化 ----------

    public static function init(array $config): void
    {
        if (self::$pdo !== null) {
            return;
        }

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            self::$pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options'] ?? []
            );
        } catch (PDOException $e) {
            die('DB Connection Failed: ' . $e->getMessage());
        }
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('Database not initialized.');
        }
        return self::$pdo;
    }

    // ---------- 静态入口 ----------

    public static function table(string $table): self
    {
        $instance = new self();
        $instance->table = $table;
        return $instance;
    }

    // ---------- 原生查询 ----------

    public static function query(string $sql, array $bindings = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    public static function execute(string $sql, array $bindings = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }

    // ---------- 查询构造器 ----------

    public function select(array $columns = ['*']): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function where(string $column, string $op, $value = null): self
    {
        // 支持 where('id', 1) 两参写法，默认为 =
        if ($value === null && func_num_args() === 2) {
            $value = $op;
            $op = '=';
        }

        $this->wheres[]   = "{$column} {$op} ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = "{$column} IN ({$placeholders})";
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy = "ORDER BY {$column} " . strtoupper($direction);
        return $this;
    }

    public function limit(int $offset, ?int $count = null): self
    {
        if ($count === null) {
            $this->limit = "LIMIT {$offset}";
        } else {
            $this->limit = "LIMIT {$offset}, {$count}";
        }
        return $this;
    }

    // ---------- 构建 SQL ----------

    protected function buildWhere(): string
    {
        return $this->wheres ? 'WHERE ' . implode(' AND ', $this->wheres) : '';
    }

    protected function buildSelect(): string
    {
        $cols = implode(', ', $this->columns);
        $sql  = "SELECT {$cols} FROM {$this->table}";
        $sql .= ' ' . $this->buildWhere();
        $sql .= ' ' . $this->orderBy;
        $sql .= ' ' . $this->limit;
        return trim($sql);
    }

    // ---------- 执行查询 ----------

    public function get(): array
    {
        $stmt = self::pdo()->prepare($this->buildSelect());
        $stmt->execute($this->bindings);
        return $stmt->fetchAll();
    }

    public function first(): ?array
    {
        $this->limit = 'LIMIT 1';
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function count(): int
    {
        $cols = $this->columns;
        $this->columns = ['COUNT(*) AS cnt'];
        $row = $this->first();
        $this->columns = $cols;
        return (int) ($row['cnt'] ?? 0);
    }

    // ---------- 写入 ----------

    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $holders = implode(', ', array_fill(0, count($data), '?'));

        $sql  = "INSERT INTO {$this->table} ({$columns}) VALUES ({$holders})";
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(array_values($data));

        return (int) self::pdo()->lastInsertId();
    }

    public function update(array $data): int
    {
        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = "{$col} = ?";
        }
        $sql  = "UPDATE {$this->table} SET " . implode(', ', $sets);
        $sql .= ' ' . $this->buildWhere();

        $bindings = array_merge(array_values($data), $this->bindings);

        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $sql  = "DELETE FROM {$this->table} " . $this->buildWhere();
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->rowCount();
    }

    // ---------- 事务 ----------

    public static function begin(): void   { self::pdo()->beginTransaction(); }
    public static function commit(): void  { self::pdo()->commit(); }
    public static function rollback(): void{ self::pdo()->rollBack(); }
}