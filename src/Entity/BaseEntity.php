<?php

namespace App\Entity;

use App\Service\BaseService;

abstract class BaseEntity
{
  const TRUE = 1;
  const FALSE = 0;

  // 通用状态

  public function mergeArray(array &$retArray, array $methodNames): array
  {
    foreach ($methodNames as $k => $v) {
      //如果$k为数字键，则直接调用方法$v
      $methodName = sprintf("get%sArray", ucfirst(is_int($k) ? $v : $k));
      // 非数字键，则调用getXXXArray方法,将$v作为参数
      if (method_exists($this, $methodName)) {
        $retArray = array_merge($retArray, is_int($k) ? $this->$methodName() : $this->$methodName($v));
      }
    }
    return $retArray;
  }

  /**
   * @param array $props 属性数组["name"=>"value",]
   * @param array $igores 忽略的属性数组
   * @return static 当前实例
   */
  public function _setProps(array $props, array $igores = []): static
  {
    if (empty($props)) return $this;
    //从$props 中移除key在igores中的元素
    $props = array_diff_key($props, array_flip($igores));
    foreach ($props as $key => $value) {
      $setter = sprintf('set%s', self::toCamelCase($key));
      if (method_exists($this, $setter)) {
        $this->$setter($value);
      }
    }
    return $this;
  }

  // snakeCase 转换为小驼峰格式, config_name => configName
  public static function toCamelCase(string $input): string
  {
    // // 如果输入已经是小驼峰或单个单词，直接返回
    if (ctype_lower($input[0]) && !preg_match('/[A-Z]/', $input)) {
      return $input;
    }

    // 分割字符串（支持下划线、短横线分隔的单词）
    $words = preg_split('/[-_]+/', $input);

    // 将每个单词首字母转换为大写（除了第一个单词）
    array_walk($words, function (&$word, $index) {
      if ($index !== 0) {
        $word = ucfirst(strtolower($word));
      }
    });

    // 合并单词，形成小驼峰格式
    return implode('', $words);
  }
  abstract public function toArray(): array;
}
