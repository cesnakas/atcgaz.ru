<?php

namespace Its\Service\Gbo;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\DB\Exception;

class CEntyty
{
  const HL_ENTITY_NAME = 'GboEntity';

  private $entity;

  function __construct()
  {
    $this->entity = CHelper::getHlEntity(self::HL_ENTITY_NAME);
  }

  /**
   * @throws ArgumentTypeException
   * @throws Exception
   */
  public function add($arFields)
  {
    if (!is_array($arFields))
      throw new ArgumentTypeException('arFields', 'array');

    $result = $this->entity::add($arFields);

    if ($result->isSuccess()) {
      return $result->getId();
    } else {
      throw new Exception($result->getErrorMessages());
    }
  }

  /**
   * @throws ArgumentTypeException
   * @throws Exception
   */
  public function delete($id)
  {
    if (intval($id) <= 0)
      throw new ArgumentTypeException('id', 'integer');

    $result = $this->entity->delete($id);
    if (!$result->isSuccess()) {
      throw new Exception($result->getErrorMessages());
    }
  }

  /**
   * @throws ArgumentTypeException
   * @throws Exception
   */
  public function update($id, $arFields)
  {
    if (intval($id) <= 0)
      throw new ArgumentTypeException('id', 'integer');

    $result = $this->entity::update($id, $arFields);

    if ($result->isSuccess()) {
      return $result->getId();
    } else {
      throw new Exception($result->getErrorMessages());
    }
  }

  public function getList($arSort = [], $arFilter = [], $arSelect = ['*'], $params = []): array
  {
    $arEntity = [];
    $rsEntity = $this->entity::getlist([
      'order' => $arSort,
      'select' => $arSelect,
      'filter' => $arFilter,
    ]);

    while ($arResult = $rsEntity->fetch()) {
      $arEntity[] = $arResult;
    }

    return $arEntity;
  }

  public function getByEntity($entity): array
  {
    return $this->entity::getlist([
      'filter' => ['UF_ENTITY' => $entity],
    ])->fetch();
  }
}