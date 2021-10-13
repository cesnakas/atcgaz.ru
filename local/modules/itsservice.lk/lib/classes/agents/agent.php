<?php
namespace Its\Service\Agents;

use Its\Service\CMain,
    Bitrix\Main\UserGroupTable,
    Its\Service\Rest\CRestUser,
    CEventLog,
    CUser,
    Its\Service\CCompany,
    Its\Service\CDeal;

class CAgent
{
  public static function getUsers(): string
  {
    $arRegions = CMain::getRegions();

    $elementId = 0;
    $finish = false;

    $existingUser = [];
    $result = UserGroupTable::getList([
      'filter' => ['GROUP_ID' => CRestUser::GROUP_USER_IDS],
      'select' => ['USER_ID', 'XML_ID' => 'USER.XML_ID'],
    ]);
    while ($arResult = $result->fetch()) {
      if($arResult['XML_ID'] > 0)
        $existingUser[$arResult['XML_ID']] = $arResult['USER_ID'];
    }

    $CUser = new CUser;

    while (!$finish) {
      $params = [
        'IBLOCK_TYPE_ID' => 'lists',
        'IBLOCK_ID' => CRestUser::LISTS_IBLOCK_USER_PROFILE,
        'ELEMENT_ORDER' => ['ID' => 'ASC'],
        'FILTER' => ['>ID' => $elementId],
        'start' => -1
      ];
      $arResultUser = CMain::getDataHttp('lists.element.get.json', $params);

      if (count($arResultUser['result']) > 0) {
        foreach ($arResultUser['result'] as $arUser) {
          $elementId = $arUser['ID'];

          $login = $arUser['PROPERTY_164'][array_key_first($arUser['PROPERTY_164'])];
          $password = $arUser['PROPERTY_162'][array_key_first($arUser['PROPERTY_162'])];
          $region = $arRegions[$arUser['PROPERTY_160'][array_key_first($arUser['PROPERTY_160'])]];
          $name = $arUser['NAME'];

          $arFields = [
            'NAME' => $name,
            'LOGIN' => $login,
            'PASSWORD' => $password,
            'CONFIRM_PASSWORD' => $password,
            'GROUP_ID' => CRestUser::GROUP_USER_IDS,
            'XML_ID' => $elementId,
            'UF_REGION' => $region
          ];

          if(isset($existingUser[$elementId])) {
            if(!$CUser->Update($existingUser[$elementId], $arFields)) {
              CEventLog::Add([
                "SEVERITY" => "ERROR",
                "AUDIT_TYPE_ID" => "UPDATE_USER",
                "MODULE_ID" => "itsservice.lk",
                'ITEM_ID' => $existingUser[$elementId],
                "DESCRIPTION" => "Не удалось обновить пользователя (".$existingUser[$elementId].") в битриксе24 (".$elementId.")".$CUser->LAST_ERROR,
              ]);
            }
          } else {
            $ID = $CUser->Add($arFields);
            if (intval($ID) <= 0) {
              CEventLog::Add([
                "SEVERITY" => "ERROR",
                "AUDIT_TYPE_ID" => "ADD_USER",
                "MODULE_ID" => "itsservice.lk",
                "DESCRIPTION" => "Не удалось создать пользователя в битриксе24 (".$elementId.") ".$CUser->LAST_ERROR,
              ]);
            }
          }

          unset($existingUser[$elementId]);
        }
      } else {
        $finish = true;
      }
    }

    foreach($existingUser as $userId) {
      if($userId > 0)
        $CUser->Delete($userId);
    }

    return "\Its\Service\Agents\CAgent::getUsers();";
  }

  public static function getRegions(): string
  {
    $hlClassName = CMain::getHlEntity(CMain::HL_REGION_NAME);

    $arRegions = [];
    $rsRegion = $hlClassName::getList([
      "select" => ["ID", "UF_XML_ID"],
      "order" => ["ID" => "ASC"]
    ]);
    while($arRes = $rsRegion->fetch()) {
      $arRegions[$arRes['UF_XML_ID']] = $arRes['ID'];
    }

    $elementId = 0;
    $finish = false;

    while (!$finish) {
      $params = [
        'IBLOCK_TYPE_ID' => 'lists',
        'IBLOCK_ID' => CRestUser::LISTS_IBLOCK_REGION,
        'ELEMENT_ORDER' => ['ID' => 'ASC'],
        'FILTER' => ['>ID' => $elementId],
        'start' => -1
      ];
      $arResultRegions = CMain::getDataHttp('lists.element.get.json', $params);

      if (count($arResultRegions['result']) > 0) {
        foreach ($arResultRegions['result'] as $arResultRegion) {
          $elementId = $arResultRegion['ID'];

          $arFields = [
            'UF_NAME' => $arResultRegion['NAME'],
            'UF_BUDGET' => $arResultRegion['PROPERTY_174'][array_key_first($arResultRegion['PROPERTY_174'])],
//        'UF_SUBSIDY_AGREEMENT' => $arRegion['PROPERTY_322'],
            'UF_REGIONAL_LIST_ID' => $arResultRegion['PROPERTY_324'][array_key_first($arResultRegion['PROPERTY_174'])],
            'UF_XML_ID' => $elementId
          ];

          if(isset($arRegions[$elementId])) {
            $hlClassName::update($arRegions[$elementId], $arFields);
          } else {
            $hlClassName::Add($arFields);
          }

          unset($arRegions[$elementId]);
        }
      } else {
        $finish = true;
      }
    }

    foreach ($arRegions as $regionId) {
      if($regionId > 0)
        $hlClassName::delete($regionId);
    }

    return "\Its\Service\Agents\CAgent::getRegions();";
  }

  public static function getCompany(): string
  {
    $hlCompanyClassName = CMain::getHlEntity(CCompany::HL_COMPANY_NAME);

    $arCompanys = [];
    $rsCompany = $hlCompanyClassName::getList([
      "select" => ["ID", "UF_XML_ID"],
      "order" => ["ID" => "ASC"]
    ]);
    while($arRes = $rsCompany->fetch()) {
      $arCompanys[$arRes['UF_XML_ID']] = $arRes['ID'];
    }

    $arRegions = CMain::getRegions();

    $elementId = 0;
    $finish = false;

    while (!$finish) {
      $params = [
        'select' => ["ID"],
        'order' => ['ID' => 'ASC'],
        'FILTER' => ['>ID' => $elementId],
        'start' => -1
      ];
      $arResultCompany = CMain::getDataHttp('crm.company.list.json', $params);

      if (count($arResultCompany['result']) > 0) {
        foreach ($arResultCompany['result'] as $arResult) {
          if($arResult['ID'] <= 0) continue;
          $elementId = $arResult['ID'];

          $paramsCompany = ['id' => $elementId];
          $arCompany = CMain::getDataHttp('crm.company.get.json', $paramsCompany)['result'];

          $arFields = [
            'UF_NAME' => $arCompany['TITLE'],
            'UF_XML_ID' => $arCompany['ID'],
            'UF_LEGAL_ADDRESS' => $arCompany['UF_CRM_1592462881003'],
            'UF_ACTUAL_ADDRESS' => $arCompany['UF_CRM_1604414604661'],
            'UF_REGION' => $arRegions[$arCompany['UF_CRM_1614179621']],
            'UF_COMPANY_TYPE' => $arCompany['COMPANY_TYPE'],
            'UF_ACCREDITED' => $arCompany['UF_CRM_1614626235366'],
            'UF_IS_NEW' => true,
            'UF_TOTAL_SUBSIDIZED' => $arCompany['UF_CRM_1616341078539'],
            'UF_AMOUNT_CONTRACT_IN_PROGRESS' => $arCompany['UF_CRM_1616173177287'],
            'UF_AMOUNT_SUBSIDIES_PAID' => $arCompany['UF_CRM_1616173206844'],
            'UF_TOTAL_TS_PLAN' => $arCompany['UF_CRM_1598341220313'],    //Общее количество ТС
          ];

          if(isset($arCompanys[$elementId])) {
            $hlCompanyClassName::update($arCompanys[$elementId], $arFields);
          } else {
            $hlCompanyClassName::Add($arFields);
          }

          unset($arCompanys[$elementId]);
        }
      } else {
        $finish = true;
      }
    }

    foreach ($arCompanys as $companyId) {
      if($companyId > 0)
        $hlCompanyClassName::delete($companyId);
    }

    return "\Its\Service\Agents\CAgent::getCompany();";
  }

  public static function getDeal(): string
  {
    $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);

    $arSystemDeal = [];
    $rsDeal = $hlDealClassName::getList([
      "select" => ["ID", "UF_XML_ID"],
      "order" => ["ID" => "ASC"]
    ]);
    while($arRes = $rsDeal->fetch()) {
      $arSystemDeal[$arRes['UF_XML_ID']] = $arRes['ID'];
    }

    $arCompany = CCompany::getCompanyListByXmlID();

    $elementId = 0;
    $finish = false;

    while (!$finish) {
      $params = [
        'order' => ['ID' => 'ASC'],
        'select' => ["ID"],
        'FILTER' => ['>ID' => $elementId],
        'start' => -1
      ];
      $arResultDeal = CMain::getDataHttp('crm.deal.list.json', $params);

      if (count($arResultDeal['result']) > 0) {
        foreach ($arResultDeal['result'] as $arResult) {
          $elementId = $arResult['ID'];

          $paramsDeal = ['id' => $elementId];
          $arDeal = CMain::getDataHttp('crm.deal.get.json', $paramsDeal)['result'];

          //поиск сделки с номером договора переооборудования
          $parentSystemDealId = '';
          if(!empty($arDeal['UF_CRM_1605507036'])) {
            $arParentSystemDeal = $hlDealClassName::getlist([
              'filter' => ['UF_XML_ID' => $arDeal['UF_CRM_1605507036']]
            ])->fetch();
            $parentSystemDealId = $arParentSystemDeal['ID'];
          }

          $arFields = [
            'UF_NAME' => $arDeal['TITLE'],
            'UF_XML_ID' => $arDeal['ID'],
            'UF_CATEGORY_ID' => $arDeal['CATEGORY_ID'],
            'UF_COMPANY_ID' => $arCompany[$arDeal['COMPANY_ID']]['ID'],
            'UF_STAGE_ID' => $arDeal['STAGE_ID'],
            'UF_DEAL_RETOOL' => $parentSystemDealId,
            'UF_BRAND_TS' => $arDeal['UF_CRM_1566246595'],
            'UF_TYPE_TS' => $arDeal['UF_CRM_1585246934'],
            'UF_BALLOON_PLACE_TS' => $arDeal['UF_CRM_1575317904'],    //СПИСОК
            'UF_VOLUME_BALLOON_TS' => $arDeal['UF_CRM_1605612191889'],    //СПИСОК
            'UF_COUNT_BALLOON' => $arDeal['UF_CRM_1585250572'],
            'UF_TOTAL_VOLUME_BALLOON' => $arDeal['UF_CRM_1585250605'],
            'UF_SYSTEM_MANUFACTURE_TS' => $arCompany[$arDeal['UF_CRM_1594011620']],   //Компания (привязка в битрикс24)
            'UF_CYLINDER_MANUFACTURE_TS' => $arCompany[$arDeal['UF_CRM_1594010739']],      //Компания (привязка в битрикс24)
            'UF_NUM_BALLON_TS' => $arDeal['UF_CRM_1585251763'],
            'UF_REALIZE_CARGO_TS' => explode('|', $arDeal['UF_CRM_1616268922161'])[0],   //Реализовано ТС (грузовые)
            'UF_REALIZE_PASSENGER_CAR_TS' => explode('|', $arDeal['UF_CRM_1616268895225'])[0],   //Реализовано ТС (легковые)
            'UF_REALIZE_BUSES_TS' => explode('|', $arDeal['UF_CRM_1616268922161'])[0],   //Реализовано ТС (автобусы)
            'UF_TOTAL_SUBSIDIZED_PLAN' => explode('|', $arDeal['UF_CRM_1613334202819'])[0],     //Общая сумма субсидирования (план)
            'UF_TOTAL_TS_RETOOL' => $arDeal['UF_CRM_1612877517'],    //Общее количество переоборудованного ТС
            'UF_IN_REQUEST_CARGO_TS' => explode('|', $arDeal['UF_CRM_1616269823539'])[0],   //В заявках ТС (грузовые)
            'UF_IN_REQUEST_PASSENGER_CAR_TS' => explode('|', $arDeal['UF_CRM_1616268996499'])[0],   //В заявках ТС (легковые)
            'UF_IN_REQUEST_BUSES_TS' => explode('|', $arDeal['UF_CRM_1616269845346'])[0],   //В заявках ТС (автобусы)
          ];

          if($arDeal['CATEGORY_ID'] == CDeal::ACCREDITATION_DEAL_CATEGORY_ID) {
            $arFields['UF_COMPANY_ID'] = $arCompany[$arDeal['COMPANY_ID']]['ID'];
          } elseif($arDeal['CATEGORY_ID'] == CDeal::INSTALLATION_CENTER_CATEGORY_ID) {
            $arFields['UF_COMPANY_ID'] = $arCompany[$arDeal['UF_CRM_5E4BA3C31BDD6']]['ID'];
          }

          if(isset($arSystemDeal[$elementId])) {
            $hlDealClassName::update($arSystemDeal[$elementId], $arFields);
            $systemDealId = $arSystemDeal[$elementId];
          } else {
            $resDeal = $hlDealClassName::Add($arFields);
            $systemDealId = $resDeal->getId();
          }

          if ($systemDealId > 0) {
            CMain::fetchDealDocs($arDeal, $systemDealId);
          }

          unset($arSystemDeal[$elementId]);
        }
      } else {
        $finish = true;
      }
    }

    foreach ($arSystemDeal as $dealId) {
      if($dealId > 0)
        $hlDealClassName::delete($dealId);
    }

    return "\Its\Service\Agents\CAgent::getDeal();";
  }
}