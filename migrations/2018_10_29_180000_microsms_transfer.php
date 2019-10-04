<?php

use App\Install\Migration;

class MicrosmsTransfer extends Migration
{
    public function up()
    {
        $this->db->query(
            <<<EOF
UPDATE `ss_transaction_services`
SET `transfer` = 1, `data` = CONCAT(SUBSTRING(`data`, 1, LENGTH(`data`) - 1), ',"shop_id": "","hash": ""}')
WHERE `id` = 'microsms';
EOF
        );
    }
}
