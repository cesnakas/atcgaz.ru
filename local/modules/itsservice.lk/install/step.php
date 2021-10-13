<?php
use Bitrix\Main\Localization\Loc;

if(!check_bitrix_sessid()) return;
IncludeModuleLangFile(__FILE__);

echo CAdminMessage::ShowNote(Loc::getMessage('INSTALL_SUCCESS'));