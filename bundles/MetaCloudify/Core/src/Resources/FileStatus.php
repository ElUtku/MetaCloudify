<?php

namespace MetaCloudify\Core\Resources;

enum FileStatus: string
{
    case NEW = 'new';
    case MODIFIED = 'modified';
    case DELETED = 'deleted';
    case EXISTENT = 'existent';
}
