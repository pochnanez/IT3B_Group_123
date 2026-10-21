<?php
namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;

class Management
{
    public function __construct(public ?BaseConnection $db = null) { $this->db ??= db_connect(); }
    public function ready(): bool { return $this->db->tableExists('bookings') && $this->db->tableExists('users'); }
    public function now(): string { return (new \DateTimeImmutable('now',new \DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s'); }
    public function atomic(string $name, callable $operation): mixed
    {
        $lock=null;
        if($this->db->DBDriver==='MySQLi') {
            $lock='solar-'.$name.'-'.substr(hash('sha256',$this->db->database),0,40);
            if((int)$this->db->query('SELECT GET_LOCK(?, 5) AS acquired',[$lock])->getRowArray()['acquired']!==1) { throw new RuntimeException('The system is busy. Please try again.'); }
        }
        try {
            if(!$this->db->transBegin()) { throw new RuntimeException('Unable to save changes.'); }
            $result=$operation();
            if(!$this->db->transStatus() || !$this->db->transCommit()) { throw new RuntimeException('Unable to save changes.'); }
            return $result;
        } catch(\Throwable $e) { $this->db->transRollback(); throw $e; }
        finally { if($lock!==null) { $this->db->query('SELECT RELEASE_LOCK(?)',[$lock]); } }
    }
    public function insert(string $table,array $data): int
    {
        if(!$this->db->table($table)->insert($data+['created_at'=>$this->now()])) { throw new RuntimeException('Unable to save this record.'); }
        return (int)$this->db->insertID();
    }
    public function update(string $table,int $id,array $data): void
    {
        if(!$this->db->table($table)->where('id',$id)->update($data)) { throw new RuntimeException('Unable to save changes.'); }
    }
    public function audit(?int $actor,string $event,string $entity,int $id,string $summary): void
    { $this->insert('audit_logs',['actor_user_id'=>$actor,'event'=>$event,'entity'=>$entity,'entity_id'=>$id,'summary'=>$summary]); }
    public function notify(int $user,string $message,?int $booking=null): void
    { $this->insert('notifications',['user_id'=>$user,'booking_id'=>$booking,'message'=>$message]); }
    public function permit(array $actor,array $roles): array
    {
        $row=$this->db->table('users u')->select('u.*, r.slug AS role')->join('roles r','r.id=u.role_id')->where('u.id',(int)($actor['user_id']??0))->where('u.active',1)->get()->getRowArray();
        if(!$row || !in_array($row['role'],$roles,true)) { throw new RuntimeException('You do not have permission for this action.'); }
        return $row;
    }
    public function validate(array $input,array $rules): void
    {
        $v=service('validation'); $v->reset(); $v->setRules($rules);
        if(!$v->run($input)) { throw new RuntimeException(implode(' ', $v->getErrors())); }
    }
    public function future(string $value): string
    {
        $dt=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$value,new \DateTimeZone('Asia/Manila'));
        if(!$dt || $dt->format('Y-m-d\TH:i')!==$value || $dt->getTimestamp()<=time()) { throw new RuntimeException('Choose a valid future date and time (Philippine time).'); }
        return $dt->format('Y-m-d H:i:s');
    }
    public function booking(array $actor,int $id): array
    {
        $who=$this->permit($actor,['customer','admin','dispatcher','technician']);
        $row=$this->db->table('bookings b')->select('b.*, s.name AS service_name, a.id AS assignment_id, a.technician_user_id, a.status AS assignment_status')
            ->join('services s','s.id=b.service_id')->join('technician_assignments a','a.booking_id=b.id','left')->where('b.id',$id)->get()->getRowArray();
        if(!$row || ($who['role']==='customer' && (int)$row['customer_user_id']!==(int)$who['id']) || ($who['role']==='technician' && (int)($row['technician_user_id']??0)!==(int)$who['id'])) { throw new RuntimeException('Booking not found or access denied.'); }
        return $row;
    }
    public function bookings(array $actor): array
    {
        $who=$this->permit($actor,['customer','admin','dispatcher','technician']);
        $q=$this->db->table('bookings b')->select('b.*, s.name AS service_name, a.id AS assignment_id, a.technician_user_id, a.status AS assignment_status')->join('services s','s.id=b.service_id')->join('technician_assignments a','a.booking_id=b.id','left');
        if($who['role']==='customer') { $q->where('b.customer_user_id',$who['id']); }
        if($who['role']==='technician') { $q->where('a.technician_user_id',$who['id']); }
        return $q->orderBy('b.id','DESC')->get()->getResultArray();
    }
    public function request(array $actor,array $input): int
    {
        $who=$this->permit($actor,['customer']);
        $this->validate($input,['service_id'=>'required|is_natural_no_zero','preferred_at'=>'required','address'=>'required|max_length[255]','notes'=>'permit_empty|max_length[3000]']);
        $at=$this->future($input['preferred_at']);
        return $this->atomic('management',function() use($who,$input,$at) {
            if(!$this->db->table('services')->where('id',$input['service_id'])->where('active',1)->countAllResults()) { throw new RuntimeException('This service is unavailable.'); }
            $id=$this->insert('bookings',['customer_user_id'=>$who['id'],'service_id'=>(int)$input['service_id'],'status'=>'requested','preferred_at'=>$at,'address'=>$input['address'],'notes'=>$input['notes']??'','updated_at'=>$this->now()]);
            $this->notify($who['id'],'Booking #'.$id.' received. Your preferred time is subject to review.',$id);
            $this->audit($who['id'],'booking.requested','bookings',$id,'Customer submitted a service request.'); return $id;
        });
    }
    public function changeBooking(array $actor,int $id,array $input): void
    {
        $this->atomic('management',function() use($actor,$id,$input) {
            $who=$this->permit($actor,['customer','admin','dispatcher']); $b=$this->booking($actor,$id);
            if(!in_array($b['status'],['requested','scheduled'],true)) { throw new RuntimeException('This booking can no longer be changed.'); }
            $action=$input['action']??'';
            if(!in_array($action,['cancel','reschedule'],true)) { throw new RuntimeException('Invalid booking action.'); }
            $data=['status'=>$action==='cancel'?'cancelled':'requested','scheduled_at'=>null,'scheduled_end'=>null,'updated_at'=>$this->now()];
            if($action==='reschedule') { $data['preferred_at']=$this->future($input['preferred_at']??''); }
            if($b['assignment_id']) {
                $this->update('technician_assignments',(int)$b['assignment_id'],['status'=>'cancelled']);
                $this->notify((int)$b['technician_user_id'],'Booking #'.$id.' was '.$action.'d. Review your job list.',$id);
            }
            $this->update('bookings',$id,$data);
            $this->notify((int)$b['customer_user_id'],'Booking #'.$id.($action==='cancel'?' was cancelled.':' requires review after rescheduling.'),$id);
            $this->audit($who['id'],'booking.'.$action,'bookings',$id,'Booking updated; any prior assignment was cancelled.');
        });
    }
    public function assign(array $actor,int $id,array $input): void
    {
        $this->validate($input,['technician_user_id'=>'required|is_natural_no_zero','scheduled_at'=>'required']); $at=$this->future($input['scheduled_at']);
        $end=(new \DateTimeImmutable($at,new \DateTimeZone('Asia/Manila')))->modify('+2 hours')->format('Y-m-d H:i:s');
        $this->atomic('management',function() use($actor,$id,$input,$at,$end) {
            $who=$this->permit($actor,['admin','dispatcher']); $b=$this->booking($actor,$id);
            if(!in_array($b['status'],['requested','scheduled'],true)) { throw new RuntimeException('Only pending or scheduled bookings can be assigned.'); }
            $tech=$this->db->table('users u')->join('roles r','r.id=u.role_id')->where('u.id',$input['technician_user_id'])->where('r.slug','technician')->where('u.active',1)->countAllResults();
            if(!$tech) { throw new RuntimeException('Choose an active, authorized technician.'); }
            $conflicts=$this->db->table('technician_assignments a')->join('bookings b','b.id=a.booking_id')->where('a.technician_user_id',$input['technician_user_id'])->where('a.status','assigned')->where('b.id !=',$id)->whereIn('b.status',['scheduled','in_progress'])->where('b.scheduled_at <',$end)->where('b.scheduled_end >',$at)->countAllResults();
            if($conflicts) { throw new RuntimeException('Technician has an overlapping two-hour visit. Choose another time or technician.'); }
            $data=['booking_id'=>$id,'technician_user_id'=>(int)$input['technician_user_id'],'assigned_by'=>$who['id'],'status'=>'assigned'];
            if($b['assignment_id']) {
                if($this->db->table('attendance_logs')->where('assignment_id',$b['assignment_id'])->countAllResults()) { throw new RuntimeException('An assignment with attendance cannot be replaced.'); }
                $this->update('technician_assignments',(int)$b['assignment_id'],$data);
                if((int)$b['technician_user_id']!==(int)$input['technician_user_id']) { $this->notify((int)$b['technician_user_id'],'Booking #'.$id.' was reassigned.',$id); }
            } else { $this->insert('technician_assignments',$data); }
            $this->update('bookings',$id,['status'=>'scheduled','scheduled_at'=>$at,'scheduled_end'=>$end,'updated_at'=>$this->now()]);
            $this->notify((int)$b['customer_user_id'],'Booking #'.$id.' is scheduled for '.$at.' Philippine time.',$id);
            $this->notify((int)$input['technician_user_id'],'You are assigned to booking #'.$id.' at '.$at.' Philippine time.',$id);
            $this->audit($who['id'],'booking.assigned','bookings',$id,'Assigned technician user #'.$input['technician_user_id'].' for '.$at.'.');
        });
    }
    private function assignment(array $actor,int $id): array
    {
        $who=$this->permit($actor,['technician']);
        $a=$this->db->table('technician_assignments')->where('id',$id)->where('technician_user_id',$who['id'])->get()->getRowArray();
        if(!$a || $a['status']==='cancelled') { throw new RuntimeException('Assignment not found or unavailable.'); }
        return $a;
    }
    public function attendance(array $actor,int $id,array $input): void
    {
        $this->atomic('management',function() use($actor,$id,$input) {
            $a=$this->assignment($actor,$id); $b=$this->booking($actor,(int)$a['booking_id']);
            $existing=$this->db->table('attendance_logs')->where('assignment_id',$id)->get()->getRowArray();
            if(($input['action']??'')==='checkout') {
                if(!$existing || $existing['checked_out_at']) { throw new RuntimeException('Check in first; each visit can only be checked out once.'); }
                $this->update('attendance_logs',(int)$existing['id'],['checked_out_at'=>$this->now()]);
            } else {
                if(($input['action']??'')!=='checkin' || $existing || $b['status']!=='scheduled') { throw new RuntimeException('Check-in is unavailable for this visit.'); }
                $latitude=$input['latitude']??''; $longitude=$input['longitude']??''; $reason=trim($input['manual_reason']??'');
                if($latitude!=='' || $longitude!=='') {
                    if(!is_numeric($latitude) || !is_numeric($longitude) || !is_finite((float)$latitude) || !is_finite((float)$longitude) || abs((float)$latitude)>90 || abs((float)$longitude)>180) { throw new RuntimeException('Invalid coordinates.'); }
                    $latitude=(float)$latitude; $longitude=(float)$longitude; $reason=null;
                } else {
                    if(mb_strlen($reason)<10 || mb_strlen($reason)>500) { throw new RuntimeException('If location is unavailable, explain the manual check-in (10–500 characters).'); }
                    $latitude=null; $longitude=null;
                }
                $this->insert('attendance_logs',['assignment_id'=>$id,'technician_user_id'=>$a['technician_user_id'],'checked_in_at'=>$this->now(),'latitude'=>$latitude,'longitude'=>$longitude,'manual_reason'=>$reason]);
                $this->update('bookings',(int)$b['id'],['status'=>'in_progress','updated_at'=>$this->now()]);
                $this->notify((int)$b['customer_user_id'],'The technician checked in for booking #'.$b['id'].'.',(int)$b['id']);
            }
            $this->audit((int)$a['technician_user_id'],'attendance.'.($input['action']??''),'technician_assignments',$id,'Recorded technician attendance.');
        });
    }
    public function report(array $actor,int $id,array $input): void
    {
        $this->validate($input,['report'=>'required|min_length[10]|max_length[5000]']);
        $this->atomic('management',function() use($actor,$id,$input) {
            $a=$this->assignment($actor,$id); $b=$this->booking($actor,(int)$a['booking_id']);
            if($a['status']!=='assigned' || $b['status']!=='in_progress' || !$this->db->table('attendance_logs')->where('assignment_id',$id)->countAllResults()) { throw new RuntimeException('Check in before submitting the completion report.'); }
            $reportId=$this->insert('service_reports',['assignment_id'=>$id,'technician_user_id'=>$a['technician_user_id'],'report'=>$input['report']]);
            $this->update('technician_assignments',$id,['status'=>'completed']); $this->update('bookings',(int)$b['id'],['status'=>'completed','updated_at'=>$this->now()]);
            $this->notify((int)$b['customer_user_id'],'Booking #'.$b['id'].' was completed. Your service report is available in booking details.',(int)$b['id']);
            $this->audit((int)$a['technician_user_id'],'service.completed','service_reports',$reportId,'Submitted completion report for booking #'.$b['id'].'.');
        });
    }
    public function issue(array $actor,int $id,array $input): void
    {
        $this->validate($input,['description'=>'required|min_length[10]|max_length[3000]']);
        $this->atomic('management',function() use($actor,$id,$input) {
            $a=$this->assignment($actor,$id);
            $issue=$this->insert('equipment_issues',['assignment_id'=>$id,'reported_by'=>$a['technician_user_id'],'description'=>$input['description'],'status'=>'open']);
            $this->audit((int)$a['technician_user_id'],'equipment.reported','equipment_issues',$issue,'Reported an equipment issue.');
        });
    }
    public function ticket(array $actor,array $input): int
    {
        $who=$this->permit($actor,['customer']); $this->validate($input,['subject'=>'required|max_length[150]','message'=>'required|min_length[10]|max_length[5000]']);
        return $this->atomic('management',function() use($who,$input) {
            $id=$this->insert('support_tickets',['user_id'=>$who['id'],'subject'=>$input['subject'],'message'=>$input['message'],'status'=>'open']);
            $this->audit($who['id'],'support.opened','support_tickets',$id,'Customer submitted a support concern.'); return $id;
        });
    }
    public function respond(array $actor,int $id,array $input): void
    {
        $who=$this->permit($actor,['admin']); $this->validate($input,['response'=>'required|min_length[2]|max_length[5000]','status'=>'required|in_list[open,resolved]']);
        $this->atomic('management',function() use($who,$id,$input) {
            $ticket=$this->db->table('support_tickets')->where('id',$id)->get()->getRowArray(); if(!$ticket) { throw new RuntimeException('Support concern not found.'); }
            $this->update('support_tickets',$id,['response'=>$input['response'],'status'=>$input['status'],'responded_by'=>$who['id']]);
            $this->notify((int)$ticket['user_id'],'Your support concern #'.$id.' has an update.'); $this->audit($who['id'],'support.responded','support_tickets',$id,'Updated support response and status.');
        });
    }
    public function announcement(array $actor,array $input): void
    {
        $who=$this->permit($actor,['admin','hr']); $this->validate($input,['title'=>'required|max_length[150]','body'=>'required|min_length[5]|max_length[5000]','category'=>'required|in_list[general,safety]']);
        $this->atomic('management',function() use($who,$input) {
            $id=$this->insert('announcements',['author_user_id'=>$who['id'],'title'=>$input['title'],'body'=>$input['body'],'category'=>$input['category']]);
            $this->audit($who['id'],'announcement.published','announcements',$id,'Published a '.$input['category'].' announcement.');
        });
    }
    public function catalog(array $actor,string $kind,int $id,array $input): void
    {
        $who=$this->permit($actor,['admin']); if(!in_array($kind,['products','services'],true)) { throw new RuntimeException('Invalid catalog.'); }
        $this->validate($input,['name'=>'required|max_length[100]','description'=>'required|max_length[5000]','price'=>'permit_empty|decimal|greater_than_equal_to[0]','active'=>'required|in_list[0,1]']);
        $price=$input['price']??''; if($price!=='' && (!preg_match('/^\d{1,10}(\.\d{1,2})?$/',$price))) { throw new RuntimeException('Enter a price with at most two decimal places.'); }
        $data=['name'=>$input['name'],'description'=>$input['description'],'price'=>$price===''?null:$price,'active'=>(int)$input['active']];
        if($kind==='products') {
            $this->validate($input,['category'=>'required|in_list[Solar panels,Inverters,Batteries,Mounting equipment,Wires]','stock'=>'permit_empty|is_natural|less_than_equal_to[100000000]']);
            $data+=['category'=>$input['category'],'stock'=>($input['stock']??'')===''?null:(int)$input['stock']];
        }
        $this->atomic('management',function() use($who,$kind,$id,$data) {
            if($id) { if(!$this->db->table($kind)->where('id',$id)->countAllResults()) { throw new RuntimeException('Catalog item not found.'); } $this->update($kind,$id,$data); }
            else { $id=$this->insert($kind,$data); }
            $this->audit($who['id'],'catalog.saved',$kind,$id,'Updated catalog information.');
        });
    }
    public function payment(array $actor,int $bookingId,array $input): void
    {
        $who=$this->permit($actor,['admin']); $this->booking($actor,$bookingId);
        $this->validate($input,['amount'=>'required|decimal|greater_than[0]','reference'=>'required|max_length[100]']);
        if(!preg_match('/^\d{1,10}(\.\d{1,2})?$/',$input['amount'])) { throw new RuntimeException('Enter a valid payment amount with at most two decimal places.'); }
        $this->atomic('management',function() use($who,$bookingId,$input) {
            if($this->db->table('payments')->where('reference',$input['reference'])->countAllResults()) { throw new RuntimeException('Payment reference already recorded.'); }
            $id=$this->insert('payments',['booking_id'=>$bookingId,'recorded_by'=>$who['id'],'amount'=>$input['amount'],'reference'=>$input['reference'],'paid_at'=>$this->now()]);
            $b=$this->db->table('bookings')->where('id',$bookingId)->get()->getRowArray();
            $this->notify((int)$b['customer_user_id'],'Payment receipt #'.$id.' was recorded for booking #'.$bookingId.'.',$bookingId);
            $this->audit($who['id'],'payment.recorded','payments',$id,'Recorded payment for booking #'.$bookingId.'.');
        });
    }
    public function saveUser(array $actor,int $id,array $input): void
    {
        $who=$this->permit($actor,['admin','hr']);
        $this->atomic('management',function() use($who,$actor,$id,$input) {
            $target=$this->db->table('users')->where('id',$id)->get()->getRowArray();
            if(!$target || ($who['role']==='hr' && $target['account_type']!=='employee')) { throw new RuntimeException('Account not found or access denied.'); }
            $data=[];
            if($target['account_type']==='employee') {
                if(!in_array($input['department']??'',Accounts::DEPARTMENTS,true)) { throw new RuntimeException('Choose a valid department.'); }
                $dept=$this->db->table('departments')->where('name',$input['department'])->get()->getRowArray(); $data['department_id']=$dept['id'];
                if($who['role']==='admin') {
                    if(!in_array($input['role']??'', ['employee','hr','dispatcher','technician'],true)) { throw new RuntimeException('Choose an authorized employee role.'); }
                    $data['role_id']=$this->db->table('roles')->where('slug',$input['role'])->get()->getRowArray()['id'];
                }
            }
            if($who['role']==='admin') {
                if(!in_array($input['active']??'', ['0','1'],true)) { throw new RuntimeException('Choose an account status.'); }
                if((int)$who['id']===$id && $input['active']==='0') { throw new RuntimeException('You cannot deactivate your own account.'); }
                $data['active']=(int)$input['active'];
            }
            $targetAccount=['type'=>$target['account_type'],'id'=>(int)$target['legacy_id'],'user_id'=>$id];
            (new Accounts($this->db))->updateProfile($targetAccount,$input,(int)$who['id']);
            if($target['account_type']==='employee') {
                if(!$this->db->table('employees')->where('Employee_Id',$target['legacy_id'])->update(['Department'=>$input['department']])) { throw new RuntimeException('Employee department could not be saved.'); }
            }
            if($data) { $this->update('users',$id,$data); }
            $this->audit($who['id'],'account.updated','users',$id,'Updated account information, access or department.');
        });
    }
    public function settings(array $actor,array $input): void
    {
        $who=$this->permit($actor,['admin']);
        $this->validate($input,['contact_email'=>'permit_empty|valid_email|max_length[254]','contact_phone'=>'permit_empty|max_length[50]','office_address'=>'permit_empty|max_length[255]']);
        $this->atomic('management',function() use($who,$input) {
            foreach(['contact_email','contact_phone','office_address'] as $key) {
                $row=$this->db->table('settings')->where('setting_key',$key)->get()->getRowArray();
                if($row) { $this->update('settings',(int)$row['id'],['value'=>$input[$key]??'']); } else { $this->insert('settings',['setting_key'=>$key,'value'=>$input[$key]??'']); }
            }
            $this->audit($who['id'],'settings.updated','users',$who['id'],'Updated official company contact information.');
        });
    }
}
