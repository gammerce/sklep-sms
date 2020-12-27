<?php
namespace App\Server;

use MyCLabs\Enum\Enum;

/**
 * @method static Platform AMXMODX()
 * @method static Platform SOURCEMOD()
 */
class Platform extends Enum
{
    const AMXMODX = "amxmodx";
    const SOURCEMOD = "sourcemod";
}
