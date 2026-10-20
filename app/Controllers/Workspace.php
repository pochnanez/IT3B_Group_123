<?php
namespace App\Controllers;

use App\Libraries\Accounts;
use App\Libraries\Management;

class Workspace extends BaseController
{
    private function account(): array { return session()->get('account'); }
    private function model(): Management { return new Management(); }
    private function page(string $title,string $section,array $data=[])
    {
        if(!$this->model()->ready()) { return $this->response->setStatusCode(503)->setBody('Management setup is pending. Run the reviewed database migrations before using this module.'); }
        return view('layouts/workspace',['title'=>$title,'section'=>$section,'account'=>$this->account(),...$data]);
    }
    private function input(): array
    {
        $data=[];
        foreach($this->request->getPost() as $key=>$value) { $data[$key]=is_string($value)?(str_contains(strtolower($key),'password')?$value:trim($value)):''; }
        return $data;
    }
    private function change(callable $operation,string $destination,string $success='Changes saved.')
    {
        try { $operation($this->input()); return redirect()->to($destination)->with('success',$success); }
        catch(\Throwable $e) { return redirect()->to($destination)->with('error',get_class($e)===\RuntimeException::class ? $e->getMessage() : 'Unable to save changes. Please try again later.'); }
    }
    public function index()
    {
        $m=$this->model(); if(!$m->ready()) { return view('account',['account'=>$this->account()]); }
        $a=$this->account(); $rows=in_array($a['role'],['customer','admin','dispatcher','technician'],true)?$m->bookings($a):[];
        $counts=[]; foreach($rows as $row) { $counts[$row['status']]=($counts[$row['status']]??0)+1; }
        $announcements=$a['type']!=='customer'?$m->db->table('announcements')->orderBy('id','DESC')->get(20)->getResultArray():[];
        return $this->page($a['role']==='customer'?'Homepage':ucfirst($a['role']).' dashboard','dashboard',['bookings'=>$rows,'counts'=>$counts,'announcements'=>$announcements]);
    }
    public function bookings() { return $this->page('Service bookings','bookings',['bookings'=>$this->model()->bookings($this->account())]); }
    public function newBooking()
    {
        return $this->page('Request a service','new_booking',['services'=>$this->model()->db->table('services')->where('active',1)->orderBy('name')->get()->getResultArray(),'profile'=>(new Accounts())->profile($this->account()['type'],$this->account()['id'])]);
    }
    public function requestBooking()
    {
        $id=0; $result=$this->change(function($input) use(&$id) { $id=$this->model()->request($this->account(),$input); },'/bookings/new','Service request submitted. Your preferred time is subject to review.');
        return $id ? redirect()->to('/bookings/'.$id)->with('success','Service request submitted. Your preferred time is subject to review.') : $result;
    }
    public function booking(int $id)
    {
        try {
            $m=$this->model(); $b=$m->booking($this->account(),$id);
            $attendance=$b['assignment_id']?$m->db->table('attendance_logs')->where('assignment_id',$b['assignment_id'])->get()->getRowArray():null;
            $report=$b['assignment_id']?$m->db->table('service_reports')->where('assignment_id',$b['assignment_id'])->get()->getRowArray():null;
            $techs=in_array($this->account()['role'],['admin','dispatcher'],true)?$this->usersWithRole('technician'):[];
            $customer=$this->user((int)$b['customer_user_id']);
            $technician=$b['technician_user_id']?$this->user((int)$b['technician_user_id']):null;
            return $this->page('Booking #'.$id,'booking',['booking'=>$b,'attendance'=>$attendance,'report'=>$report,'technicians'=>$techs,'customer'=>$customer,'technician'=>$technician,'payments'=>$m->db->table('payments')->where('booking_id',$id)->orderBy('id','DESC')->get()->getResultArray()]);
        } catch(\Throwable $e) { return $this->response->setStatusCode(404)->setBody('Booking unavailable or access denied.'); }
    }
    public function changeBooking(int $id) { return $this->change(fn($input)=>$this->model()->changeBooking($this->account(),$id,$input),'/bookings/'.$id); }
    public function assign(int $id) { return $this->change(fn($input)=>$this->model()->assign($this->account(),$id,$input),'/bookings/'.$id,'Technician assigned and schedule confirmed.'); }
    private function assignmentChange(int $id,string $method)
    {
        $row=$this->model()->db->table('technician_assignments')->where('id',$id)->where('technician_user_id',$this->account()['user_id'])->get()->getRowArray();
        if(!$row) { return $this->response->setStatusCode(404)->setBody('Assignment unavailable.'); }
        return $this->change(fn($input)=>$this->model()->$method($this->account(),$id,$input),'/bookings/'.$row['booking_id']);
    }
    public function attendance(int $id) { return $this->assignmentChange($id,'attendance'); }
    public function report(int $id) { return $this->assignmentChange($id,'report'); }
    public function issue(int $id) { return $this->assignmentChange($id,'issue'); }
    public function notifications()
    {
        return $this->page('Notifications','notifications',['notifications'=>$this->model()->db->table('notifications')->where('user_id',$this->account()['user_id'])->orderBy('id','DESC')->get()->getResultArray()]);
    }
    public function readNotifications()
    {
        return $this->change(function() {
            if(!$this->model()->db->table('notifications')->where('user_id',$this->account()['user_id'])->where('read_at',null)->update(['read_at'=>$this->model()->now()])) { throw new \RuntimeException('Unable to update notifications.'); }
        },'/notifications');
    }
    public function profile()
    {
        return $this->page('Your profile','profile',['profile'=>(new Accounts())->profile($this->account()['type'],$this->account()['id']),'target'=>null]);
    }
    public function saveProfile() { return $this->change(fn($input)=>(new Accounts())->updateProfile($this->account(),$input),'/profile','Profile saved.'); }
    public function password()
    {
        $input=$this->input();
        if(($input['new_password']??'')!==($input['confirm_password']??'')) { return redirect()->to('/profile')->with('error','Password confirmation does not match.'); }
        try { (new Accounts())->changePassword($this->account(),$input['current_password']??'',$input['new_password']??''); }
        catch(\Throwable $e) { return redirect()->to('/profile')->with('error',get_class($e)===\RuntimeException::class?$e->getMessage():'Unable to change password.'); }
        session()->remove(array_keys($_SESSION)); session()->destroy();
        return redirect()->to('/login');
    }
    public function support()
    {
        $q=$this->model()->db->table('support_tickets'); if($this->account()['role']!=='admin') { $q->where('user_id',$this->account()['user_id']); }
        return $this->page('Support concerns','support',['tickets'=>$q->orderBy('id','DESC')->get()->getResultArray()]);
    }
    public function createTicket() { return $this->change(fn($input)=>$this->model()->ticket($this->account(),$input),'/support','Your support concern was saved.'); }
    public function respond(int $id) { return $this->change(fn($input)=>$this->model()->respond($this->account(),$id,$input),'/support'); }
    public function receipt(int $id)
    {
        $p=$this->model()->db->table('payments')->where('id',$id)->get()->getRowArray(); if(!$p) { return $this->response->setStatusCode(404)->setBody('Receipt not found.'); }
        try { $b=$this->model()->booking($this->account(),(int)$p['booking_id']); if(!in_array($this->account()['role'],['admin','customer'],true)) { throw new \RuntimeException(); } }
        catch(\Throwable $e) { return $this->response->setStatusCode(404)->setBody('Receipt not found.'); }
        return $this->page('Payment receipt #'.$id,'receipt',['payment'=>$p,'booking'=>$b]);
    }
    public function payments()
    {
        $q=$this->model()->db->table('payments p')->select('p.*, b.customer_user_id, s.name AS service_name')->join('bookings b','b.id=p.booking_id')->join('services s','s.id=b.service_id');
        if($this->account()['role']==='customer') { $q->where('b.customer_user_id',$this->account()['user_id']); }
        return $this->page('Payment records','payments',['payments'=>$q->orderBy('p.id','DESC')->get()->getResultArray()]);
    }
    public function recordPayment(int $id) { return $this->change(fn($input)=>$this->model()->payment($this->account(),$id,$input),'/bookings/'.$id,'Payment record saved.'); }
    private function user(int $id): ?array
    {
        $row=$this->model()->db->table('users u')->select('u.*, r.slug AS role, d.name AS department')->join('roles r','r.id=u.role_id')->join('departments d','d.id=u.department_id','left')->where('u.id',$id)->get()->getRowArray();
        if(!$row) { return null; }
        $profile=(new Accounts())->profile($row['account_type'],(int)$row['legacy_id']); return $row+['profile'=>$profile,'display_name'=>$profile['first_name'].' '.$profile['last_name']];
    }
    private function usersWithRole(string $role): array
    {
        $ids=$this->model()->db->table('users u')->select('u.id')->join('roles r','r.id=u.role_id')->where('r.slug',$role)->where('u.active',1)->get()->getResultArray();
        return array_values(array_filter(array_map(fn($row)=>$this->user((int)$row['id']),$ids)));
    }
    public function users()
    {
        $q=$this->model()->db->table('users'); if($this->account()['role']==='hr') { $q->where('account_type','employee'); }
        $rows=$q->orderBy('id','DESC')->get()->getResultArray(); $users=[]; foreach($rows as $row) { $user=$this->user((int)$row['id']); if($user) { $users[]=$user; } }
        return $this->page($this->account()['role']==='hr'?'Employee management':'User management','users',['users'=>$users]);
    }
    public function editUser(int $id)
    {
        $target=$this->user($id); if(!$target || ($this->account()['role']==='hr' && $target['account_type']!=='employee')) { return $this->response->setStatusCode(404)->setBody('Account unavailable.'); }
        return $this->page('Manage user #'.$id,'profile',['profile'=>$target['profile'],'target'=>$target]);
    }
    public function saveUser(int $id) { return $this->change(fn($input)=>$this->model()->saveUser($this->account(),$id,$input),'/users/'.$id); }
    public function announcements()
    { return $this->page('Company announcements','announcements',['announcements'=>$this->model()->db->table('announcements')->orderBy('id','DESC')->get()->getResultArray()]); }
    public function publish() { return $this->change(fn($input)=>$this->model()->announcement($this->account(),$input),'/announcements','Announcement published.'); }
    public function catalog(string $kind)
    {
        if(!in_array($kind,['products','services'],true)) { return $this->response->setStatusCode(404); }
        return $this->page(ucfirst($kind).' management','catalog',['kind'=>$kind,'items'=>$this->model()->db->table($kind)->orderBy('id','DESC')->get()->getResultArray()]);
    }
    public function saveCatalog(string $kind,int $id=0) { return $this->change(fn($input)=>$this->model()->catalog($this->account(),$kind,$id,$input),'/manage/'.$kind); }
    public function settings()
    {
        $settings=[]; foreach($this->model()->db->table('settings')->get()->getResultArray() as $row) { $settings[$row['setting_key']]=$row['value']; }
        return $this->page('System settings','settings',['settings'=>$settings]);
    }
    public function saveSettings() { return $this->change(fn($input)=>$this->model()->settings($this->account(),$input),'/settings'); }
    public function reports()
    {
        $from=$this->request->getGet('from'); $to=$this->request->getGet('to'); $status=$this->request->getGet('status');
        $from=is_string($from)?$from:''; $to=is_string($to)?$to:''; $status=is_string($status)?$status:'';
        $validDate=static function(string $value): bool { $date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value); return $date && $date->format('Y-m-d')===$value; };
        if(($from!=='' && !$validDate($from)) || ($to!=='' && !$validDate($to)) || ($from && $to && $from>$to) || !in_array($status,['','requested','scheduled','in_progress','completed','cancelled'],true)) { return $this->response->setStatusCode(400)->setBody('Choose a valid date range and booking status.'); }
        $q=$this->model()->db->table('bookings b')->select('b.*, s.name AS service_name')->join('services s','s.id=b.service_id');
        if($from) { $q->where('b.created_at >=',$from.' 00:00:00'); } if($to) { $q->where('b.created_at <=',$to.' 23:59:59'); } if($status) { $q->where('b.status',$status); }
        $bookings=$q->orderBy('b.created_at','DESC')->get()->getResultArray(); $counts=[]; $byService=[]; $byMonth=[]; $ids=array_column($bookings,'id');
        foreach($bookings as $b) { $counts[$b['status']]=($counts[$b['status']]??0)+1; $byService[$b['service_name']]=($byService[$b['service_name']]??0)+1; $month=substr($b['created_at'],0,7); $byMonth[$month]=($byMonth[$month]??0)+1; }
        $payments=$this->model()->db->table('payments')->whereIn('booking_id',$ids?:[0])->get()->getResultArray();
        return $this->page('Operations reports','reports',['bookings'=>$bookings,'counts'=>$counts,'byService'=>$byService,'byMonth'=>$byMonth,'paymentTotal'=>array_sum(array_map(fn($p)=>(float)$p['amount'],$payments)),'from'=>$from,'to'=>$to,'status'=>$status]);
    }
    public function records()
    {
        $m=$this->model();
        return $this->page('Attendance, reports & audit','records',['attendance'=>$m->db->table('attendance_logs')->orderBy('id','DESC')->get()->getResultArray(),'reports'=>$m->db->table('service_reports')->orderBy('id','DESC')->get()->getResultArray(),'issues'=>$m->db->table('equipment_issues')->orderBy('id','DESC')->get()->getResultArray(),'logs'=>$m->db->table('audit_logs')->orderBy('id','DESC')->get(200)->getResultArray()]);
    }
    public function closeIssue(int $id)
    {
        return $this->change(function() use($id) {
            $m=$this->model(); $who=$m->permit($this->account(),['admin']);
            $m->atomic('management',function() use($m,$who,$id) { if(!$m->db->table('equipment_issues')->where('id',$id)->countAllResults()) { throw new \RuntimeException('Issue not found.'); } $m->update('equipment_issues',$id,['status'=>'resolved']); $m->audit($who['id'],'equipment.resolved','equipment_issues',$id,'Resolved equipment issue.'); });
        },'/records');
    }
}
