<?php
namespace App\Repositories;

use App\Models\Transaction;

class TransactionRepository
{
    private $transactionsQuery = <<<EOF
(SELECT bs.id AS `id`,
bs.uid AS `uid`,
u.username AS `username`,
bs.payment AS `payment`,
bs.payment_id AS `payment_id`,
bs.service AS `service`,
bs.server AS `server`,
bs.amount AS `amount`,
bs.auth_data AS `auth_data`,
bs.email AS `email`,
bs.extra_data AS `extra_data`,
CONCAT_WS('', pa.ip, ps.ip, pt.ip, pw.ip, pc.ip) AS `ip`,
CONCAT_WS('', pa.platform, ps.platform, pt.platform, pw.platform, pc.platform) AS `platform`,
CONCAT_WS('', ps.income, pt.income) AS `income`,
CONCAT_WS('', ps.cost, pt.income, pw.cost) AS `cost`,
pa.aid AS `aid`,
u2.username AS `adminname`,
ps.code AS `sms_code`,
ps.text AS `sms_text`,
ps.number AS `sms_number`,
IFNULL(ps.free, IFNULL(pt.free, 0)) AS `free`,
pc.code AS `service_code`,
bs.timestamp AS `timestamp`
FROM `ss_bought_services` AS bs
LEFT JOIN `ss_users` AS u ON u.uid = bs.uid
LEFT JOIN `ss_payment_admin` AS pa ON bs.payment = 'admin' AND pa.id = bs.payment_id
LEFT JOIN `ss_users` AS u2 ON u2.uid = pa.aid
LEFT JOIN `ss_payment_sms` AS ps ON bs.payment = 'sms' AND ps.id = bs.payment_id
LEFT JOIN `ss_payment_transfer` AS pt ON bs.payment = 'transfer' AND pt.id = bs.payment_id
LEFT JOIN `ss_payment_wallet` AS pw ON bs.payment = 'wallet' AND pw.id = bs.payment_id
LEFT JOIN `ss_payment_code` AS pc ON bs.payment = 'service_code' AND pc.id = bs.payment_id)
EOF;

    public function mapToModel(array $data)
    {
        return new Transaction(
            as_int($data['id']),
            as_int($data['uid']),
            $data['username'],
            $data['payment'],
            $data['payment_id'],
            $data['service'],
            as_int($data['server']),
            as_float($data['amount']),
            $data['auth_data'],
            $data['email'],
            json_decode($data['extra_data'], true),
            $data['ip'],
            $data['platform'],
            as_int($data['income']),
            as_int($data['cost']),
            as_int($data['aid']),
            $data['adminname'],
            $data['sms_code'],
            $data['sms_text'],
            $data['sms_number'],
            (bool) $data['free'],
            $data['service_code'],
            $data['timestamp']
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
