<?php
namespace Its\Service;

class CCompany extends CMain
{
  const HL_COMPANY_NAME = 'Company';
  const FIELD_COMPANY_TYPE_ID = 'COMPETITOR';

  public static function getCompanyListByXmlID(): array
  {
    $arCompany = [];
    $hlCompanyClassName = self::getHlEntity(self::HL_COMPANY_NAME);

    $rsCompany = $hlCompanyClassName::getlist([
      'select' => ['*'],
    ]);

    while ($arResult = $rsCompany->fetch()) {
      $arCompany[$arResult['UF_XML_ID']] = $arResult;
    }

    return $arCompany;
  }

  public static function getCountCompanyRequiringAttention(): int
  {
    global $USER;
    $arUserInfo = CMain::getUserInfo($USER->GetID());

    $regionId = $arUserInfo['REGION']['ID'];

    $fileTypeList = CMain::getEnumField(25);

    $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);
    $rsDeal = $hlDealClassName::getlist([
      'order' => ['ID' => 'desc'],
      'select' => [
        'ID',
        'UF_COMPANY_ID',
        'FILE_SIGNED' => 'FILES.UF_FILE_SIGNED',
        'FILE_ID' => 'FILES.ID',
        'FILE_STATUS' => 'FILES.UF_STATUS',
        'FILE_IS_AGREEMENT' => 'FILES.UF_IS_AGREEMENT',
        'COMPANY_ID' => 'COMPANY.ID',
        'COMPANY_REGION_ID' => 'COMPANY.UF_REGION',
        'COMPANY_TYPE' => 'COMPANY.UF_COMPANY_TYPE',
        'COMPANY_ACCREDITED' => 'COMPANY.UF_ACCREDITED',
      ],
      'filter' => [
        'UF_CATEGORY_ID' => CDeal::ACCREDITATION_DEAL_CATEGORY_ID,
        'UF_STAGE_ID' => [
          CDeal::ACCREDITATION_DEAL_STAGE_APPROVED,
          CDeal::ACCREDITATION_DEAL_STAGE_CHECK_DOCUMENTS,
          CDeal::ACCREDITATION_DEAL_STAGE_SIGNING_AGREEMENT,
          CDeal::ACCREDITATION_DEAL_STAGE_END_APPROVED,
        ],
        'FILE_STATUS' => $fileTypeList[CDeal::HL_FILE_FIELD_ENUM_TYPE_XML_ID_DEFAULT]['ID'],
        'COMPANY_REGION_ID' => $regionId,
        'FILE_IS_AGREEMENT' => false,
        'COMPANY_TYPE' => CCompany::FIELD_COMPANY_TYPE_ID,
        'COMPANY_ACCREDITED' => false,
        'UF_PACK_DOCS_CONFIRMED' => true,
        '>FILE_SIGNED' => 0,
      ],
      'runtime' => [
        'FILES' => [
          'data_type' => CMain::getHlEntity(CDeal::HL_FILE_NAME),
          'reference' => [
            '=this.ID' => 'ref.UF_DEAL_ID',
          ],
          'join_type' => 'inner',
        ],
        'COMPANY' => [
          'data_type' => CMain::getHlEntity(CCompany::HL_COMPANY_NAME),
          'reference' => [
            '=this.UF_COMPANY_ID' => 'ref.ID',
          ],
          'join_type' => 'inner',
        ],
      ],
    ]);

    $countCompany = 0;
    $arCompanyIDs = [];
    while ($arResultDeal = $rsDeal->fetch()) {
      if(in_array($arResultDeal['UF_COMPANY_ID'], $arCompanyIDs) === false) {
        $arCompanyIDs[] = $arResultDeal['UF_COMPANY_ID'];
        $countCompany++;
      }
    }

    return $countCompany;
  }

  public static function getCountAccreditatedCompanies(): int
  {
    global $USER;
    $arUserInfo = CMain::getUserInfo($USER->GetID());

    $regionId = $arUserInfo['REGION']['ID'];

    $hlCompanyClassName = CMain::getHlEntity(self::HL_COMPANY_NAME);
    return $hlCompanyClassName::getlist([
      'select' => [
        'ID',
      ],
      'filter' => [
        'UF_ACCREDITED' => true,
        'UF_REGION' => $regionId
      ]
    ])->getSelectedRowsCount();
  }
}