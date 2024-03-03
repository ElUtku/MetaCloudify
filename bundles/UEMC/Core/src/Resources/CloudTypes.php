<?php

namespace UEMC\Core\Resources;

enum CloudTypes: string
{
    case GoogleDrive = 'googledrive';
    case OneDrive = 'onedrive';
    case OwnCloud = 'owncloud';
    case FTP = 'ftp';
}

