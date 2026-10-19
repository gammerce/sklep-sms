<?php

use App\Install\Migration;

class AddSteamAuth extends Migration
{
    public function up()
    {
        // steam_verified marks a steam_id that was proven via Steam OpenID.
        // Only a verified steam_id may be used to authenticate; it is immutable
        // outside of the unlink flow (see SteamAuthController).
        //
        // email_change_* hold a pending e-mail change (mirrors reset_password_key):
        // the new address is not applied until it is confirmed by a message sent
        // to it.
        //
        // "steam_only" accounts (auto-created on first Steam login) are detected by
        // an empty password value, so no dedicated column is needed for them.
        $this->executeQueries([
            <<<EOF
            ALTER TABLE `ss_users`
                ADD COLUMN `steam_verified` TINYINT(1) NOT NULL DEFAULT 0,
                ADD COLUMN `email_change_key` VARCHAR(32) NOT NULL DEFAULT '',
                ADD COLUMN `email_change_new` VARCHAR(128) NOT NULL DEFAULT ''
EOF
        ,
        ]);
    }
}
