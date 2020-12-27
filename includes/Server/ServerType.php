<?php
namespace App\Server;

use MyCLabs\Enum\Enum;

/**
 * @method static ServerType AMXMODX()
 * @method static ServerType SOURCEMOD()
 */
class ServerType extends Enum
{
    const AMXMODX = "amxmodx";
    const SOURCEMOD = "sourcemod";
}
