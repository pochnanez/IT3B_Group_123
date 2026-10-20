<?php
// Synthetic browser-QA fixtures, strictly isolated from the live database.
$root=dirname(__DIR__);
$file=$root.'/writable/management-preview-'.date('YmdHis').'.sqlite';
if(file_exists($file)) { throw new RuntimeException('Preview database already exists; refusing to overwrite it.'); }
putenv('database.tests.database='.$file);
putenv('database.tests.DBDriver=SQLite3');
putenv('database.tests.DBPrefix=qa_');
define('ENVIRONMENT','testing'); define('FCPATH',$root.'/public/');
require $root.'/app/Config/Paths.php'; $paths=new Config\Paths(); require $paths->systemDirectory.'/Boot.php'; CodeIgniter\Boot::bootConsole($paths);
$db=db_connect();
if($db->DBDriver!=='SQLite3' || $db->database!==$file || $db->DBPrefix!=='qa_') { throw new RuntimeException('Preview database safety check failed.'); }
require APPPATH.'Database/Migrations/2026-10-20-150000_ManagementFoundation.php';
(new App\Database\Migrations\ManagementFoundation())->up();
$accounts=new App\Libraries\Accounts($db); $m=new App\Libraries\Management($db); $actors=[];
foreach(['customer','admin','hr','dispatcher','technician','employee'] as $role) {
    $type=in_array($role,['customer','admin'],true)?$role:'employee';
    $id=$accounts->create($type,['firstname'=>'Preview','middlename'=>'','lastname'=>ucfirst($role),'birthdate'=>'1990-01-01','gender'=>'Prefer not to say','email'=>'preview-'.$role.'@example.test','contact'=>'09123456789','address'=>'Synthetic preview address, not a customer site','username'=>'preview_'.$role,'department'=>'IT','password'=>'PreviewOnly123!','confirmPassword'=>'PreviewOnly123!']);
    $roleId=$db->table('roles')->where('slug',$role)->get()->getRowArray()['id'];
    $db->table('users')->where('account_type',$type)->where('legacy_id',$id)->update(['role_id'=>$roleId]);
    $actors[$role]=$accounts->find($type,$id);
}
$service=$db->table('services')->get()->getRowArray()['id'];
$at=(new DateTimeImmutable('now',new DateTimeZone('Asia/Manila')))->modify('+1 day')->format('Y-m-d\TH:i');
$booking=$m->request($actors['customer'],['service_id'=>(string)$service,'preferred_at'=>$at,'address'=>'PREVIEW ONLY — synthetic service address','notes'=>'Synthetic browser verification record. Not a real service request.']);
$m->assign($actors['dispatcher'],$booking,['technician_user_id'=>(string)$actors['technician']['user_id'],'scheduled_at'=>$at]);
$m->request($actors['customer'],['service_id'=>(string)$service,'preferred_at'=>$at,'address'=>'PREVIEW ONLY — second synthetic site','notes'=>'Test request awaiting review.']);
$m->announcement($actors['hr'],['title'=>'Preview safety announcement','category'=>'safety','body'=>'Synthetic announcement for checking employee dashboard rendering. Not an operational instruction.']);
$m->ticket($actors['customer'],['subject'=>'Preview customer concern','message'=>'Synthetic support message for browser verification only.']);
$m->payment($actors['admin'],$booking,['amount'=>'100.00','reference'=>'PREVIEW-NOT-A-REAL-PAYMENT']);
echo json_encode(['database'=>$file,'booking_id'=>$booking,'actors'=>array_map(fn($a)=>['user_id'=>$a['user_id'],'role'=>$a['role']],$actors)]);
