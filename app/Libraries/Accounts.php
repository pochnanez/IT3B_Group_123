<?php
namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;

class Accounts
{
    public const DEPARTMENTS = ['Administration', 'IT', 'Dispatch', 'Accounting', 'HR', 'Marketing', 'Sales', 'Customer Service'];
    private const TABLES = ['customer' => 'customers', 'employee' => 'employees', 'admin' => 'admins'];
    private const KEYS = ['customer' => 'Customer_id', 'employee' => 'Employee_Id', 'admin' => 'admin_id'];

    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    public static function rules(bool $employee = false): array
    {
        $rules = [
            'firstname' => 'required|min_length[2]|max_length[50]',
            'middlename' => 'permit_empty|max_length[50]',
            'lastname' => 'required|min_length[2]|max_length[50]',
            'birthdate' => 'required|valid_date[Y-m-d]',
            'gender' => 'required|in_list[Male,Female,Prefer not to say]',
            'email' => 'required|valid_email|max_length[100]',
            'contact' => 'required|regex_match[/^09[0-9]{9}$/]',
            'address' => 'required|max_length[255]',
            'username' => 'required|min_length[4]|max_length[50]|regex_match[/^[A-Za-z0-9_.-]+$/]',
            'password' => 'required|min_length[8]|max_length[72]',
            'confirmPassword' => 'required|matches[password]',
        ];
        if ($employee) {
            $rules['department'] = 'required|in_list[' . implode(',', self::DEPARTMENTS) . ']';
        }
        return $rules;
    }

    public function create(string $type, array $input, bool $initialAdmin = false): int
    {
        $table = self::TABLES[$type] ?? throw new RuntimeException('Invalid account type.');
        $validation = service('validation');
        $validation->reset();
        $validation->setRules(self::rules($type === 'employee'));
        if (! $validation->run($input)) {
            throw new RuntimeException('Please check the account details.');
        }
        if ($input['birthdate'] > date('Y-m-d')) {
            throw new RuntimeException('Birthdate cannot be in the future.');
        }
        if (strlen($input['password']) > 72 || str_contains($input['password'], "\0")) {
            throw new RuntimeException('Password must be at most 72 bytes and contain no null characters.');
        }
        // Serialize all application account creation across the legacy tables.
        // This also prevents two operators from bootstrapping initial admins.
        $lock = null;
        if ($this->db->DBDriver === 'MySQLi') {
            $lock = 'solar-accounts-' . substr(hash('sha256', $this->db->database), 0, 40);
            $result = $this->db->query('SELECT GET_LOCK(?, 5) AS acquired', [$lock])->getRowArray();
            if ((int) ($result['acquired'] ?? 0) !== 1) {
                throw new RuntimeException('Account service is busy. Please try again.');
            }
        }
        try {
            if (! $this->db->transBegin()) {
                throw new RuntimeException('Account could not be created. Please try again.');
            }
            if ($initialAdmin && ($type !== 'admin' || $this->adminExists())) {
                throw new RuntimeException('Initial administrator setup is unavailable because an administrator already exists.');
            }
            foreach (self::TABLES as $existing) {
                if ($this->db->table($existing)->groupStart()->where('LOWER(email)', strtolower($input['email']))
                    ->orWhere('LOWER(username)', strtolower($input['username']))->groupEnd()->countAllResults() > 0) {
                    throw new RuntimeException('That email address or username is already registered.');
                }
            }
            $data = [
                'first_name' => $input['firstname'], 'middle_name' => ($input['middlename'] ?? '') ?: null,
                'last_name' => $input['lastname'], 'birthdate' => $input['birthdate'],
                'gender' => $input['gender'], 'email' => $input['email'],
                'contact_number' => $input['contact'], 'address' => $input['address'],
                'username' => $input['username'], 'password' => password_hash($input['password'], PASSWORD_DEFAULT),
            ];
            if ($type === 'employee') {
                $data['department'] = $input['department'];
            }
            if (! $this->db->table($table)->insert($data)) {
                throw new RuntimeException('Account could not be created. Please try again.');
            }
            $id = (int) $this->db->insertID();
            $registry = new \App\Models\IdentityRegistry($this->db);
            if ($registry->ready()) {
                $userId = $registry->register($type, $id, $input['department'] ?? null);
                $actor = session()->get('account')['user_id'] ?? null;
                if(!$actor || !$this->db->table('users')->where('id',$actor)->countAllResults()) { $actor=null; }
                $this->db->table('audit_logs')->insert(['actor_user_id'=>$actor,'event'=>'account.created','entity'=>'users','entity_id'=>$userId,'summary'=>'Created ' . $type . ' account.','created_at'=>date('Y-m-d H:i:s')]);
            }
            if (! $this->db->transStatus() || ! $this->db->transCommit()) {
                throw new RuntimeException('Account could not be created. Please try again.');
            }
            return $id;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        } finally {
            if ($lock !== null) {
                $this->db->query('SELECT RELEASE_LOCK(?)', [$lock]);
            }
        }
    }

    public function authenticate(string $identifier, string $password): ?array
    {
        $matches = [];
        foreach (self::TABLES as $type => $table) {
            foreach ($this->db->table($table)->groupStart()->where('LOWER(email)', strtolower($identifier))
                ->orWhere('LOWER(username)', strtolower($identifier))->groupEnd()->get()->getResultArray() as $row) {
                $matches[] = $this->identity($type, $row);
            }
        }
        // Legacy ambiguous identifiers fail closed, even if passwords differ.
        $hash = count($matches) === 1 ? $matches[0]['password'] : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $valid = password_verify($password, $hash);
        if (count($matches) !== 1 || ! $valid || ! $matches[0]['active']) {
            return null;
        }
        $account = $matches[0];
        if (password_needs_rehash($account['password'], PASSWORD_DEFAULT)) {
            if (! $this->db->table(self::TABLES[$account['type']])->where(self::KEYS[$account['type']], $account['id'])
                ->update(['password' => password_hash($password, PASSWORD_DEFAULT)])) {
                throw new RuntimeException('Unable to refresh account credentials.');
            }
        }
        unset($account['password']);
        return $account;
    }

    public function find(string $type, int $id): ?array
    {
        if (! isset(self::TABLES[$type])) {
            return null;
        }
        $row = $this->db->table(self::TABLES[$type])->where(self::KEYS[$type], $id)->get()->getRowArray();
        if (! $row) {
            return null;
        }
        $account = $this->identity($type, $row);
        if (!$account['active']) { return null; }
        unset($account['password']);
        return $account;
    }

    public function adminExists(): bool
    {
        return $this->db->table('admins')->countAllResults() > 0;
    }

    public function profile(string $type, int $id): array
    {
        if (!isset(self::TABLES[$type])) { throw new \RuntimeException('Account not found.'); }
        $row=$this->db->table(self::TABLES[$type])->where(self::KEYS[$type],$id)->get()->getRowArray();
        if (!$row) { throw new \RuntimeException('Account not found.'); }
        $row=array_change_key_case($row,CASE_LOWER); unset($row['password']); return $row;
    }

    public function updateProfile(array $account, array $input, ?int $performedBy = null): void
    {
        $rules=self::rules(); unset($rules['password'],$rules['confirmPassword']);
        $validator=service('validation'); $validator->reset(); $validator->setRules($rules);
        if (!$validator->run($input) || ($input['birthdate']??'')>date('Y-m-d')) { throw new \RuntimeException('Please check all profile fields, including birthdate, email and phone.'); }
        (new Management($this->db))->atomic('accounts',function() use($account,$input,$performedBy) {
            foreach(self::TABLES as $type=>$table) {
                $query=$this->db->table($table)->groupStart()->where('LOWER(email)',strtolower($input['email']))->orWhere('LOWER(username)',strtolower($input['username']))->groupEnd();
                if ($type===$account['type']) { $query->where(self::KEYS[$type].' !=',$account['id']); }
                if($query->countAllResults()) { throw new \RuntimeException('That email address or username is already registered.'); }
            }
            $fields=['firstname'=>'first_name','middlename'=>'middle_name','lastname'=>'last_name','birthdate'=>'birthdate','gender'=>'gender','email'=>'email','contact'=>'contact_number','address'=>'address','username'=>'username']; $data=[];
            foreach($fields as $form=>$column) { $data[$column]=$input[$form]??''; }
            if (!$this->db->table(self::TABLES[$account['type']])->where(self::KEYS[$account['type']],$account['id'])->update($data)) { throw new \RuntimeException('Profile could not be saved.'); }
            if(isset($account['user_id']) && $account['user_id']) { (new Management($this->db))->audit($performedBy??$account['user_id'],'profile.updated','users',$account['user_id'],$performedBy?'Updated managed profile.':'Updated own profile.'); }
        });
    }

    public function changePassword(array $account, string $current, string $password): void
    {
        if(mb_strlen($password)<8 || strlen($password)>72 || str_contains($password,"\0")) { throw new \RuntimeException('Use a password of at least 8 characters and at most 72 bytes.'); }
        (new Management($this->db))->atomic('accounts',function() use($account,$current,$password) {
            $row=$this->db->table(self::TABLES[$account['type']])->where(self::KEYS[$account['type']],$account['id'])->get()->getRowArray();
            $row=array_change_key_case($row??[],CASE_LOWER);
            if(!password_verify($current,$row['password']??'')) { throw new \RuntimeException('Current password is incorrect.'); }
            if(!$this->db->table(self::TABLES[$account['type']])->where(self::KEYS[$account['type']],$account['id'])->update(['password'=>password_hash($password,PASSWORD_DEFAULT)])) { throw new \RuntimeException('Password could not be changed.'); }
            if(!empty($account['user_id'])) {
                if(!$this->db->table('users')->where('id',$account['user_id'])->set('session_version','session_version + 1',false)->update()) { throw new \RuntimeException('Unable to invalidate previous sessions.'); }
                (new Management($this->db))->audit($account['user_id'],'password.changed','users',$account['user_id'],'Changed own password and invalidated prior sessions.');
            }
        });
    }

    private function identity(string $type, array $row): array
    {
        $row = array_change_key_case($row, CASE_LOWER);
        $role = $type;
        if ($type === 'employee' && in_array((int) $row['employee_id'], config('AccountAccess')->hrEmployeeIds, true)) {
            $role = 'hr';
        }
        $id=(int)$row[strtolower(self::KEYS[$type])]; $registry=new \App\Models\IdentityRegistry($this->db);
        $registered=$registry->ready() ? $registry->lookup($type,$id) : null;
        return ['type' => $type, 'id' => $id, 'user_id'=>(int)($registered['id']??0),'session_version'=>(int)($registered['session_version']??0),
            'active'=>$registry->ready() ? (bool)($registered['active']??false) : true,
            'name' => $row['first_name'] . ' ' . $row['last_name'], 'role' => $registered['role']??$role, 'password' => $row['password']];
    }
}
