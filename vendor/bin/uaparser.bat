@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../ua-parser/uap-php/bin/uaparser
php "%BIN_TARGET%" %*
