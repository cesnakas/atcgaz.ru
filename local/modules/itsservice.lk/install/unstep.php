<?php
use Bitrix\Main\Localization\Loc;

if(!check_bitrix_sessid()) return;

echo CAdminMessage::ShowNote(Loc::getMessage('UNINSTALL_SUCCESS'));
