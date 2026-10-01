<?php

namespace Tests\Support\Database;

use Config\Database;

// Creates only an isolated test table, never the application's database.
final class AdminTable
{
    public static function create(): void
    {
        $forge = Database::forge('tests');
        $forge->dropTable('admins', true);
        $fields = ['admin_id' => ['type' => 'INTEGER', 'auto_increment' => true]];
        foreach (['first_name' => 50, 'middle_name' => 50, 'last_name' => 50,
            'birthdate' => 10, 'gender' => 20, 'email' => 254, 'contact_number' => 11,
            'address' => 255, 'username' => 50, 'password' => 255] as $field => $length) {
            $fields[$field] = ['type' => 'VARCHAR', 'constraint' => $length, 'null' => $field === 'middle_name'];
        }
        $forge->addField($fields);
        $forge->addKey('admin_id', true);
        $forge->addUniqueKey('email');
        $forge->addUniqueKey('username');
        $forge->createTable('admins');
    }
}
