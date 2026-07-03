<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCheckoutFeesToTransaction extends Migration
{
    public function up()
    {
        $fields = [
            'biaya_admin' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'kupon_code' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'diskon_kupon' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'cashback' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'grand_total' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
        ];

        $this->forge->addColumn('transaction', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('transaction', 'biaya_admin');
        $this->forge->dropColumn('transaction', 'kupon_code');
        $this->forge->dropColumn('transaction', 'diskon_kupon');
        $this->forge->dropColumn('transaction', 'cashback');
        $this->forge->dropColumn('transaction', 'grand_total');
    }
}
