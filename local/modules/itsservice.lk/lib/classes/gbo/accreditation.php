<?php

namespace Its\Service\Gbo;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\DB\Exception;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\MimeType;

class CAccreditation
{
  const HL_ACCREDITATION_NAME = 'GboAccreditation';
  const HL_ACCREDITATION_FILE_NAME = 'GboFileAccreditation';
  const ENTITY_NAME = 'ACCREDITATION';

  private $entity;

  function __construct()
  {
    $this->entity = CHelper::getHlEntity(self::HL_ACCREDITATION_NAME);
  }

  public function restSaveDeal($dealData)
  {
    $CCompany = new CCompany();
    $arCompany = $CCompany->getList();
    $arCompanyByXmlId = [];
    foreach($arCompany as $company) {
      $arCompanyByXmlId[$company['UF_XML_ID']] = $company;
    }

    $arAccreditation = $this->getList(['ID' => 'DESC'], ['UF_XML_ID' => $dealData['ID']]);
    if(count($arAccreditation) > 0) {
      foreach($arAccreditation as $key => $accreditation) {
        if($key == 0) continue;
        $this->delete($accreditation['ID']);
      }
    }
    $arAccreditation = $arAccreditation[0];
    $acreditationId = $arAccreditation['ID'];

    $arFieldsDeal = [
      'UF_NAME' => $dealData['TITLE'],
      'UF_XML_ID' => $dealData['ID'],
      'UF_COMPANY_ID' => $arCompanyByXmlId[$dealData['COMPANY_ID']]['ID'],
      'UF_STAGE_ID' => $dealData['STAGE_ID'],
      'UF_PACK_DOCS_CONFIRMED' => $dealData['UF_CRM_1621841213766'],   //пакет документов подтвержден?
    ];

    if (empty($arAccreditation)) {
      $resAccreditation = $this->add($arFieldsDeal);
      $acreditationId = $resAccreditation->getId();
    } else {
      if(!$arAccreditation['UF_PACK_DOCS_CONFIRMED']) {
        $dealData['FIRST_SIGNED'] = true;
      }
      $this->update($acreditationId, $arFieldsDeal);
    }

    if ($acreditationId > 0 ) {
      $this->fetchDealDocs($dealData, $acreditationId);
    }
  }

  private function fetchDealDocs(array $dealData, int $acreditationId)
  {
    $arFileStatus = CHelper::getEnumField('UF_STATUS');

    $arFileName = $this->getFileName();

    $arDownloadFiles = [];
    foreach ($dealData as $key => $value) {
      if(isset($arFileName[$key])) {
        if(isset($value[0])) {
          foreach($value as $item) {
            if(empty($item)) continue;

            $arDownloadFiles[] = array_merge($item, ['FIELD_KEY' => $key]);
          }
        } else {
          if(empty($value)) continue;

          $arDownloadFiles[] = array_merge($value, ['FIELD_KEY' => $key]);
        }
      }
    }

    $CFile = new CFile();
    $mimeTypeList = MimeType::getMimeTypeList();
    $addedFileDlag = false;
    foreach ($arDownloadFiles as $arValue) {
      if (!empty($CFile->getlist([], ['UF_XML_ID' => $arValue['id']]))) continue;

      $arFields = [
        'UF_STATUS' => $arFileStatus[CFile::HL_FILE_FIELD_ENUM_TYPE_XML_ID_DEFAULT]['ID'],
        'UF_XML_ID' => $arValue['id'],
        'UF_TYPE' => 87
      ];

      $resFileId = $CFile->Add($arFields);
      $addedFileDlag = true;

      if(intval($resFileId) <= 0 ) continue;

      $token = CHelper::getAccessTokenBitrix24();
      $urlFile = 'https://its-online.bitrix24.ru' . $arValue['downloadUrl'] . '&auth=' . $token;

      $arFile = \CFile::MakeFileArray($urlFile);

      $extensionFile= 'pdf';
      foreach ($mimeTypeList as $k => $v) {
        if ($v == $arFile['type']) {
          $extensionFile = $k;
          break;
        }
      }

      $arFile['name'] = $arFileName[$arValue['FIELD_KEY']]['UF_NAME'] . '_' . $resFileId . '.' . $extensionFile;

      $arFields = [
        'UF_NAME' => $arFileName[$arValue['FIELD_KEY']]['ID'],
        'UF_FILE' => $arFile,
      ];

      $CFile->update($resFileId, $arFields);

      $arFields = [
        'UF_ACCREDITATION_ID' => $acreditationId,
        'UF_FILE_ID' => $resFileId,
        'UF_ACTIVE' => true
      ];
      CHelper::getHlEntity(self::HL_ACCREDITATION_FILE_NAME)::add($arFields);
    }

    if(count($arDownloadFiles) >= count($this->getCountFiles()) && $addedFileDlag) {
      $this->sendDocumentsSignature($acreditationId);
    }
  }

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

  public function getList($arOrder = [], $arFilter = [], $arSelect = ['*'], $params = []): array
  {
    $arAccreditation = [];
    $rsAccreditation = $this->entity::getlist([
      'order' => $arOrder,
      'select' => $arSelect,
      'filter' => $arFilter,
    ]);

    while ($arResult = $rsAccreditation->fetch()) {
      $arAccreditation[] = $arResult;
    }

    return $arAccreditation;
  }

  private function getFileName()
  {
    $arFileName = [];
    $entity = CHelper::getHlEntity(CFileName::HL_FILENAME_NAME);
    $rsRes = $entity::getList([
      'select' => [
        '*',
        'UF_ENTITY' => 'ENTITY.UF_ENTITY'
      ],
      'filter' => [
        'UF_ENTITY' => self::ENTITY_NAME,
      ],
      'runtime' => [
        'ENTITY' => [
          'data_type' => CHelper::getHlEntity(CEntyty::HL_ENTITY_NAME),
          'reference' => [
            '=this.UF_ENTITY_ID' => 'ref.ID',
          ],
          'join_type' => 'inner',
        ],
      ],
    ]);
    while($arRes = $rsRes->fetch()) {
      $arFileName[$arRes['UF_FIELD_CODE']] = $arRes;
    }

    return $arFileName;
  }

  public function getCountFiles()
  {
    return count($this->getFileName());
  }

  public static function sendDocumentsSignature($accreditationId)
  {
    $rsFiles = CHelper::getHlEntity(self::HL_ACCREDITATION_FILE_NAME)::getList([
      'select' => [
        '*',
        'NAME' => 'FILES.UF_NAME',
        'STATUS' => 'FILES.UF_STATUS',
        'FILE' => 'FILES.UF_FILE',
        'FILE_SIGNED' => 'FILES.UF_FILE_SIGNED',
        'TYPE' => 'FILES.UF_TYPE',
      ],
      'filter' => [
        'UF_ACCREDITATION_ID' => $accreditationId,
        'UF_ACTIVE' => true,
        'FILE_SIGNED' => false,
        'TYPE' => 87,
      ],
      'runtime' => [
        'FILES' => [
          'data_type' => CHelper::getHlEntity(CFile::HL_FILE_NAME),
          'reference' => [
            '=this.UF_FILE_ID' => 'ref.ID',
          ],
          'join_type' => 'inner',
        ],
      ],
    ]);
    while ($arFiles = $rsFiles->fetch()) {
      $filePath = Application::getDocumentRoot() . \CFile::GetPath($arFiles['FILE']);
      if (File::isFileExists($filePath)) {
        $arFileSrc[] = 'https://xn--j1ab.xn--90ad8a.xn--p1ai' . \CFile::GetPath($arFiles['FILE']);
      }
    }

    $localeDirPath = Application::getDocumentRoot() . '/upload/signed_documents/' . $accreditationId . '/';
    $localeFilePath = Application::getDocumentRoot() . '/upload/signed_documents/' . $accreditationId . '/' . $accreditationId . '.zip';
    if (Directory::isDirectoryExists($localeDirPath)) {
      if (File::isFileExists($localeFilePath)) {
        File::deleteFile($localeFilePath);
      }
    } else {
      Directory::createDirectory($localeDirPath);
    }

    if (empty($arFileSrc)) return;
    $params = [
      'package_id' => $accreditationId,
      'docs' => $arFileSrc,
      'ftp' => [
        'host' => '91.210.168.68',
        'port' => 21,
        'path' => '/' . $accreditationId,
        'username' => 'signed_documents',
        'password' => '7wujE2Js3Ax7WkZv'
      ],
      'webhook' => 'https://xn--j1ab.xn--90ad8a.xn--p1ai/restapi/signed_documents.php?package_id=' . $accreditationId.'&entity='.self::ENTITY_NAME
    ];

    $http = new HttpClient();
    $http->setTimeout(20);
    $http->waitResponse(20);
    $http->setStreamTimeout(20);
    $http->setHeader('Content-Type', 'application/json', true);
    $json = $http->post('https://sign.webservise.ru/api/prepare-docs', json_encode($params));

    $result = Json::decode($json);

    $arParams = [
      'fields' => [
        'ENTITYID' => 3302,
        'ENTITYTYPEID' => 2,
        'POST_TITLE' => 'Необходимо подписать документы',
        'MESSAGE' => "Необходимо подписать документы <a href='" . $result . "' target='_blank'>Подписать</a>",
      ]
    ];
    CHelper::getDataHttp('crm.livefeedmessage.add', $arParams);
  }

  public function delete($accreditationId)
  {
    $arAccreditation = $this->entity::getlist([
      'select' => ['ID'],
      'filter' => ['UF_XML_ID' => $accreditationId]
    ])->fetch();
    if ($arAccreditation['ID'] > 0) {
      $this->deleteFiles($arAccreditation['ID']);
      $this->entity::delete($arAccreditation['ID']);
    }
  }

  private function deleteFiles($accreditationId)
  {
    $hlFileClassName = CMain::getHlEntity(CDeal::HL_FILE_NAME);

    $rsSystemFiles = $hlFileClassName::getlist([
      'select' => ['ID'],
      'filter' => [
        'UF_DEAL_ID' => $accreditationId,
      ]
    ]);
    while($arSystemFiles = $rsSystemFiles->fetch()) {
      $hlFileClassName::delete($arSystemFiles['ID']);
    }
  }
}