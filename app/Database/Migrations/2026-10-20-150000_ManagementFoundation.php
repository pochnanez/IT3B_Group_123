<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ManagementFoundation extends Migration
{
    private function text(int $length = 255, bool $null = false): array { return ['type' => 'VARCHAR', 'constraint' => $length, 'null' => $null]; }
    private function ref(bool $null = false): array { return ['type' => 'INT', 'unsigned' => true, 'null' => $null]; }
    private function table(string $name, array $fields, array $references = [], array $unique = []): void
    {
        $this->forge->addField(['id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true], ...$fields,
            'created_at' => ['type' => 'DATETIME']]);
        $this->forge->addKey('id', true);
        foreach ($references as $field => $target) {
            if(!in_array($field,$unique,true)) { $this->forge->addKey($field); }
            $this->forge->addForeignKey($field, $target, 'id', 'RESTRICT', 'RESTRICT');
        }
        foreach ($unique as $columns) { $this->forge->addUniqueKey($columns); }
        $this->forge->createTable($name);
    }

    public function up()
    {
        $targets = ['roles','departments','users','products','services','bookings','technician_assignments','attendance_logs','service_reports','notifications','announcements','payments','support_tickets','audit_logs','equipment_issues','settings'];
        foreach ($targets as $table) {
            if ($this->db->tableExists($table)) { throw new \RuntimeException('Management table already exists: ' . $table . '. Inspect schema before migrating.'); }
        }
        foreach (['customers'=>'Customer_id','employees'=>'Employee_Id','admins'=>'admin_id'] as $table => $key) {
            if ($this->db->tableExists($table)) { continue; }
            $fields = [$key => ['type'=>'INT','auto_increment'=>true]];
            foreach (['First_Name'=>50,'Middle_Name'=>50,'Last_Name'=>50,'Email'=>100,'Contact_Number'=>15,'Address'=>255,'Username'=>50,'Password'=>255,'Gender'=>30] as $field=>$length) {
                $fields[$field] = $this->text($length, $field === 'Middle_Name');
            }
            $fields['Birthdate'] = ['type'=>'DATE'];
            if ($table === 'employees') { $fields['Department'] = $this->text(40); }
            $this->forge->addField($fields); $this->forge->addKey($key,true);
            $this->forge->addUniqueKey('Email'); $this->forge->addUniqueKey('Username'); $this->forge->createTable($table);
        }
        $long = ['type'=>'TEXT'];
        $date = ['type'=>'DATETIME','null'=>true];
        $money = ['type'=>'DECIMAL','constraint'=>'12,2','null'=>true];
        $this->table('roles', ['slug'=>$this->text(30),'name'=>$this->text(50)], [], ['slug']);
        $this->table('departments', ['name'=>$this->text(50)], [], ['name']);
        $this->table('users', ['account_type'=>$this->text(15),'legacy_id'=>$this->ref(),'role_id'=>$this->ref(),'department_id'=>$this->ref(true),'active'=>['type'=>'INT','default'=>1],'session_version'=>['type'=>'INT','default'=>1]], ['role_id'=>'roles','department_id'=>'departments'], [['account_type','legacy_id']]);
        $this->table('products', ['name'=>$this->text(100),'category'=>$this->text(40),'description'=>$long,'price'=>$money,'stock'=>['type'=>'INT','null'=>true],'active'=>['type'=>'INT','default'=>1]]);
        $this->table('services', ['name'=>$this->text(100),'description'=>$long,'price'=>$money,'active'=>['type'=>'INT','default'=>1]], [], ['name']);
        $this->table('bookings', ['customer_user_id'=>$this->ref(),'service_id'=>$this->ref(),'status'=>$this->text(20),'preferred_at'=>['type'=>'DATETIME'],'scheduled_at'=>$date,'scheduled_end'=>$date,'address'=>$this->text(),'notes'=>$long,'updated_at'=>['type'=>'DATETIME']], ['customer_user_id'=>'users','service_id'=>'services']);
        $this->table('technician_assignments', ['booking_id'=>$this->ref(),'technician_user_id'=>$this->ref(),'assigned_by'=>$this->ref(),'status'=>$this->text(20)], ['booking_id'=>'bookings','technician_user_id'=>'users','assigned_by'=>'users'], ['booking_id']);
        $this->table('attendance_logs', ['assignment_id'=>$this->ref(),'technician_user_id'=>$this->ref(),'checked_in_at'=>['type'=>'DATETIME'],'checked_out_at'=>$date,'latitude'=>['type'=>'DECIMAL','constraint'=>'10,7','null'=>true],'longitude'=>['type'=>'DECIMAL','constraint'=>'10,7','null'=>true],'manual_reason'=>$this->text(500,true)], ['assignment_id'=>'technician_assignments','technician_user_id'=>'users'], ['assignment_id']);
        $this->table('service_reports', ['assignment_id'=>$this->ref(),'technician_user_id'=>$this->ref(),'report'=>$long], ['assignment_id'=>'technician_assignments','technician_user_id'=>'users'], ['assignment_id']);
        $this->table('equipment_issues', ['assignment_id'=>$this->ref(),'reported_by'=>$this->ref(),'description'=>$long,'status'=>$this->text(20)], ['assignment_id'=>'technician_assignments','reported_by'=>'users']);
        $this->table('notifications', ['user_id'=>$this->ref(),'booking_id'=>$this->ref(true),'message'=>$this->text(500),'read_at'=>$date], ['user_id'=>'users','booking_id'=>'bookings']);
        $this->table('announcements', ['author_user_id'=>$this->ref(),'title'=>$this->text(150),'body'=>$long,'category'=>$this->text(20)], ['author_user_id'=>'users']);
        $this->table('payments', ['booking_id'=>$this->ref(),'recorded_by'=>$this->ref(),'amount'=>['type'=>'DECIMAL','constraint'=>'12,2'],'reference'=>$this->text(100),'paid_at'=>['type'=>'DATETIME']], ['booking_id'=>'bookings','recorded_by'=>'users'], ['reference']);
        $this->table('support_tickets', ['user_id'=>$this->ref(),'subject'=>$this->text(150),'message'=>$long,'status'=>$this->text(20),'response'=>['type'=>'TEXT','null'=>true],'responded_by'=>$this->ref(true)], ['user_id'=>'users','responded_by'=>'users']);
        $this->table('audit_logs', ['actor_user_id'=>$this->ref(true),'event'=>$this->text(80),'entity'=>$this->text(60),'entity_id'=>$this->ref(true),'summary'=>$this->text(500)], ['actor_user_id'=>'users']);
        $this->table('settings', ['setting_key'=>$this->text(60),'value'=>$long], [], ['setting_key']);
        $now = date('Y-m-d H:i:s'); $roles=[]; $departments=[];
        foreach (['customer','admin','hr','dispatcher','technician','employee'] as $role) {
            $this->db->table('roles')->insert(['slug'=>$role,'name'=>ucfirst($role),'created_at'=>$now]); $roles[$role]=(int)$this->db->insertID();
        }
        foreach (\App\Libraries\Accounts::DEPARTMENTS as $name) {
            $this->db->table('departments')->insert(['name'=>$name,'created_at'=>$now]); $departments[$name]=(int)$this->db->insertID();
        }
        foreach (config('PublicSite')->services as $service) {
            $this->db->table('services')->insert(['name'=>$service['name'],'description'=>$service['description'].' '.$service['details'],'active'=>1,'created_at'=>$now]);
        }
        foreach (['customer'=>['customers','Customer_id'],'employee'=>['employees','Employee_Id'],'admin'=>['admins','admin_id']] as $type => [$table,$key]) {
            $query=$this->db->table($table)->select($key . ($type==='employee' ? ', Department' : ''));
            foreach ($query->get()->getResultArray() as $row) {
                $row=array_change_key_case($row,CASE_LOWER); $legacy=(int)$row[strtolower($key)];
                $role=$type==='employee' && in_array($legacy, config('AccountAccess')->hrEmployeeIds,true) ? 'hr' : $type;
                $this->db->table('users')->insert(['account_type'=>$type,'legacy_id'=>$legacy,'role_id'=>$roles[$role],'department_id'=>$departments[$row['department']??'']??null,'active'=>1,'created_at'=>$now]);
            }
        }
    }

    public function down() { throw new \RuntimeException('Destructive rollback is disabled. Preserve management and account records.'); }
}
