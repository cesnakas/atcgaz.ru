<?php

namespace Its\Service\Gbo;

use Bitrix\Main\ArgumentTypeException,
    Bitrix\Main\DB\Exception,
    Bitrix\Main\SystemException;

class CCompany
{
  const HL_COMPANY_NAME = 'GboCompany';

  private $entity;

  function __construct()
  {
    $this->entity = CHelper::getHlEntity(self::HL_COMPANY_NAME);
  }

  /**
   * @throws ArgumentTypeException
   * @throws SystemException
   */
  public function restSaveCompany($companyId)
  {
    $arCloudCompany = CHelper::getDataHttp('crm.company.get.json', ['id' => $companyId]);

    if (!empty($arCloudCompany['result'])) {
      $arCloudCompany = $arCloudCompany['result'];
      $arRegions = CRegions::getRegions();

      //игнорировать не дилерские центры атс
      //if($arCompany['COMPANY_TYPE'] != CCompany::FIELD_COMPANY_TYPE_ID) return;

      $arCompany = $this->getByXmlId($companyId);

      $arFields = [
        'UF_NAME' => $arCloudCompany['TITLE'],
        'UF_XML_ID' => $arCloudCompany['ID'],
        'UF_REGION' => $arRegions[$arCloudCompany['UF_CRM_1614179621']],
        'UF_LEGAL_ADDRESS' => $arCloudCompany['UF_CRM_1592462881003'],
        'UF_ACTUAL_ADDRESS' => $arCloudCompany['UF_CRM_1604414604661'],
        'UF_COMPANY_TYPE' => $arCloudCompany['COMPANY_TYPE'],
        'UF_ACCREDITED' => $arCloudCompany['UF_CRM_1614626235366'],
        'UF_TOTAL_SUBSIDIZED' => explode('|', $arCloudCompany['UF_CRM_1616341078539'])[0],
        'UF_AMOUNT_SUBSIDIES_PAID' => explode('|', $arCloudCompany['UF_CRM_1616173206844'])[0],
      ];

      if ($arCompany['ID'] > 0) {
        $this->update($arCompany['ID'], $arFields);
      } else {
        $arFields['UF_IS_NEW'] = true;
        $this->add($arFields);
      }
    }
  }

  /**
   * @throws ArgumentTypeException
   */
  public function getByXmlId($companyId, $arSelect = ['*'])
  {
    if (intval($companyId) <= 0)
      throw new ArgumentTypeException('companyId', 'integer');

    return $this->entity::getlist([
      'select' => $arSelect,
      'filter' => ['UF_XML_ID' => $companyId]
    ])->fetch();
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
  public function deleteByXmlId($xmlId)
  {
    if (intval($xmlId) <= 0)
      throw new ArgumentTypeException('xmlId', 'integer');

    $arCompany = $this->entity::getlist([
      'select' => ['ID'],
      'filter' => ['UF_XML_ID' => $xmlId]
    ])->fetch();

    if ($arCompany['ID'] > 0) {
      $this->delete($arCompany['ID']);
    }
  }

  /**
   * @throws ArgumentTypeException
   * @throws Exception
   */
  public function delete($companyId)
  {
    if (intval($companyId) <= 0)
      throw new ArgumentTypeException('companyId', 'integer');

    $result = $this->entity->delete($companyId);
    if (!$result->isSuccess()) {
      throw new Exception($result->getErrorMessages());
    }
  }

  /**
   * @throws ArgumentTypeException
   * @throws Exception
   */
  public function update($companyId, $arFields)
  {
    if (intval($companyId) <= 0)
      throw new ArgumentTypeException('companyId', 'integer');

    $result = $this->entity::update($companyId, $arFields);

    if ($result->isSuccess()) {
      return $result->getId();
    } else {
      throw new Exception($result->getErrorMessages());
    }
  }

  public function getList($arSort = [], $arFilter = [], $arSelect = ['*'], $params = []): array
  {
    $arCompany = [];
    $rsCompany = $this->entity::getlist([
      'order' => $arSort,
      'select' => $arSelect,
      'filter' => $arFilter,
    ]);

    while ($arResult = $rsCompany->fetch()) {
      $arCompany[] = $arResult;
    }

    return $arCompany;
  }
}