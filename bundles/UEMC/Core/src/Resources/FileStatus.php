<?php

namespace UEMC\Core\Resources;

enum FileStatus: string
{
    case NEW = 'new';
    case MODIFIED = 'modified';
    case DELETED = 'deleted';
}
