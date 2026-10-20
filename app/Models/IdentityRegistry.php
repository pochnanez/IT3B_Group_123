<?php
namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class IdentityRegistry
{
    public function __construct(private ?BaseConnection $db = null) { $this->db ??= db_connect(); }
    public function ready(): bool { return $this->db->tableExists('users'); }
    public function lookup(string $type, int $id): ?array
    {
        return $this->db->table('users u')->select('u.*, r.slug AS role')->join('roles r','r.id=u.role_id')
            ->where('account_type',$type)->where('legacy_id',$id)->get()->getRowArray();
    }
    public function register(string $type, int $id, ?string $department = null): int
    {
        $role=$type==='employee' && in_array($id,config('AccountAccess')->hrEmployeeIds,true) ? 'hr' : $type;
        $roleRow=$this->db->table('roles')->where('slug',$role)->get()->getRowArray();
        $dept=$department ? $this->db->table('departments')->where('name',$department)->get()->getRowArray() : null;
        if (!$roleRow || !$this->db->table('users')->insert(['account_type'=>$type,'legacy_id'=>$id,'role_id'=>$roleRow['id'],'department_id'=>$dept['id']??null,'active'=>1,'created_at'=>date('Y-m-d H:i:s')])) {
            throw new \RuntimeException('Unable to register the account identity.');
        }
        return (int)$this->db->insertID();
    }
}
