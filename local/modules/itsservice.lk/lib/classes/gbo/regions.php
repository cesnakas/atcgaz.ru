<?php

namespace Its\Service\Gbo;

use Bitrix\Main\ArgumentTypeException,
    Bitrix\Main\DB\Exception;

class CRegions
{
  const HL_REGION_NAME = 'UsersRegions';

  public static function getRegions(): array
  {
    $arRegions = [];
    $hlClassName = CHelper::getHlEntity(self::HL_REGION_NAME);

    $rsRegion = $hlClassName::getList([
      "select" => ["ID", "UF_XML_ID"],
      "order" => ["ID" => "ASC"]
    ]);
    while ($arRes = $rsRegion->fetch()) {
      $arRegions[$arRes['UF_XML_ID']] = $arRes['ID'];
    }

    return $arRegions;
  }
}