<?php

use App\Libraries\Accounts;
use App\Libraries\Management;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

require_once APPPATH.'Database/Migrations/2026-10-20-150000_ManagementFoundation.php';

final class ManagementWorkflowTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    private Management $management;
    private int $sequence=0;

    protected function setUp(): void
    {
        parent::setUp(); $this->db=db_connect(); $_SESSION=[];
        self::assertSame('testing',ENVIRONMENT); self::assertSame('SQLite3',$this->db->DBDriver); self::assertSame(':memory:',$this->db->database);
        $this->cleanDatabase(); $this->db->resetTransStatus();
        (new App\Database\Migrations\ManagementFoundation())->up();
        $this->management=new Management($this->db);
        $cache=new \CodeIgniter\Test\Mock\MockCache(config('Cache'));
        \Config\Services::injectMock('cache',$cache); \Config\Services::injectMock('throttler',new \CodeIgniter\Throttle\Throttler($cache));
    }
    protected function tearDown(): void { $this->cleanDatabase(); parent::tearDown(); }
    private function cleanDatabase(): void
    {
        $this->db->resetDataCache();
        $this->db->query('PRAGMA foreign_keys = OFF');
        foreach($this->db->listTables() as $table) {
            if(str_starts_with($table,$this->db->DBPrefix)) { $this->db->query('DROP TABLE IF EXISTS "'.str_replace('"','""',$table).'"'); }
        }
        $this->db->query('PRAGMA foreign_keys = ON');
        $this->db->resetDataCache();
    }
    private function data(array $extra=[]): array
    {
        $n=++$this->sequence;
        return array_replace(['firstname'=>'Test','middlename'=>'','lastname'=>'Person','birthdate'=>'1990-01-01','gender'=>'Female','email'=>'test'.$n.'@example.com','contact'=>'09123456789','address'=>'Pasig City','username'=>'person'.$n,'password'=>'WorkflowTest123!','confirmPassword'=>'WorkflowTest123!','department'=>'IT'],$extra);
    }
    private function actor(string $type='customer',?string $role=null): array
    {
        $id=(new Accounts())->create($type,$this->data());
        if($role) {
            $roleId=$this->db->table('roles')->where('slug',$role)->get()->getRowArray()['id'];
            $this->db->table('users')->where('account_type',$type)->where('legacy_id',$id)->update(['role_id'=>$roleId]);
        }
        return (new Accounts())->find($type,$id);
    }
    private function future(int $hours=24): string { return (new DateTimeImmutable('now',new DateTimeZone('Asia/Manila')))->modify('+'.$hours.' hours')->format('Y-m-d\TH:i'); }
    private function booking(array $customer): int
    {
        $service=$this->db->table('services')->get()->getRowArray();
        return $this->management->request($customer,['service_id'=>(string)$service['id'],'preferred_at'=>$this->future(),'address'=>'Pasig test site','notes'=>'Customer notes.']);
    }
    private function sessionFor(array $actor): array { return ['account'=>$actor,'signed_in_at'=>time(),'last_activity'=>time()]; }
    private function submit(string $path,array $input,array $actor)
    {
        $hash=service('security')->getHash(); $session=$this->sessionFor($actor); $session[config('Security')->tokenName]=$hash;
        return $this->withSession($session)->post($path,$input+[csrf_token()=>$hash]);
    }
    public function testMigrationCreatesReferenceDataAndForeignKeysWithoutFakeOperations(): void
    {
        $this->assertSame(6,$this->db->table('roles')->countAllResults()); $this->assertSame(8,$this->db->table('departments')->countAllResults());
        $this->assertSame(7,$this->db->table('services')->countAllResults());
        foreach(['products','bookings','payments','attendance_logs','announcements'] as $table) { $this->assertSame(0,$this->db->table($table)->countAllResults()); }
        $this->assertNotEmpty($this->db->getForeignKeyData('bookings'));
    }
    public function testCompleteRequestAssignmentAttendanceReportAndReceiptWorkflow(): void
    {
        $c=$this->actor(); $admin=$this->actor('admin'); $dispatch=$this->actor('employee','dispatcher'); $tech=$this->actor('employee','technician');
        $id=$this->booking($c); $this->assertSame('requested',$this->management->booking($c,$id)['status']);
        $this->management->assign($dispatch,$id,['technician_user_id'=>(string)$tech['user_id'],'scheduled_at'=>$this->future()]);
        $b=$this->management->booking($tech,$id); $assignment=(int)$b['assignment_id']; $this->assertSame('scheduled',$b['status']);
        $this->management->attendance($tech,$assignment,['action'=>'checkin','latitude'=>'14.5764','longitude'=>'121.0851']);
        $this->assertSame('in_progress',$this->management->booking($c,$id)['status']);
        $this->management->issue($tech,$assignment,['description'=>'A test equipment issue needs assessment.']);
        $this->management->report($tech,$assignment,['report'=>'Completed the documented maintenance checks for this test visit.']);
        $this->management->attendance($tech,$assignment,['action'=>'checkout']);
        $this->assertSame('completed',$this->management->booking($c,$id)['status']);
        $this->management->payment($admin,$id,['amount'=>'1500.00','reference'=>'TEST-RECEIPT-1']);
        $payment=$this->db->table('payments')->get()->getRowArray();
        $this->withSession($this->sessionFor($c))->get('/receipts/'.$payment['id'])->assertOK();
        $this->assertGreaterThanOrEqual(4,$this->db->table('notifications')->where('user_id',$c['user_id'])->countAllResults());
        $this->assertNotEmpty($this->db->table('attendance_logs')->get()->getRowArray()['checked_out_at']);
        $this->assertSame(1,$this->db->table('service_reports')->countAllResults());
    }
    public function testCustomersCannotReadOthersBookingsPaymentsTicketsOrNotifications(): void
    {
        $one=$this->actor(); $two=$this->actor(); $admin=$this->actor('admin'); $id=$this->booking($one);
        $this->management->payment($admin,$id,['amount'=>'1.00','reference'=>'PRIVATE-RECEIPT']);
        $receipt=$this->db->table('payments')->get()->getRowArray()['id'];
        $this->management->ticket($one,['subject'=>'Private subject','message'=>'Private customer concern details.']);
        $this->withSession($this->sessionFor($two))->get('/bookings/'.$id)->assertStatus(404);
        $this->withSession($this->sessionFor($two))->get('/receipts/'.$receipt)->assertStatus(404);
        $body=$this->withSession($this->sessionFor($two))->get('/support')->response()->getBody(); $this->assertStringNotContainsString('Private subject',$body);
        $body=$this->withSession($this->sessionFor($two))->get('/notifications')->response()->getBody(); $this->assertStringNotContainsString('Booking #'.$id.' received',$body);
        $this->submit('/notifications/read',[],$two);
        $this->assertNull($this->db->table('notifications')->where('user_id',$one['user_id'])->get()->getRowArray()['read_at']);
    }
    public function testCustomerCannotAssignOrSetBookingOwner(): void
    {
        $c=$this->actor(); $other=$this->actor(); $id=$this->booking($c);
        $this->submit('/bookings/'.$id.'/assign',['technician_user_id'=>(string)$other['user_id'],'scheduled_at'=>$this->future()],$c)->assertStatus(403);
        $service=$this->db->table('services')->get()->getRowArray()['id'];
        $this->submit('/bookings',['customer_user_id'=>(string)$other['user_id'],'status'=>'completed','service_id'=>(string)$service,'preferred_at'=>$this->future(),'address'=>'My address','notes'=>''],$c)->assertRedirect();
        $row=$this->db->table('bookings')->orderBy('id','DESC')->get()->getRowArray(); $this->assertSame($c['user_id'],(int)$row['customer_user_id']); $this->assertSame('requested',$row['status']);
    }
    public function testAssignmentConflictsAreBlockedAndCancelledSlotCanBeReused(): void
    {
        $c=$this->actor(); $admin=$this->actor('admin'); $tech=$this->actor('employee','technician'); $first=$this->booking($c); $second=$this->booking($c);
        $input=['technician_user_id'=>(string)$tech['user_id'],'scheduled_at'=>$this->future()]; $this->management->assign($admin,$first,$input);
        try { $this->management->assign($admin,$second,$input); $this->fail('Overlapping assignment accepted'); } catch(RuntimeException $e) { $this->assertStringContainsString('overlapping',$e->getMessage()); }
        $this->management->changeBooking($c,$first,['action'=>'cancel']); $this->management->assign($admin,$second,$input);
        $this->assertSame('scheduled',$this->management->booking($c,$second)['status']);
    }
    public function testNonTechnicianCannotBeAssigned(): void
    {
        $c=$this->actor(); $admin=$this->actor('admin'); $employee=$this->actor('employee'); $id=$this->booking($c);
        $this->expectException(RuntimeException::class); $this->expectExceptionMessage('authorized technician');
        $this->management->assign($admin,$id,['technician_user_id'=>(string)$employee['user_id'],'scheduled_at'=>$this->future()]);
    }
    public function testReschedulingCancelsPriorAssignmentAndRequiresReview(): void
    {
        $c=$this->actor(); $admin=$this->actor('admin'); $tech=$this->actor('employee','technician'); $id=$this->booking($c);
        $this->management->assign($admin,$id,['technician_user_id'=>(string)$tech['user_id'],'scheduled_at'=>$this->future()]);
        $this->management->changeBooking($c,$id,['action'=>'reschedule','preferred_at'=>$this->future(48)]);
        $b=$this->management->booking($c,$id); $this->assertSame('requested',$b['status']); $this->assertNull($b['scheduled_at']); $this->assertSame('cancelled',$b['assignment_status']);
    }
    public function testTechnicianCannotCheckInAnotherTechniciansAssignment(): void
    {
        $c=$this->actor(); $admin=$this->actor('admin'); $tech=$this->actor('employee','technician'); $other=$this->actor('employee','technician'); $id=$this->booking($c);
        $this->management->assign($admin,$id,['technician_user_id'=>(string)$tech['user_id'],'scheduled_at'=>$this->future()]); $b=$this->management->booking($tech,$id);
        $this->submit('/assignments/'.$b['assignment_id'].'/attendance',['action'=>'checkin','latitude'=>'14','longitude'=>'121'],$other)->assertStatus(404);
        $this->assertSame(0,$this->db->table('attendance_logs')->countAllResults());
    }
    public function testInvalidCoordinatesFailAndManualExceptionRequiresReason(): void
    {
        $c=$this->actor(); $admin=$this->actor('admin'); $tech=$this->actor('employee','technician'); $id=$this->booking($c);
        $this->management->assign($admin,$id,['technician_user_id'=>(string)$tech['user_id'],'scheduled_at'=>$this->future()]); $assignment=(int)$this->management->booking($tech,$id)['assignment_id'];
        foreach([['latitude'=>'91','longitude'=>'121'],['manual_reason'=>'no'],['latitude'=>'NaN','longitude'=>'121']] as $invalid) {
            try { $this->management->attendance($tech,$assignment,['action'=>'checkin']+$invalid); $this->fail('Invalid attendance accepted'); } catch(RuntimeException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->management->attendance($tech,$assignment,['action'=>'checkin','manual_reason'=>'Browser location permission was denied at the test site.']);
        $row=$this->db->table('attendance_logs')->get()->getRowArray(); $this->assertNull($row['latitude']); $this->assertNull($row['longitude']); $this->assertNotEmpty($row['manual_reason']);
    }
    public function testReportBeforeCheckInIsRejected(): void
    {
        $c=$this->actor(); $admin=$this->actor('admin'); $tech=$this->actor('employee','technician'); $id=$this->booking($c);
        $this->management->assign($admin,$id,['technician_user_id'=>(string)$tech['user_id'],'scheduled_at'=>$this->future()]); $assignment=(int)$this->management->booking($tech,$id)['assignment_id'];
        $this->expectException(RuntimeException::class); $this->expectExceptionMessage('Check in before');
        $this->management->report($tech,$assignment,['report'=>'Premature completion report.']);
    }
    public function testDeactivationInvalidatesLoginAndExistingSession(): void
    {
        $c=$this->actor(); $this->db->table('users')->where('id',$c['user_id'])->update(['active'=>0]);
        $this->assertNull((new Accounts())->find($c['type'],$c['id']));
        $profile=(new Accounts())->profile($c['type'],$c['id']); $this->assertNull((new Accounts())->authenticate($profile['username'],'WorkflowTest123!'));
        $this->withSession($this->sessionFor($c))->get('/account')->assertRedirectTo('/login');
    }
    public function testDashboardAndRoleSpecificPagesRenderForEachRole(): void
    {
        foreach([['customer',null],['admin',null],['employee','hr'],['employee','dispatcher'],['employee','technician'],['employee',null]] as [$type,$role]) {
            $actor=$this->actor($type,$role); $this->withSession($this->sessionFor($actor))->get('/account')->assertOK();
            $this->withSession($this->sessionFor($actor))->get('/profile')->assertOK();
        }
        $admin=$this->actor('admin');
        foreach(['/users','/manage/products','/manage/services','/settings','/reports','/records','/payments','/support','/announcements'] as $path) { $this->withSession($this->sessionFor($admin))->get($path)->assertOK(); }
    }
    public function testHrCannotGrantRolesButAdminCan(): void
    {
        $hr=$this->actor('employee','hr'); $staff=$this->actor('employee'); $admin=$this->actor('admin'); $p=(new Accounts())->profile('employee',$staff['id']);
        $data=$this->data(['firstname'=>$p['first_name'],'lastname'=>$p['last_name'],'email'=>$p['email'],'username'=>$p['username'],'department'=>'HR','role'=>'technician','active'=>'1']);
        $this->management->saveUser($hr,$staff['user_id'],$data); $this->assertSame('employee',(new Accounts())->find('employee',$staff['id'])['role']);
        $this->management->saveUser($admin,$staff['user_id'],$data); $this->assertSame('technician',(new Accounts())->find('employee',$staff['id'])['role']);
    }
    public function testSupportResponseNotifiesOnlyTheOwner(): void
    {
        $c=$this->actor(); $admin=$this->actor('admin'); $id=$this->management->ticket($c,['subject'=>'Service question','message'=>'Please help with my service request.']);
        $this->management->respond($admin,$id,['response'=>'We have reviewed your concern.','status'=>'resolved']);
        $this->assertSame('resolved',$this->db->table('support_tickets')->where('id',$id)->get()->getRowArray()['status']);
        $this->assertSame(1,$this->db->table('notifications')->where('user_id',$c['user_id'])->countAllResults());
    }
    public function testProfileAndPasswordUpdatesValidateAndPreserveHashes(): void
    {
        $c=$this->actor(); $p=(new Accounts())->profile('customer',$c['id']);
        $data=$this->data(['email'=>$p['email'],'username'=>$p['username'],'address'=>'Updated Pasig address']);
        (new Accounts())->updateProfile($c,$data); $this->assertSame('Updated Pasig address',(new Accounts())->profile('customer',$c['id'])['address']);
        (new Accounts())->changePassword($c,'WorkflowTest123!','ChangedPassword123!');
        $this->assertNotNull((new Accounts())->authenticate($p['username'],'ChangedPassword123!')); $this->assertNull((new Accounts())->authenticate($p['username'],'WorkflowTest123!'));
        $audits=json_encode($this->db->table('audit_logs')->get()->getResultArray()); $this->assertStringNotContainsString('ChangedPassword123!',$audits); $this->assertStringNotContainsString('$2y$',$audits);
    }
    public function testCatalogWritesAreAdminOnlyAndFeedThePublicHomepage(): void
    {
        $admin=$this->actor('admin');
        $this->management->catalog($admin,'products',0,['name'=>'Verified test panel','category'=>'Solar panels','description'=>'Test fixture product description.','price'=>'15000.00','stock'=>'5','active'=>'1']);
        $result=$this->get('/'); $result->assertSee('Verified test panel'); $result->assertSee('PHP 15,000.00');
        $this->submit('/manage/products/0',['name'=>'Forged product'],$this->actor())->assertStatus(403);
    }
    public function testPastTimesInvalidFieldsAndUnavailableServicesAreRejected(): void
    {
        $c=$this->actor(); $s=$this->db->table('services')->get()->getRowArray();
        try { $this->management->request($c,['service_id'=>(string)$s['id'],'preferred_at'=>'2000-01-01T10:00','address'=>'Test site']); $this->fail('Past booking accepted'); } catch(RuntimeException $e) { $this->assertStringContainsString('future',$e->getMessage()); }
        $this->db->table('services')->where('id',$s['id'])->update(['active'=>0]);
        $this->expectException(RuntimeException::class); $this->expectExceptionMessage('unavailable');
        $this->management->request($c,['service_id'=>(string)$s['id'],'preferred_at'=>$this->future(),'address'=>'Test site']);
    }
    public function testFailedMutationRollsBackBookingAndNotifications(): void
    {
        $c=$this->actor(); $this->db->query('DROP TABLE '.$this->db->prefixTable('notifications'));
        try { $this->booking($c); $this->fail('Missing notifications table accepted'); } catch(Throwable $e) { $this->assertSame(0,$this->db->table('bookings')->countAllResults()); }
    }

    public function testMigrationPreservesExistingProfilesAndPasswordHashesExactly(): void
    {
        $this->actor(); $this->actor('employee'); $this->actor('admin'); $before=[];
        foreach(['customers','employees','admins'] as $table) { $before[$table]=$this->db->table($table)->get()->getResultArray(); }
        $this->db->query('PRAGMA foreign_keys = OFF'); $this->db->resetDataCache();
        foreach($this->db->listTables() as $table) {
            if(str_starts_with($table,$this->db->DBPrefix) && !in_array($table,array_map(fn($t)=>$this->db->prefixTable($t),array_keys($before)),true)) { $this->db->query('DROP TABLE "'.$table.'"'); }
        }
        $this->db->query('PRAGMA foreign_keys = ON'); $this->db->resetDataCache();
        (new App\Database\Migrations\ManagementFoundation())->up();
        foreach($before as $table=>$rows) { $this->assertSame($rows,$this->db->table($table)->get()->getResultArray()); }
        $this->assertSame(3,$this->db->table('users')->countAllResults());
    }
    public function testPasswordChangeInvalidatesAllPreviouslyIssuedSessions(): void
    {
        $c=$this->actor(); $session=$this->sessionFor($c);
        (new Accounts())->changePassword($c,'WorkflowTest123!','NewWorkflowPassword123!');
        $this->withSession($session)->get('/account')->assertRedirectTo('/login');
        $this->assertGreaterThan($c['session_version'],(new Accounts())->find('customer',$c['id'])['session_version']);
    }
    public function testStaleRoleCannotBypassServerAuthorization(): void
    {
        $actor=$this->actor('employee','dispatcher'); $actor['role']='admin';
        $this->withSession($this->sessionFor($actor))->get('/reports')->assertStatus(403);
        $this->submit('/manage/services/0',['name'=>'Unauthorized'],$actor)->assertStatus(403);
    }
    public function testInProgressVisitCannotBeCancelledOrRescheduled(): void
    {
        $c=$this->actor(); $admin=$this->actor('admin'); $tech=$this->actor('employee','technician'); $id=$this->booking($c);
        $this->management->assign($admin,$id,['technician_user_id'=>(string)$tech['user_id'],'scheduled_at'=>$this->future()]);
        $assignment=(int)$this->management->booking($tech,$id)['assignment_id'];
        $this->management->attendance($tech,$assignment,['action'=>'checkin','manual_reason'=>'A synthetic location exception for testing.']);
        $this->submit('/bookings/'.$id.'/change',['action'=>'cancel'],$c)->assertSessionHas('error');
        $this->assertSame('in_progress',$this->management->booking($c,$id)['status']);
    }
    public function testDuplicatePaymentReferenceIsRejectedWithoutDuplicateRecords(): void
    {
        $c=$this->actor(); $admin=$this->actor('admin'); $id=$this->booking($c); $input=['amount'=>'100.00','reference'=>'TEST-UNIQUE-REFERENCE'];
        $this->management->payment($admin,$id,$input);
        try { $this->management->payment($admin,$id,$input); $this->fail('Duplicate payment accepted'); } catch(RuntimeException $e) { $this->assertStringContainsString('already recorded',$e->getMessage()); }
        $this->assertSame(1,$this->db->table('payments')->countAllResults());
    }
    public function testSettingsPublishOnlyValidatedEscapedCompanyDetails(): void
    {
        $admin=$this->actor('admin');
        $this->management->settings($admin,['contact_email'=>'official@example.test','contact_phone'=>'<script>unsafe</script>','office_address'=>'Test office address']);
        $body=(string)$this->get('/')->response()->getBody(); $this->assertStringContainsString('official@example.test',$body); $this->assertStringNotContainsString('<script>unsafe</script>',$body);
        $this->withSession($this->sessionFor($admin))->get('/reports?from=2026-02-30')->assertStatus(400);
    }
}
