<?php

namespace Its\Service\Gbo;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader,
    Bitrix\Highloadblock\HighloadBlockTable,
    Bitrix\Main\LoaderException,
    CUserFieldEnum,
    Bitrix\Main\SystemException;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Its\Service\CMain;

class CHelper
{
  const MODULE_ID = 'itsservice.lk';
  const LOCAL_APP_CLIENT_ID = 'local.609b935acf3425.32733560';
  const LOCAL_APP_CLIENT_SECRET = 'L8nDEThlQ0XWNd2w3gJ30ouV423Jx7HPcUVzdhl99emmDc5Zu6';
  const LOCAL_APP_REFRESH_TOKEN = '722ccb60005483aa0052065a00000001100e03dfc6ae79b73174ca3eb5ec21eb359b54';

  const REST_API_URL = 'https://its-online.bitrix24.ru/rest/1/xwwtnn6izo1fyqji/';
  const ACCREDITATION_DEAL_CATEGORY_ID = 12;
  const INSTALLATION_CENTER_CATEGORY_ID = 2;
  const TS_CATEGORY_ID = 4;

  /**
   * @throws LoaderException
   * @throws SystemException
   */
  public static function getHlEntity($nameEntity)
  {
    if (!Loader::includeModule("highloadblock"))
      throw new LoaderException('Error: include highloadblock module');

    $hlblock = HighloadBlockTable::getList([
      'filter' => ['=NAME' => $nameEntity]
    ])->fetch();

    if (!$hlblock) return false;

    return (HighloadBlockTable::compileEntity($hlblock))->getDataClass();
  }

  public static function getDataHttp($method, $params)
  {
    sleep(1);
    $http = new HttpClient();
    $http->setTimeout(20);
    $http->waitResponse(20);
    $http->setStreamTimeout(20);
    $json = $http->post(self::REST_API_URL . $method, $params);
    $result = Json::decode($json);

    if ($result['error'] == 'QUERY_LIMIT_EXCEEDED') {
      CMain::getDataHttp($method, $params);
    }

    return $result;
  }

  public static function getEnumField($fieldName): array
  {
    $arResult = [];
    $CUserFieldEnum = new CUserFieldEnum();
    $rsEnumField = $CUserFieldEnum->GetList([], ["USER_FIELD_ID " => $fieldName]);
    while ($arRes = $rsEnumField->GetNext()) {
      $arResult[$arRes['XML_ID']] = $arRes;
    }

    return $arResult;
  }

  public static function getAccessTokenBitrix24()
  {
    $currentTimestamp = time();
    $timestamp = Option::get(self::MODULE_ID, "local_app_access_token_timestamp");
    if ($currentTimestamp >= $timestamp) {
      $aupdateAccessTokenUrl = 'https://oauth.bitrix.info/oauth/token/?grant_type=refresh_token&client_id=' . self::LOCAL_APP_CLIENT_ID . '&client_secret=' . self::LOCAL_APP_CLIENT_SECRET . '&refresh_token=' . self::LOCAL_APP_REFRESH_TOKEN;
      $http = new HttpClient();
      $result = Json::decode($http->get($aupdateAccessTokenUrl));

      Option::set(self::MODULE_ID, "local_app_access_token_timestamp", $result['expires']);
      $accessToken = $result['access_token'];
      Option::set(self::MODULE_ID, "local_app_access_token", $accessToken);
    } else {
      $accessToken = Option::get(self::MODULE_ID, "local_app_access_token");
    }

    return $accessToken;
  }
}