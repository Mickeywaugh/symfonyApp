<?php

namespace App\Service;

use App\Service\Logger;
use Doctrine\DBAL\DriverManager;

class DbalService
{
  private $conn;
  private $table;
  public $qb;
  private $whereCounter = 0;
  private $whereCond = "AND";
  private $schemaManager;
  private $debug = false;
  protected $isLogSql = true;

  // 支持的查询方法
  protected $expr = [
    '=',
    '<>',
    '>',
    '>=',
    '<',
    '<=',
    'BETWEEN',
    'NOT_BETWEEN',
    'NULL',
    'NOT_NULL',
    'LIKE',
    'NOT_LIKE',
    'IN',
    'NOT_IN',
    'LT_TIME',
    'GT_TIME',
    'LTE_TIME',
    'GTE_TIME',
    'FIND_IN',
    'OR'
  ];
  public function __construct()
  {
    $this->conn = self::getConnection();
    $this->schemaManager = $this->conn->createSchemaManager();
    $this->qb = $this->conn->createQueryBuilder();
    $this->resetQueryBuilder();
  }

  public static function getConnection()
  {
    //获取数据库配置信息
    $conf = parse_url($_ENV['DATABASE_URL']);
    $attrs = [
      'driver' => "pdo_" . $conf['scheme'],
      'host' => $conf['host'],
      'dbname' => basename($conf['path']),
      'port' => $conf['port'],
      'user' => $conf['user'],
      'password' => $conf['pass']
    ];

    return DriverManager::getConnection($attrs);
  }

  public static function table($table, ?string $alias = 't')
  {
    $table = self::toSnakeCase($table);
    $instance = new static; // 创建一个新的实例
    $instance->table = $table;
    $instance->qb->from($table, $alias); // 设置表名
    $instance->qb->select('*');
    $instance->resetQueryBuilder();
    return $instance;
  }

  public function resetQueryBuilder(): static
  {
    $this->qb->resetWhere();
    $this->qb->resetOrderBy();
    $this->qb->resetGroupBy();
    $this->qb->resetHaving();
    return $this;
  }

  public function getQueryBuilder()
  {
    return $this->qb;
  }

  public function setQeuryBuilder($qb): static
  {
    $this->qb = $qb;
    return $this;
  }

  public function select(...$args)
  {
    $this->qb->select(...$args);
    return $this;
  }

  public function orderBy(...$orderBy)
  {
    $this->qb->orderBy(...$orderBy);
    return $this;
  }

  public function groupBy(...$groupBy)
  {
    $this->qb->groupBy(...$groupBy);
    return $this;
  }

  public function join(...$args)
  {
    $this->qb->join(...$args);
    return $this;
  }

  public function leftJoin(...$args)
  {
    $this->qb->leftJoin(...$args);
    return $this;
  }

  public function rightJoin(...$args)
  {
    $this->qb->rightJoin(...$args);
    return $this;
  }

  public function innerJoin(...$args)
  {
    $this->qb->innerJoin(...$args);
    return $this;
  }

  public function setMaxResults(...$args)
  {
    $this->qb->setMaxResults(...$args);
    return $this;
  }

  public function resetWhere()
  {
    $this->qb->resetWhere();
    return $this;
  }

  public function execSql($sql, $params = [])
  {
    return $this->conn->executeStatement($sql, $params);
  }

  public function setWhere(string $where): self
  {
    if (in_array($where, ["AND", "OR"])) {
      $this->whereCond = $where;
    }
    return $this;
  }
  public function wheres(array $where): static
  {
    if (empty($where)) return $this;
    // 处理模糊搜索条件
    foreach ($where as $field => $expr) {
      $paramName = sprintf("value%d", $this->whereCounter);
      $start = sprintf("start%d",  $this->whereCounter);
      $end = sprintf("end%d",  $this->whereCounter);
      //判断$where的数组的结构，只支持key=>value的形式, 如果value为数组，则认为是非精确查询条件，默认为数组第1个元素为操作符，数组第2个元素为匹配条件
      $rfield = self::toSnakeCase($field);
      if (is_array($expr)) {
        list($op, $condtion) = $expr;
        $op = strtoupper($op);
        if (!in_array($op, $this->expr)) {
          //如果操作符不在$this->expr数组中，则默认为精确查询
          $this->setQbWhere("$rfield = :$paramName")->setParameter($paramName, $condtion);
        } else {
          switch ($op) {
            case "NULL":
              $this->setQbWhere($this->qb->expr()->isNull("$rfield"));
              break;
            case "NOT_NULL":
              $this->setQbWhere($this->qb->expr()->isNotNull("$rfield"));
              break;
            case "LIKE":
              $rfields = explode("|", $rfield); //支持多个字段值like查询 "name|title"=>['like','value']
              $conditions = [];
              foreach ($rfields as $field) {
                $field = self::toSnakeCase($field);
                $conditions[] = $this->qb->expr()->like($field, ":$paramName");
              }

              // 正确的方式：使用 expr()->or() 组合多个条件
              if (count($conditions) > 0) {
                $orExpr = $conditions[0];
                for ($i = 1; $i < count($conditions); $i++) {
                  $orExpr = $this->qb->expr()->or($orExpr, $conditions[$i]);
                }
                $this->setQbWhere($orExpr)->setParameter($paramName, "%" . $condtion . "%");
              }
              break;
            case "NOT_LIKE":
              $this->setQbWhere($this->qb->expr()->notLike("$rfield", ":$paramName"))
                ->setParameter($paramName, "%" . $condtion . "%");
              break;
            case "IN":
              $this->setQbWhere(sprintf("%s IN (%s)", $rfield, implode(",", (array)$condtion)));
              break;
            case "NOT_IN":
              $this->setQbWhere(sprintf("%s NOT IN (%s)", $rfield, implode(",", (array)$condtion)));
              break;
            case "FIND_IN":
              $this->setQbWhere("FIND_IN_SET(:$paramName, $rfield)")
                ->setParameter($paramName, $condtion);
              break;
            case "BETWEEN":
              $betweenExpr = $this->qb->expr()->and(
                $this->qb->expr()->gte("$rfield", ":$start"),
                $this->qb->expr()->lte("$rfield", ":$end")
              );
              $this->setQbWhere($betweenExpr)->setParameter($start, $condtion[0])->setParameter($end, $condtion[1]);
              break;
            case "NOT_BETWEEN":
              $notBetweenExpr = $this->qb->expr()->or(
                $this->qb->expr()->lt("$rfield", ":$start"),
                $this->qb->expr()->gt("$rfield", ":$end")
              );
              $this->setQbWhere($notBetweenExpr)->setParameter("$start", $condtion[0])->setParameter($end, $condtion[1]);
              break;
            case "OR":
              // $expr为数组,循环添加orX操作
              foreach ($condtion as $orVal) {
                $paramName = sprintf("value%d", $this->whereCounter);
                $this->qb->orWhere("$rfield = :$paramName")->setParameter($paramName, $orVal);
                $this->whereCounter++;
              }
              break;
            default:
              $this->setQbWhere("$rfield $op :$paramName")->setParameter($paramName, $condtion);
              break;
          }
        }
      } else {
        $this->setQbWhere("$rfield = :$paramName")->setParameter($paramName, $expr);
      }
      $this->whereCounter++;
    }
    return $this;
  }

  public function pagination($pageSize, $pageNum)
  {
    $offset = ($pageNum - 1) * $pageSize;
    if ($pageSize !== null) {
      $this->qb->setMaxResults($pageSize);
    }
    if ($offset !== null) {
      $this->qb->setFirstResult($offset);
    }
    return $this;
  }

  private function setQbWhere($argv)
  {
    if ($this->whereCond === "AND") {
      $this->qb->andWhere($argv);
    }
    if ($this->whereCond === "OR") {
      $this->qb->orWhere($argv);
    }
    return $this->qb;
  }

  public function getResult(): array
  {
    return $this->logSql()->qb->fetchAllAssociative();
  }

  public function getValue($field)
  {
    $result = $this->getFirst();
    return $result[$field] ?? null;
  }

  public function getCount()
  {
    return $this->logSql()->qb->select("COUNT(*) AS count")->fetchOne();
  }

  public function getFirst()
  {
    $result = $this->getResult();
    return $result ? $result[0] : null;
  }

  public function find($id, ?string $pk = "id")
  {
    return $this->qb
      ->where(sprintf('%s = :id', $pk))
      ->setParameter('id', $id)
      ->fetchAssociative() ?: null;
  }

  public function getColumn(string $field): array
  {
    $result = $this->getResult();
    return array_column($result, $field);
  }

  public function comparison($field, $op, $value): static
  {
    $this->setQbWhere("$field $op :$field")->setParameter($field, $value);
    return $this;
  }

  public function update(array $data): int
  {
    $this->qb->update($this->table);
    foreach ($data as $key => $value) {
      $key = $this->toSnakeCase($key);
      $this->qb->set($key, ":$key")->setParameter($key, $value);
    }
    return $this->qb->executeStatement();
  }

  // 插入数据
  public function insert(array $data): int
  {
    // 设置要插入的数据
    foreach ($data as $field => $value) {
      $field = self::toSnakeCase($field);
      $this->qb->setValue($field, ":$field")->setParameter($field, $value);
    }
    // 执行插入并返回受影响的行数
    return $this->qb->insert($this->table)->executeStatement();
  }

  // 删除数据
  public function delete(): int
  {
    return $this->qb->delete($this->table)->executeStatement();
  }

  public function getDbTables($prefix = ""): array
  {
    $tableObjs = $this->schemaManager->listTables();
    $tables = [];
    foreach ($tableObjs as $tableObj) {
      // 根据表名前缀搜索
      if (!empty($prefix) && strpos($tableObj->getName(), $prefix) === 0) {
        $tables[] = $tableObj->getName();
      } else {
        $tables[] = $tableObj->getName();
      }
    }
    return $tables;
  }
  private function getTableFields($table = null): array
  {
    $table = $table ?: $this->table;
    $fieldsObjs = $this->schemaManager->listTableColumns($table);
    $fields = [];
    foreach ($fieldsObjs as $field) {
      $fields[] = $field->getName();
    }
    return $fields;
  }

  // 驼峰转下划线
  private static function toSnakeCase(string $input): string
  {
    return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $input));
  }

  public function debug(): self
  {
    $this->debug = true;
    return $this;
  }

  private function logSql(): self
  {
    if ($this->debug) Logger::log($this->qb->getSQL());
    return $this;
  }

  public function __destruct()
  {
    $this->qb = null;
  }
}
