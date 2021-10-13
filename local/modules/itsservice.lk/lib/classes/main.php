<?php

namespace Its\Service;

use Bitrix\Main\IO\Directory,
  Bitrix\Main\IO\File,
  Bitrix\Main\Loader,
  CFile,
  ZipArchive,
  Bitrix\Highloadblock\HighloadBlockTable,
  Bitrix\Main\UserTable,
  Bitrix\Main\Application,
  Bitrix\Main\Web\Uri,
  Bitrix\Main\Engine\Response\Redirect,
  Bitrix\Main\Web\HttpClient,
  Bitrix\Main\Web\Json,
  Bitrix\Main\Web\MimeType,
  Bitrix\Main\Config\Option,
  Bitrix\Main\Data\Cache,
  CUserFieldEnum;

class CMain
{
  const MODULE_ID = 'itsservice.lk';
  const REST_API_URL = 'https://its-online.bitrix24.ru/rest/1/xwwtnn6izo1fyqji/';
  const HL_REGION_NAME = 'UsersRegions';
  const LK_USER_GROUP_ID = 7;
  const LK_PRE_USER_GROUP_ID = 9;
  const FLK_USER_GROUP_ID = 8;

  const LOCAL_APP_CLIENT_ID = 'local.609b935acf3425.32733560';
  const LOCAL_APP_CLIENT_SECRET = 'L8nDEThlQ0XWNd2w3gJ30ouV423Jx7HPcUVzdhl99emmDc5Zu6';
  const LOCAL_APP_REFRESH_TOKEN = '68c7f260005483aa0052065a00000001100e03fb30fca3ae1e1138180b4e6fa204b032';

  public function __construct()
  {
    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) return false;
    require_once __DIR__ . '/../vendor/autoload.php';
  }

  public static function getHlEntity($nameEntity)
  {
    if (!Loader::includeModule("highloadblock"))
      return false;

    $hlblock = HighloadBlockTable::getList([
      'filter' => ['=NAME' => $nameEntity]
    ])->fetch();

    if (!$hlblock) return false;

    return (HighloadBlockTable::compileEntity($hlblock))->getDataClass();
  }

  public static function getUserInfo($userId)
  {
    global $USER_FIELD_MANAGER;
    $arUser = UserTable::getList([
      'select' => ['ID', 'NAME', 'LOGIN', 'LAST_NAME'],
      'order' => ['LAST_LOGIN' => 'DESC'],
      'filter' => ['ID' => $userId]
    ])->fetch();

    $arUser['GROUP_ID'] = UserTable::getUserGroupIds($userId);
    if (in_array(self::LK_PRE_USER_GROUP_ID, $arUser['GROUP_ID']) != false) {
      $arUser['IS_PRE_APPROVED'] = true;
    }

    $EntityTable = new UserTable();
    $arUF = $USER_FIELD_MANAGER->GetUserFields($EntityTable::getUFId(), $userId);
    $arUser = array_merge($arUser, $arUF);

    $arRegion = [];
    if ($arUser['UF_REGION']['VALUE'] > 0) {
      $hlClassName = CMain::getHlEntity(CMain::HL_REGION_NAME);
      $arRegion['REGION'] = $hlClassName::getList([
        "select" => ["*"],
        "filter" => ["ID" => $arUser['UF_REGION']['VALUE']]
      ])->fetch();
    } else {
      $request = Application::getInstance()->getContext()->getRequest();
      $uri = new Uri($request->getRequestUri());
      $path = $uri->getPath();
      if ($path != '/error.php' && $userId > 0) {
        $response = new Redirect('/error.php', true);
        $response->send();
      }
    }

    return array_merge($arUser, $arUF, $arRegion);
  }

  public static function getEnumField($fieldName, $xml = true): array
  {
    $arResult = [];
    $CUserFieldEnum = new CUserFieldEnum();
    $rsEnumField = $CUserFieldEnum->GetList([], ["USER_FIELD_ID" => $fieldName]);
    while ($arRes = $rsEnumField->GetNext()) {
      if($xml)
        $arResult[$arRes['XML_ID']] = $arRes;
      else
        $arResult[$arRes['ID']] = $arRes;
    }

    return $arResult;
  }

  public static function getRegions(): array
  {
    $arRegions = [];
    $hlClassName = CMain::getHlEntity(CMain::HL_REGION_NAME);

    $rsRegion = $hlClassName::getList([
      "select" => ["ID", "UF_XML_ID"],
      "order" => ["ID" => "ASC"]
    ]);
    while ($arRes = $rsRegion->fetch()) {
      $arRegions[$arRes['UF_XML_ID']] = $arRes['ID'];
    }

    return $arRegions;
  }

  /**
   * загрузка файлов с битрикса24 в систему
   * @param array $dealData - массив данных сделки из битрикс24
   * @param int $systemDealId - системный идентификатор сделки
   */
  public static function fetchDealDocs(array $dealData, int $systemDealId)
  {
    $arFileType = CMain::getEnumField(25);
    $hlFileClassName = CMain::getHlEntity(CDeal::HL_FILE_NAME);

    $arSystemFilesIDs = [];
    $rsSystemFiles = $hlFileClassName::getlist([
      'filter' => [
        'UF_DEAL_ID' => $systemDealId,
        'UF_IS_AGREEMENT' => false,
        'UF_IS_CET_CONCLUSION' => false,
        'UF_IS_CONCLUSION_SUBSIDION' => false,
        'UF_IS_AGREEMENT_SUBSIDION' => false,
      ]
    ]);
    while ($arFiles = $rsSystemFiles->fetch()) {
      $arSystemFilesIDs[] = $arFiles['UF_XML_ID'];
      $arSystemFiles[$arFiles['UF_XML_ID']] = $arFiles;
    }

    $arBitrix24File = $arBitrix24FileMultiple = [];
    switch ($dealData['CATEGORY_ID']) {
      case CDeal::ACCREDITATION_DEAL_CATEGORY_ID:
        $arBitrix24File = CDeal::ACCREDITATION_BITRIX24_FILE_LIST;
        $arBitrix24FileMultiple = CDeal::BITRIX24_MULTIPLE_FILE_FIELDS;
        break;
      case CDeal::INSTALLATION_CENTER_CATEGORY_ID:
        $arBitrix24File = CDeal::SUBSIDION_BITRIX24_FILE_LIST;
        break;
      case CDeal::TS_CATEGORY_ID:
        $arBitrix24File = CDeal::TS_BITRIX24_FILE_LIST;
        foreach ($arBitrix24File as $key => $value) {
          $replace = ['/', '|', '\\', ':', '*', '?', '"', '<', '>'];
          $arBitrix24File[$key] = $value.' '.str_replace($replace, '', $dealData['TITLE']);
        }
        break;
    }

    $mimeTypeList = MimeType::getMimeTypeList();

    $arBitrix24FileValue = $arBitrix24FileValueIDs = [];
    foreach ($dealData as $key => $value) {
      if (isset($arBitrix24File[$key])) {
        if (in_array($key, CDeal::BITRIX24_MULTIPLE_FILE_FIELDS) !== false) {
          foreach ($value as $file) {
            if (isset($file['downloadUrl'])) {
              $arBitrix24FileValueIDs[] = $file['id'];
              $file['KEY_FIELD'] = $key;
              $arBitrix24FileValue[] = $file;
            }
          }
        } else {
          if (isset($value[0]['id'])) $value = $value[0];
          if (!$value['downloadUrl']) continue;
          $arBitrix24FileValueIDs[] = $value['id'];
          $value['KEY_FIELD'] = $key;
          $arBitrix24FileValue[] = $value;
        }
      }
    }

    $arNewFile= [];
    $countModifyFiles = 0;
    foreach ($arBitrix24FileValue as $value) {
      if ($hlFileClassName::getlist(['filter' => ['UF_XML_ID' => $value['id'], 'UF_DEAL_ID' => $systemDealId]])->getSelectedRowsCount() > 0) continue;

      if (!isset($arSystemFiles[$value['id']])) {
        $arFields = [
          'UF_XML_ID' => $value['id'],
          'UF_DOWNLOAD_URL' => $value['downloadUrl'],
          'UF_DEAL_ID' => $systemDealId,
          'UF_STATUS' => $arFileType[CDeal::HL_FILE_FIELD_ENUM_TYPE_XML_ID_DEFAULT]['ID'],
          'UF_FIELD_CODE' => $value['KEY_FIELD'],
        ];

        $resFileId = $hlFileClassName::Add($arFields)->getId();
        $countModifyFiles++;

        $rsSystemFiles = $hlFileClassName::getlist([
          'select' => ['ID'],
          'filter' => [
            'UF_XML_ID' => $value['id'],
            'UF_DEAL_ID' => $systemDealId,
          ]
        ]);
        if ($rsSystemFiles->getSelectedRowsCount() > 1) {
          while ($arFiles = $rsSystemFiles->fetch()) {
            if ($arFiles['ID'] != $resFileId) {
              $hlFileClassName::delete($arFiles['ID']);
            }
          }
        }

        if ($resFileId > 0) {
          $token = CMain::getAccessTokenBitrix24();
          $urlFile = 'https://its-online.bitrix24.ru' . $value['downloadUrl'] . '&auth=' . $token;

          $arFile = CFile::MakeFileArray($urlFile);

          $f = fopen($_SERVER['DOCUMENT_ROOT']."/myfile.txt", "a");
          fwrite($f, print_r($value, 1).PHP_EOL);
          fwrite($f, print_r($urlFile, 1).PHP_EOL);
          fwrite($f, print_r($arFile, 1).PHP_EOL);
          fclose($f);

          foreach ($mimeTypeList as $k => $v) {
            if ($v == $arFile['type']) {
              $extensionFile = $k;
              break;
            }
          }

          if(in_array($value['KEY_FIELD'], $arBitrix24FileMultiple)) {
            $arFile['name'] = $arBitrix24File[$value['KEY_FIELD']] . '_' . $resFileId . '.' . $extensionFile;
          } else {
            $arFile['name'] = $arBitrix24File[$value['KEY_FIELD']] . '.' . $extensionFile;
          }

          $arFields = [
            'UF_NAME' => $arFile['name'],
            'UF_FILE' => $arFile,
          ];

          $arNewFile[] = [
            'ID' => $resFileId,
            'UF_NAME' => $arFile['name'],
          ];

          $hlFileClassName::update($resFileId, $arFields);
        }
      }
    }


    foreach ($arSystemFilesIDs as $fileXmlId) {
      if (!in_array($fileXmlId, $arBitrix24FileValueIDs)) {
        $searchFileName = mb_substr($arSystemFiles[$fileXmlId]['UF_NAME'], 0, mb_stripos($arSystemFiles[$fileXmlId]['UF_NAME'], $arSystemFiles[$fileXmlId]['ID']));
        foreach($arNewFile as $newFile) {
          $newFileName = mb_substr($newFile['UF_NAME'], 0, mb_stripos($newFile['UF_NAME'], $newFile['ID']));
          if($searchFileName == $newFileName) {
            $hlFileCommentsClassName = CMain::getHlEntity(CFileComments::HL_FILE_COMMENTS_NAME);
            $arHistoryComments = $hlFileCommentsClassName::getList([
              'order' => ['ID' => 'DESC'],
              'filter' => ['UF_FILE_ID' => $arSystemFiles[$fileXmlId]['ID']]
            ])->fetchAll();
            foreach($arHistoryComments as $comment) {
              $hlFileCommentsClassName::update($comment['ID'], ['UF_FILE_ID' => $newFile['ID']]);
            }
          }
        }

        $hlFileClassName::delete($arSystemFiles[$fileXmlId]['ID']);
      }
    }

    $countFilesCurrentDeal = self::getCountFiles($systemDealId);

    switch ($dealData['CATEGORY_ID']) {
      case CDeal::ACCREDITATION_DEAL_CATEGORY_ID:
        if ($countFilesCurrentDeal >= count($arBitrix24File)-1 && $dealData['UF_CRM_1621841213766'] && ($countModifyFiles > 0 || $dealData['FIRST_SIGNED'])) {
          self::sendDocumentsSignature($systemDealId);
        }
        break;
      case CDeal::INSTALLATION_CENTER_CATEGORY_ID:
        if (($countModifyFiles > 0 || $dealData['FIRST_SIGNED']) && $dealData['STAGE_ID'] != CDeal::SUBSIDION_DEAL_STAGE_FILLING_DOCUMENTS
          && $dealData['STAGE_ID'] != CDeal::SUBSIDION_DEAL_STAGE_DOCUMENTS_PAYD && $dealData['STAGE_ID'] != CDeal::SUBSIDION_DEAL_STAGE_PAYMEND_CONFIRMED) {
          self::sendDocumentsSignature($systemDealId);
        }
        break;
      case CDeal::TS_CATEGORY_ID:
        if ($countModifyFiles > 0 && $dealData['STAGE_ID'] != CDeal::TS_DEAL_STAGE_GET_DATA
          && $dealData['STAGE_ID'] != CDeal::TS_DEAL_STAGE_REGISTRATION_PTE && $dealData['STAGE_ID'] != CDeal::TS_DEAL_STAGE_PREPARATION_DOCS_GIBDD) {
          $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);
          $arDealRetool = $hlDealClassName::getlist([
            'select' => [
              'ID',
              'UF_DEAL_RETOOL',
              'MAIN_DEAL_DOCS_CONFIRMED' => 'DEAL.UF_PACK_DOCS_CONFIRMED'
            ],
            'filter' => [
              'ID' => $systemDealId,
              'MAIN_DEAL_DOCS_CONFIRMED' => true,
            ],
            'runtime' => [
              'DEAL' => [
                'data_type' => CMain::getHlEntity(CDeal::HL_DEAL_NAME),
                'reference' => [
                  '=this.UF_DEAL_RETOOL' => 'ref.ID',
                ],
                'join_type' => 'inner',
              ]
            ],
          ]);
          if($arDealRetool->getSelectedRowsCount() > 0) {
            self::sendDocumentsSignature($systemDealId);
          }

        }
        break;
    }
  }

  public static function getCountFiles($dealId)
  {
    if ($dealId <= 0) return;

    $hlFileClassName = CMain::getHlEntity(CDeal::HL_FILE_NAME);
    return $hlFileClassName::getlist([
      'filter' => [
        'UF_DEAL_ID' => $dealId,
        'UF_IS_AGREEMENT' => false,
      ]
    ])->getSelectedRowsCount();
  }

  public static function getDataHttp($method, $params)
  {
    sleep(1);
    $http = new HttpClient();
    $http->setTimeout(20);
    $http->waitResponse(20);
    $http->setStreamTimeout(20);
    $json = $http->post(CMain::REST_API_URL . $method, $params);
    $result = Json::decode($json);

    if ($result['error'] == 'QUERY_LIMIT_EXCEEDED') {
      CMain::getDataHttp($method, $params);
    }

    return $result;
  }

  public static function calculatePercentOfNumber($totalNum, $num)
  {
    $result = 0;

    if ($totalNum == $num && $totalNum > 0) {
      $result = 100;
    } elseif ($num > 0) {
      $result = (100 - round((($totalNum - $num) * 100) / $totalNum));
    }

    return $result;
  }

  public static function createZip($arFiles, $fileName): string
  {
    $arPackFiles = [];
    $zipFileName = "/upload/$fileName.zip";

    if (file_exists($_SERVER["DOCUMENT_ROOT"] . $zipFileName)) {
      unlink($_SERVER["DOCUMENT_ROOT"] . $zipFileName);
    }

    foreach ($arFiles as $iFileID) {
      $arPackFiles[] = CFile::GetFileArray($iFileID);
    }

    $zip = new ZipArchive();
    $zip->open($_SERVER['DOCUMENT_ROOT'] . $zipFileName, ZIPARCHIVE::CREATE);
    $count = 0;
    foreach ($arPackFiles as $arFile) {
      $name = str_pad($count, 3, '0', STR_PAD_LEFT) . $arFile['FILE_NAME'];

      $zip->addFile($_SERVER['DOCUMENT_ROOT'] . $arFile['SRC'], $name);
      $count++;
    }
    $zip->close();
    return $zipFileName;
  }

  public static function sendDocumentsSignature($systemDealId)
  {
    $dealIDs = [$systemDealId];
    $hlDealClassName = CMain::getHlEntity(CDeal::HL_DEAL_NAME);
    $arDeal = $hlDealClassName::getlist([
      'select' => ['ID', 'UF_XML_ID', 'UF_CATEGORY_ID', 'UF_COMPANY_ID', 'UF_DEAL_RETOOL'],
      'filter' => ['ID' => $systemDealId]
    ])->fetch();

    if (CDeal::INSTALLATION_CENTER_CATEGORY_ID == $arDeal['UF_CATEGORY_ID']) {
      $arDealRetool = $hlDealClassName::getlist([
        'select' => ['ID'],
        'filter' => ['UF_DEAL_RETOOL' => $dealIDs]
      ])->fetchAll();

      foreach ($arDealRetool as $deal) {
        $dealIDs[] = $deal['ID'];
      }
    }

    if (CDeal::TS_CATEGORY_ID == $arDeal['UF_CATEGORY_ID']) {
      $arRetoolDeal = $hlDealClassName::getlist([
        'select' => ['UF_XML_ID'],
        'filter' => ['ID' => $arDeal['UF_DEAL_RETOOL']]
      ])->fetch();
      $dealIDs[] = $arDeal['UF_DEAL_RETOOL'];
      $systemRetoolDealId = $arDeal['UF_DEAL_RETOOL'];
      $systemRetoolDealXmlId = $arRetoolDeal['UF_XML_ID'];

      $arDealRetool = $hlDealClassName::getlist([
        'select' => ['ID'],
        'filter' => ['UF_DEAL_RETOOL' => $dealIDs]
      ])->fetchAll();

      foreach ($arDealRetool as $deal) {
        $dealIDs[] = $deal['ID'];
      }
    }

    $arFileType = CMain::getEnumField(25);

    //список статуса файлов
    $hlFileClassName = CMain::getHlEntity(CDeal::HL_FILE_NAME);
    $rsSystemFiles = $hlFileClassName::getlist([
      'select' => ['UF_FILE'],
      'filter' => [
        'UF_DEAL_ID' => $dealIDs,
        'UF_IS_AGREEMENT' => false,
        'UF_FILE_SIGNED' => false,
        'UF_IS_CET_CONCLUSION' => false,
        'UF_IS_CONCLUSION_SUBSIDION' => false,
        //'UF_IS_AGREEMENT_SUBSIDION' => false,
        '!UF_STATUS' => $arFileType[CDeal::HL_FILE_FIELD_ENUM_TYPE_XML_ID_REJECT]['ID']
      ]
    ]);
    $arFileSrc = [];
    while ($arSystemFiles = $rsSystemFiles->fetch()) {
      $filePath = Application::getDocumentRoot() . CFile::GetPath($arSystemFiles['UF_FILE']);
      if (File::isFileExists($filePath)) {
        $arFileSrc[] = 'https://xn--j1ab.xn--90ad8a.xn--p1ai' . CFile::GetPath($arSystemFiles['UF_FILE']);
      }
    }

    if (CDeal::TS_CATEGORY_ID == $arDeal['UF_CATEGORY_ID']) {
      $systemDealId = $systemRetoolDealId;
    }

    $localeDirPath = Application::getDocumentRoot() . '/upload/signed_documents/' . $arDeal['ID'] . '/';
    $localeFilePath = Application::getDocumentRoot() . '/upload/signed_documents/' . $arDeal['ID'] . '/' . $arDeal['ID'] . '.zip';
    if (Directory::isDirectoryExists($localeDirPath)) {
      if (File::isFileExists($localeFilePath)) {
        File::deleteFile($localeFilePath);
      }
    } else {
      Directory::createDirectory($localeDirPath);
    }

    if (empty($arFileSrc)) return;
    $params = [
      'package_id' => $systemDealId,
      'docs' => $arFileSrc,
      'ftp' => [
        'host' => '91.210.168.68',
        'port' => 21,
        'path' => '/' . $systemDealId,
        'username' => 'signed_documents',
        'password' => '7wujE2Js3Ax7WkZv'
      ],
      'webhook' => 'https://xn--j1ab.xn--90ad8a.xn--p1ai/restapi/signed_documents.php?package_id=' . $systemDealId
    ];

    $f = fopen($_SERVER['DOCUMENT_ROOT']."/signeddocs.txt", "a");
    fwrite($f, print_r($params, 1));
    fclose($f);

    $http = new HttpClient();
    $http->setTimeout(20);
    $http->waitResponse(20);
    $http->setStreamTimeout(20);
    $http->setHeader('Content-Type', 'application/json', true);
//    $json = $http->post('http://518929-cj71393.tmweb.ru/api/prepare-docs', json_encode($params));
//    $json = $http->post('192.168.48.168:8000', json_encode($params));
    $json = $http->post('https://sign.webservise.ru/api/prepare-docs', json_encode($params));

    $result = Json::decode($json);

    $arParams = [
      'fields' => [
        'ENTITYTYPEID' => 2,
        'POST_TITLE' => 'Необходимо подписать документы',
        'MESSAGE' => "Необходимо подписать документы <a href='" . $result . "' target='_blank'>Подписать</a>",
      ]
    ];
    $arParams['fields']['ENTITYID'] = (CDeal::TS_CATEGORY_ID == $arDeal['UF_CATEGORY_ID']) ? $systemRetoolDealXmlId : $arDeal['UF_XML_ID'];

    CMain::getDataHttp('crm.livefeedmessage.add', $arParams);
  }

  public static function getAccessTokenBitrix24()
  {
    $currentTimestamp = time();
    $timestamp = Option::get(self::MODULE_ID, "local_app_access_token_timestamp");

    if ($currentTimestamp >= $timestamp) {
      $aupdateAccessTokenUrl = 'https://oauth.bitrix.info/oauth/token/?grant_type=refresh_token&client_id=' . self::LOCAL_APP_CLIENT_ID . '&client_secret=' . self::LOCAL_APP_CLIENT_SECRET . '&refresh_token=' . Option::get(self::MODULE_ID, "local_app_refresh_token");
      $http = new HttpClient();
      $result = Json::decode($http->get($aupdateAccessTokenUrl));

      if($result['error'] != 'expired_token') {
        Option::set(self::MODULE_ID, "local_app_access_token_timestamp", $result['expires']);
        $accessToken = $result['access_token'];
        Option::set(self::MODULE_ID, "local_app_access_token", $accessToken);
        Option::set(self::MODULE_ID, "local_app_refresh_token", $result['refresh_token']);
      }
    } else {
      $accessToken = Option::get(self::MODULE_ID, "local_app_access_token");
    }

    return $accessToken;
  }
}