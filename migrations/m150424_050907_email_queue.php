<?php

use yii\db\Schema;
use yii\db\Migration;

class m150424_050907_email_queue extends Migration
{
    public function up()
    {
        $driver = $this->db->driverName;
        $tableOptions = "";
        if ($driver == 'mysql') {
            // MySql table options
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }
        // table email_queue
        $this->createTable(
            '{{%email_queue}}',
            [
                'id' => Schema::TYPE_PK,
                'user_id' => Schema::TYPE_INTEGER . ' NOT NULL',
                'category' => Schema::TYPE_STRING . '(255) NULL',
                'from_name' => Schema::TYPE_STRING . '(255) NULL',
                'from_address' => Schema::TYPE_STRING . '(255) NULL',
                'to_name' => Schema::TYPE_STRING . '(255) NULL',
                'to_address' => Schema::TYPE_STRING . '(255) NOT NULL',
                'subject' => Schema::TYPE_STRING . '(255) NOT NULL',
                'body' => Schema::TYPE_TEXT . '',
                'alternative_body' => Schema::TYPE_TEXT . '',
                'headers' => Schema::TYPE_TEXT . '',
                'attachments' => Schema::TYPE_TEXT . '',
                'max_attempts' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 3',
                'attempt' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 0',
                'priority' => Schema::TYPE_INTEGER . ' NOT NULL DEFAULT 5',
                'status' => Schema::TYPE_INTEGER . ' NOT NULL',
                'sent_time' => Schema::TYPE_DATETIME . ' NULL',
                'create_time' => Schema::TYPE_DATETIME . ' NOT NULL',
                'update_time' => Schema::TYPE_DATETIME . ' NULL',
            ],

            $tableOptions
        );


        // Indexes
        $this->addForeignKey('FK_email_queue_user_id_user_id', '{{%email_queue}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('status', '{{%email_queue}}', 'status');
    }

    public function down()
    {
        echo "Reverting m150424_050907_email_queue.\n";
        echo "Dropping table {{%email_queue}}.\n";
        $this->dropTable('{{%email_queue}}');
        $this->execute($this->getSettingSql());
        return true;
    }

}
