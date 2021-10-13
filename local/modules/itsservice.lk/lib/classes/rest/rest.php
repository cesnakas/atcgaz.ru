<?php
namespace Its\Service\Rest;

use Bitrix\Main\Application,
    Its\Service\CMain,
    Bitrix\Main\UserTable,
    Its\Service\CDeal,
    Its\Service\CCompany,
    CUser,
    CEventLog,
    Its\Service\Gbo,
    Bitrix\Main\Data\Cache;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;

class CRest extends CMain
{
  const WEBHOOK_DOMAIN = 'its-online.bitrix24.ru';
  const OUT_HOOK_TOKEN_APPLICATION = 'l2redtwfb9fs3kkgzorgrwiwfwtbc1nr';

  private $data;
  private $auth;
  private $query;

  /**
   * Проверка вебхука и инициализация данных
   * @return bool
   */
  public function checkRequest(): bool
  {
    $request = Application::getInstance()->getContext()->getRequest();

    $this->setAuth($request->getPost("auth"));

    if ($request->isPost() && $this->auth['domain'] == self::WEBHOOK_DOMAIN) {
      $this->setData($request->getPost("document_id"));
      $this->setQuery($request->getQueryList()->getValues());

      //Исходящий вебхоок
      if ($this->auth['application_token'] == self::OUT_HOOK_TOKEN_APPLICATION) {
        $query = $request->getValues();
        $this->setQuery($query);
        $this->setData([$query['event']]);
      }
      return true;
    }

    return false;
  }

  /**
   * Сохранение полученных данных
   */
  public function saveData()
  {
    $data = $this->getData();
    $query = $this->getQuery();

    switch ($data[0]) {
      case 'lists':
        $elementId = intval($data[2]);
        $iblockId = intval($query['IBLOCK_ID']);
        if ($iblockId > 0 && $iblockId == CRestUser::LISTS_IBLOCK_USER_PROFILE && $elementId > 0) {
          $this->updateUser($elementId);
        } elseif ($iblockId > 0 && $iblockId == CRestUser::LISTS_IBLOCK_REGION && $elementId > 0) {
          $this->updateRegion($elementId);
        }
        break;
      case 'ONCRMDEALUPDATE':
        $dealId = $query['data']['FIELDS']['ID'];
        $this->saveDeal($dealId);
//        $this->prepareDeal($dealId);
        break;
      case 'ONCRMDEALDELETE':
        $dealId = $query['data']['FIELDS']['ID'];
        $this->deleteDeal($dealId);
//        $this->prepareDeleteDeal($dealId);
        break;
      case 'ONCRMCOMPANYADD':
      case 'ONCRMCOMPANYUPDATE':
        $companyId = $query['data']['FIELDS']['ID'];
        $this->saveCompany($companyId);
//        $CCompany = new Gbo\CCompany();
//        $CCompany->restSaveCompany($companyId);
      break;
      case 'ONCRMCOMPANYDELETE':
        $companyId = $query['data']['FIELDS']['ID'];
        $this->deleteCompany($companyId);
//        $CCompany = new Gbo\CCompany();
//        $CCompany->getByXmlId($companyId);
        break;
    }
  }

  private function prepareDeal($dealId)
  {
    $arResultDeal = CMain::getDataHttp('crm.deal.get.json', ['id' => $dealId]);
    if (!empty($arResultDeal['result'])) {
      $dealData = $arResultDeal['result'];
      if($dealData['CATEGORY_ID'] == CDeal::ACCREDITATION_DEAL_CATEGORY_ID) {
        $CAccreditation = new Gbo\CAccreditation();
        $CAccreditation->restSaveDeal($dealData);
      }
    }
  }

  private function prepareDeleteDeal($dealId)
  {
    $arResultDeal = CMain::getDataHttp('crm.deal.get.json', ['id' => $dealId]);
    if (!empty($arResultDeal['result'])) {
      $dealData = $arResultDeal['result'];
      if($dealData['CATEGORY_ID'] == CDeal::ACCREDITATION_DEAL_CATEGORY_ID) {
        $CAccreditation = new Gbo\CAccreditation();
        $CAccreditation->delete($dealData);
      }
    }
  }

  private function saveCompany($companyId)
  {
    $hlCompanyClassName = CMain::getHlEntity(CCompany::HL_COMPANY_NAME);

    $arResultCompany = CMain::getDataHttp('crm.company.get.json', ['id' => $companyId]);

    if (!empty($arResultCompany['result'])) {
      $arRegions = CMain::getRegions();
      $arCompany = $arResultCompany['result'];

      //игнорировать не дилерские центры атс
      //if($arCompany['COMPANY_TYPE'] != CCompany::FIELD_COMPANY_TYPE_ID) return;

      $arSystemCompany = $hlCompanyClassName::getlist([
        'select' => ['ID'],
        'filter' => ['UF_XML_ID' => $arCompany['ID']]
      ])->fetch();

      $arFields = [
        'UF_NAME' => $arCompany['TITLE'],
        'UF_XML_ID' => $arCompany['ID'],
        'UF_LEGAL_ADDRESS' => $arCompany['UF_CRM_1592462881003'],
        'UF_ACTUAL_ADDRESS' => $arCompany['UF_CRM_1604414604661'],
        'UF_REGION' => $arRegions[$arCompany['UF_CRM_1614179621']],
        'UF_COMPANY_TYPE' => $arCompany['COMPANY_TYPE'],
        'UF_ACCREDITED' => $arCompany['UF_CRM_1614626235366'],
        'UF_TOTAL_SUBSIDIZED' => explode('|', $arCompany['UF_CRM_1616341078539'])[0],
        'UF_AMOUNT_CONTRACT_IN_PROGRESS' => explode('|', $arCompany['UF_CRM_1616173177287'])[0],
        'UF_AMOUNT_SUBSIDIES_PAID' => explode('|', $arCompany['UF_CRM_1616173206844'])[0],
        'UF_TOTAL_TS_PLAN' => $arCompany['UF_CRM_1598341220313'],    //Общее количество ТС
        'UF_FULL_NAME' => $arCompany['UF_CRM_1585245202'],    //Полное наименование организации
      ];

      if ($arSystemCompany['ID'] > 0) {
        $hlCompanyClassName::update($arSystemCompany['ID'], $arFields);
      } else {
        $arFields['UF_IS_NEW'] = true;
        $hlCompanyClassName::add($arFields);
      }
    }
  }

  private function deleteCompany($companyId)
  {
    $hlCompanyClassName = CMain::getHlEntity(CCompany::HL_COMPANY_NAME);
    $arSystemCompany = $hlCompanyClassName::getlist([
      'select' => ['ID'],
      'filter' => ['UF_XML_ID' => $companyId]
    ])->fetch();

    if ($arSystemCompany['ID'] > 0) {
      $hlCompanyClassName::delete($arSystemCompany['ID']);
    }
  }

  /**
   * Удаление сделки в системе
   * @param $dealId - ид сделки в битрикс24
   */
  private function deleteDeal($dealId)
  {
    $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);
    $arSystemDeal = $hlDealClassName::getlist([
      'select' => ['ID'],
      'filter' => ['UF_XML_ID' => $dealId]
    ])->fetch();
    if ($arSystemDeal['ID'] > 0) {
      $this->deleteFile($arSystemDeal['ID']);
      $hlDealClassName::delete($arSystemDeal['ID']);
    }
  }

  private function deleteFile($dealId)
  {
    $hlFileClassName = CMain::getHlEntity(CDeal::HL_FILE_NAME);

    $rsSystemFiles = $hlFileClassName::getlist([
      'select' => ['ID'],
      'filter' => [
        'UF_DEAL_ID' => $dealId,
      ]
    ]);
    while($arSystemFiles = $rsSystemFiles->fetch()) {
      $hlFileClassName::delete($arSystemFiles['ID']);
    }
  }

  /**
   * Обработка обновления сделки
   * @param $dealId - ид сделки в битрикс24
   */
  private function saveDeal($dealId)
  {
    $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);

    $arResultDeal = CMain::getDataHttp('crm.deal.get.json', ['id' => $dealId]);

    if (!empty($arResultDeal['result'])) {
      $dealData = $arResultDeal['result'];

      $arCompany = CCompany::getCompanyListByXmlID();

      $arSystemDeal = $hlDealClassName::getlist([
        'filter' => ['UF_XML_ID' => $dealId]
      ])->fetch();
      $systemDealId = $arSystemDeal['ID'];

      //поиск сделки с номером договора переооборудования
      $parentSystemDealId = '';
      if(!empty($dealData['UF_CRM_1605507036'])) {
        $arParentSystemDeal = $hlDealClassName::getlist([
          'filter' => ['UF_XML_ID' => $dealData['UF_CRM_1605507036']]
        ])->fetch();
        $parentSystemDealId = $arParentSystemDeal['ID'];
      }

      $arBallonPlaceList = CMain::getEnumField(70);
      $arVolumeBallonList = CMain::getEnumField(71);

      $arFieldsDeal = [
        'UF_NAME' => $dealData['TITLE'],
        'UF_XML_ID' => $dealId,
        'UF_CATEGORY_ID' => $dealData['CATEGORY_ID'],
        'UF_COMPANY_ID' => $arCompany[$dealData['COMPANY_ID']]['ID'],
        'UF_STAGE_ID' => $dealData['STAGE_ID'],
        'UF_DEAL_RETOOL' => $parentSystemDealId,
        'UF_BRAND_TS' => $dealData['UF_CRM_1566246595'],
        'UF_TYPE_TS' => $dealData['UF_CRM_1585246934'],
        'UF_BALLOON_PLACE_TS' => $arBallonPlaceList[$dealData['UF_CRM_1575317904']]['ID'],
        'UF_VOLUME_BALLOON_TS' => $arVolumeBallonList[$dealData['UF_CRM_1605612191889']]['ID'],
        'UF_COUNT_BALLOON' => $dealData['UF_CRM_1585250572'],
        'UF_TOTAL_VOLUME_BALLOON' => $dealData['UF_CRM_1585250605'],
        'UF_SYSTEM_MANUFACTURE_TS' => $arCompany[$dealData['UF_CRM_1594011620']]['ID'],   //Компания (привязка в битрикс24)
        'UF_CYLINDER_MANUFACTURE_TS' => $arCompany[$dealData['UF_CRM_1594010739']]['ID'],      //Компания (привязка в битрикс24)
        'UF_NUM_BALLON_TS' => $dealData['UF_CRM_1585251763'][0],
        'UF_REALIZE_CARGO_TS' => explode('|', $dealData['UF_CRM_1616268922161'])[0],   //Реализовано ТС (грузовые)
        'UF_REALIZE_PASSENGER_CAR_TS' => explode('|', $dealData['UF_CRM_1616268895225'])[0],   //Реализовано ТС (легковые)
        'UF_REALIZE_BUSES_TS' => explode('|', $dealData['UF_CRM_1616268922161'])[0],   //Реализовано ТС (автобусы)
        'UF_TOTAL_SUBSIDIZED_PLAN' => explode('|', $dealData['UF_CRM_1613334202819'])[0],     //Общая сумма субсидирования (план)
        'UF_TOTAL_TS_RETOOL' => $dealData['UF_CRM_1612877517'],    //Общее количество переоборудованного ТС
        'UF_IN_REQUEST_CARGO_TS' => explode('|', $dealData['UF_CRM_1616269823539'])[0],   //В заявках ТС (грузовые)
        'UF_IN_REQUEST_PASSENGER_CAR_TS' => explode('|', $dealData['UF_CRM_1616268996499'])[0],   //В заявках ТС (легковые)
        'UF_IN_REQUEST_BUSES_TS' => explode('|', $dealData['UF_CRM_1616269845346'])[0],   //В заявках ТС (автобусы)
        'UF_TOTAL_TS_PLAN' => $dealData['UF_CRM_1598341220313'],   //Общее количество ТС (план)
        'UF_PACK_DOCS_CONFIRMED' => $dealData['UF_CRM_1621841213766'],   //акет документов подтвержден?
      ];

      if($dealData['CATEGORY_ID'] == CDeal::ACCREDITATION_DEAL_CATEGORY_ID) {
        $arFieldsDeal['UF_COMPANY_ID'] = $arCompany[$dealData['COMPANY_ID']]['ID'];
      } elseif($dealData['CATEGORY_ID'] == CDeal::INSTALLATION_CENTER_CATEGORY_ID) {
        $arFieldsDeal['UF_COMPANY_ID'] = $arCompany[$dealData['UF_CRM_5E4BA3C31BDD6']]['ID'];
      }

      $isDealProcessing = false;
      $cache = Cache::createInstance();
      if ($cache->initCache(600, "processing_deal_id")) {
        $arDealId = $cache->getVars();
        if(in_array($dealId, $arDealId) === false) {
          $arDealId[] = $dealId;

          $cache->clearCache(true);
          if ($cache->startDataCache()) {
            $cache->endDataCache($arDealId);
          }
        } else {
          $isDealProcessing = true;
        }
      } elseif ($cache->startDataCache()) {
        $cache->endDataCache([$dealId]);
      }

      if (empty($arSystemDeal) && !$isDealProcessing) {
        $resDeal = $hlDealClassName::add($arFieldsDeal);
        $systemDealId = $resDeal->getId();
      } else {
        if(!$arSystemDeal['UF_PACK_DOCS_CONFIRMED']) {
          $dealData['FIRST_SIGNED'] = true;
        }
        $hlDealClassName::update($arSystemDeal['ID'], $arFieldsDeal);
      }

      if ($systemDealId > 0 ) {
        CMain::fetchDealDocs($dealData, $systemDealId);
      }
    }
  }

  /**
   * Вебхук обновление списка регионов
   * @param $elementId - ид элемента списка в битрикс24
   */
  private function updateRegion($elementId)
  {
    $arParams = [
      'IBLOCK_TYPE_ID' => 'lists',
      'IBLOCK_ID' => CRestUser::LISTS_IBLOCK_REGION,
      'ELEMENT_ID' => $elementId
    ];
    $arResultRegion = CMain::getDataHttp('lists.element.get.json', $arParams);

    if ($arResultRegion['total'] == 1) {
      $hlClassName = CMain::getHlEntity(CMain::HL_REGION_NAME);

      $rsRegion = $hlClassName::getList([
        "select" => ["ID", "UF_XML_ID"],
        "order" => ["ID" => "ASC"]
      ]);
      while ($arRes = $rsRegion->fetch()) {
        $arRegions[$arRes['UF_XML_ID']] = $arRes['ID'];
      }

      $arRegion = $arResultRegion['result'][0];

      $arFields = [
        'UF_NAME' => $arRegion['NAME'],
        'UF_BUDGET' => $arRegion['PROPERTY_174'][array_key_first($arRegion['PROPERTY_174'])],
//        'UF_SUBSIDY_AGREEMENT' => $arRegion['PROPERTY_322'],
        'UF_REGIONAL_LIST_ID' => $arRegion['PROPERTY_324'][array_key_first($arRegion['PROPERTY_174'])],
        'UF_XML_ID' => $elementId,
        'UF_FEDERAL' => $arRegion['PROPERTY_410'][array_key_first($arRegion['PROPERTY_410'])],
        'UF_PRE_APPROVED' => (empty($arRegion['PROPERTY_460'])) ? false : true,
      ];


      if (isset($arRegions[$elementId])) {
        $hlClassName::update($arRegions[$elementId], $arFields);
      } else {
        $hlClassName::Add($arFields);
      }
    }
  }

  /**
   * Обновление пользователей
   * @param $elementId - ид элемента списка в битрикс24
   */
  private function updateUser($elementId)
  {
    $arParams = [
      'IBLOCK_TYPE_ID' => 'lists',
      'IBLOCK_ID' => CRestUser::LISTS_IBLOCK_USER_PROFILE,
      'ELEMENT_ID' => $elementId
    ];
    $arResultUser = CMain::getDataHttp('lists.element.get.json', $arParams);

    if ($arResultUser['total'] == 1) {
      $resultUser = $arResultUser['result'][0];
      $arRegions = CMain::getRegions();
      $loginFirstKey = array_key_first($resultUser[CRestUser::FIELDS_BITRIX_FOR_USER['LOGIN']]);
      $passwordFirstKey = array_key_first($resultUser[CRestUser::FIELDS_BITRIX_FOR_USER['PASSWORD']]);
      $regionFirstKey = array_key_first($resultUser[CRestUser::FIELDS_BITRIX_FOR_USER['REGION']]);

      $login = $resultUser[CRestUser::FIELDS_BITRIX_FOR_USER['LOGIN']][$loginFirstKey];
      $password = $resultUser[CRestUser::FIELDS_BITRIX_FOR_USER['PASSWORD']][$passwordFirstKey];
      $region = $arRegions[$resultUser[CRestUser::FIELDS_BITRIX_FOR_USER['REGION']][$regionFirstKey]];
      $name = $resultUser['NAME'];

      $dbUser = UserTable::getList([
        'select' => ['ID'],
        'filter' => ['XML_ID' => $elementId]
      ]);
      $CUser = new CUser;
      $arFields = [
        'NAME' => $name,
        'LOGIN' => $login,
        'PASSWORD' => $password,
        'CONFIRM_PASSWORD' => $password,
        'GROUP_ID' => (empty($resultUser['PROPERTY_442'])) ? CRestUser::GROUP_USER_IDS : CRestUser::GROUP_USER_PRE_APPROVAL,
        'XML_ID' => $elementId,
        'UF_REGION' => $region
      ];
      if ($dbUser->getSelectedRowsCount() > 0) {
        while ($arUser = $dbUser->fetch()) {
          if (!$CUser->Update($arUser['ID'], $arFields)) {
            CEventLog::Add([
              "SEVERITY" => "ERROR",
              "AUDIT_TYPE_ID" => "UPDATE_USER",
              "MODULE_ID" => "itsservice.lk",
              'ITEM_ID' => $arUser['ID'],
              "DESCRIPTION" => "Не удалось обновить пользователя (" . $elementId . ") в битриксе (" . $arUser['ID'] . ")" . $CUser->LAST_ERROR,
            ]);
          }
        }
      } else {
        $ID = $CUser->Add($arFields);
        if (intval($ID) <= 0) {
          CEventLog::Add([
            "SEVERITY" => "ERROR",
            "AUDIT_TYPE_ID" => "ADD_USER",
            "MODULE_ID" => "itsservice.lk",
            "DESCRIPTION" => "Не удалось создать пользователя (" . $elementId . ") " . $CUser->LAST_ERROR,
          ]);
        }
      }
    }
  }

  /**
   * @return mixed
   */
  public function getData()
  {
    return $this->data;
  }

  /**
   * @param mixed $data
   */
  public function setData($data): void
  {
    $this->data = $data;
  }

  /**
   * @return mixed
   */
  public function getAuth()
  {
    return $this->auth;
  }

  /**
   * @param mixed $auth
   */
  public function setAuth($auth): void
  {
    $this->auth = $auth;
  }

  /**
   * @return mixed
   */
  public function getQuery()
  {
    return $this->query;
  }

  /**
   * @param mixed $query
   */
  public function setQuery($query): void
  {
    $this->query = $query;
  }
}