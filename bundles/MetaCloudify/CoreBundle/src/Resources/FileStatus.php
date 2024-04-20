<?php

namespace MetaCloudify\CoreBundle\Resources;

enum FileStatus: string
{
    case NEW = 'new';
    case MODIFIED = 'modified';
    case DELETED = 'deleted';
    case EXISTENT = 'existent';
}
