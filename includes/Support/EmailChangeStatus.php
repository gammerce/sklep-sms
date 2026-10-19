<?php
namespace App\Support;

/**
 * Status keys produced by the e-mail change flow. Pages whitelist them before
 * translating a value read from the query string.
 */
final class EmailChangeStatus
{
    public const KEYS = [
        "email_changed",
        "email_change_sent",
        "email_change_invalid",
        "email_change_not_sent",
        "email_change_wrong_password",
        "email_change_wrong_token",
        "email_occupied",
        "demo_mode",
        "wrong_email",
    ];
}
