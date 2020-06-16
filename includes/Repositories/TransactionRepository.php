<?php
namespace App\Repositories;

use App\Models\Transaction;

class TransactionRepository
{
    private $transactionsQuery = <<<EOF
(SELECT bs.id AS `id`,
bs.user_id AS `user_id`,
u.username AS `username`,
bs.payment AS `payment`,
bs.payment_id AS `payment_id`,
IFNULL(pdb.external_id, bs.payment_id) AS `external_payment_id`,
bs.service_id AS `service_id`,
bs.server_id AS `server_id`,
bs.amount AS `amount`,
bs.auth_data AS `auth_data`,
bs.email AS `email`,
bs.promo_code AS `promo_code`,
bs.extra_data AS `extra_data`,
CONCAT_WS('', pa.ip, ps.ip, pt.ip, pw.ip, pdb.ip) AS `ip`,
CONCAT_WS('', pa.platform, ps.platform, pt.platform, pw.platform, pdb.platform) AS `platform`,
CONCAT_WS('', ps.income, pt.income, pdb.income) AS `income`,
CONCAT_WS('', ps.cost, pt.cost, pw.cost, pdb.cost) AS `cost`,
pa.aid AS `aid`,
u2.username AS `adminname`,
ps.code AS `sms_code`,
ps.text AS `sms_text`,
ps.number AS `sms_number`,
IFNULL(ps.free, IFNULL(pt.free, IFNULL(pdb.free, 0))) AS `free`,
bs.timestamp AS `timestamp`
FROM `ss_bought_services` AS bs
LEFT JOIN `ss_users` AS u ON u.uid = bs.user_id
LEFT JOIN `ss_payment_admin` AS pa ON bs.payment = 'admin' AND pa.id = bs.payment_id
LEFT JOIN `ss_users` AS u2 ON u2.uid = pa.aid
LEFT JOIN `ss_payment_sms` AS ps ON bs.payment = 'sms' AND ps.id = bs.payment_id
LEFT JOIN `ss_payment_transfer` AS pt ON bs.payment = 'transfer' AND pt.id = bs.payment_id
LEFT JOIN `ss_payment_wallet` AS pw ON bs.payment = 'wallet' AND pw.id = bs.payment_id
LEFT JOIN `ss_payment_direct_billing` AS pdb ON bs.payment = 'direct_billing' AND pdb.id = bs.payment_id)
EOF;

    public function mapToModel(array $data)
    {
        return new Transaction(
            as_int($data["id"]),
            as_int($data["user_id"]),
            as_string($data["username"]),
            $data["payment"],
            $data["payment_id"],
            $data["external_payment_id"],
            as_string($data["service_id"]),
            as_int($data["server_id"]),
            as_float($data["amount"]),
            as_string($data["auth_data"]),
            as_string($data["email"]),
            as_string($data["promo_code"]),
            json_decode($data["extra_data"], true),
            as_string($data["ip"]),
            as_string($data["platform"]),
            as_int($data["income"]),
            as_int($data["cost"]),
            as_int($data["aid"]),
            as_string($data["adminname"]),
            as_string($data["sms_code"]),
            as_string($data["sms_text"]),
            $data["sms_number"],
            (bool) $data["free"],
            $data["timestamp"]
        );
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->transactionsQuery;
    }
}
