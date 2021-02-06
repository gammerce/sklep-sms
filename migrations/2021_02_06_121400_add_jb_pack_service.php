<?php

use App\Install\Migration;

class AddJBPackService extends Migration
{
    public function up()
    {
        $this->db->query(
            <<<EOF
INSERT INTO `ss_services` (`id`, `name`, `short_description`, `description`, `types`, `tag`, `module`, `groups`, `flags`, `order`, `data`)
VALUES
  ('jb_pack', 'JB Pack', '', '', 0, 'JB Pack', 'other', '', '', 10, '')
EOF
        );
    }
}
