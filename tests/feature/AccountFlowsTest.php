<?php

use App\Libraries\Accounts;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class AccountFlowsTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private Accounts $accounts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = db_connect();
        // Never create fixtures in the configured live MySQL database.
        self::assertSame('testing', ENVIRONMENT);
        self::assertSame('SQLite3', $this->db->DBDriver);
        self::assertSame(':memory:', $this->db->database);
        $this->db->resetTransStatus();
        foreach (['customers' => 'Customer_id', 'employees' => 'Employee_Id', 'admins' => 'admin_id'] as $table => $key) {
            $name = $this->db->prefixTable($table);
            $this->db->query("DROP TABLE IF EXISTS $name");
            $department = $table === 'employees' ? ', Department TEXT NOT NULL' : '';
            $this->db->query("CREATE TABLE $name ($key INTEGER PRIMARY KEY AUTOINCREMENT,
                First_Name TEXT NOT NULL, Middle_Name TEXT NULL, Last_Name TEXT NOT NULL,
                Birthdate TEXT NOT NULL, Gender TEXT NOT NULL, Email TEXT NOT NULL UNIQUE COLLATE NOCASE,
                Contact_Number TEXT NOT NULL, Address TEXT NOT NULL, Username TEXT NOT NULL UNIQUE COLLATE NOCASE,
                Password TEXT NOT NULL $department)");
        }
        config('AccountAccess')->hrEmployeeIds = [];
        $this->accounts = new Accounts($this->db);
        // Use an isolated in-memory cache, never the runtime file cache.
        $cache = new \CodeIgniter\Test\Mock\MockCache(config('Cache'));
        \Config\Services::injectMock('cache', $cache);
        \Config\Services::injectMock('throttler', new \CodeIgniter\Throttle\Throttler($cache));
    }

    private function input(array $override = []): array
    {
        return array_replace(['firstname' => 'Test', 'middlename' => '', 'lastname' => 'Customer',
            'birthdate' => '1990-01-01', 'gender' => 'Prefer not to say', 'email' => 'test@example.com',
            'contact' => '09123456789', 'address' => 'Pasig City', 'username' => 'testuser',
            'password' => 'OnlyForTests123!', 'confirmPassword' => 'OnlyForTests123!'], $override);
    }

    private function signedIn(string $type = 'customer', array $override = []): array
    {
        $id = $this->accounts->create($type, $this->input($override));
        return ['account' => ['type' => $type, 'id' => $id], 'signed_in_at' => time(), 'last_activity' => time()];
    }

    private function submit(string $path, array $input, array $session = [])
    {
        $token = service('security')->getHash();
        $session[config('Security')->tokenName] = $token;
        return $this->withSession($session)->post($path, $input + [csrf_token() => $token]);
    }

    public function testRegistrationShowsCsrfAndNoPublicDepartment(): void
    {
        $result = $this->get('/register');
        $result->assertOK();
        $this->assertStringContainsString('name="' . csrf_token() . '"', (string) $result->response()->getBody());
        $this->assertStringNotContainsString('<select', (string) $result->response()->getBody());
        $this->assertStringContainsString('href="' . site_url('login') . '"', (string) $result->response()->getBody());
    }

    public function testCustomerRegistrationHashesPasswordAndAllowsLogin(): void
    {
        $result = $this->submit('/register/save', $this->input());
        $result->assertRedirectTo('/login');
        $result->assertSessionHas('success');
        $row = $this->db->table('customers')->get()->getRowArray();
        $this->assertTrue(password_verify('OnlyForTests123!', $row['Password']));
        $this->assertArrayNotHasKey('confirmPassword', $row);
        $this->assertSame(0, $this->db->table('employees')->countAllResults());
        $this->assertSame('customer', $this->accounts->authenticate('TEST@EXAMPLE.COM', 'OnlyForTests123!')['role']);
    }

    public function testPublicRoleAndDepartmentTamperingCannotCreateEmployee(): void
    {
        $result = $this->submit('/register/save', $this->input(['department' => 'HR', 'role' => 'admin']));
        $result->assertSessionHas('errors');
        $this->assertSame(0, $this->db->table('employees')->countAllResults());
        $this->assertSame(0, $this->db->table('customers')->countAllResults());
    }

    public function testSubmittedRoleIsIgnoredForCustomer(): void
    {
        $this->submit('/register/save', $this->input(['role' => 'admin', 'department' => 'N/A']));
        $this->assertSame('customer', $this->accounts->authenticate('testuser', 'OnlyForTests123!')['role']);
    }

    public function testInvalidInputNeverFlashesPasswords(): void
    {
        $result = $this->submit('/register/save', $this->input(['email' => 'bad', 'contact' => '123', 'confirmPassword' => 'mismatch']));
        $result->assertSessionHas('errors');
        $safe = session()->get('formInput');
        $this->assertArrayNotHasKey('password', $safe);
        $this->assertArrayNotHasKey('confirmPassword', $safe);
        $this->assertSame(0, $this->db->table('customers')->countAllResults());
    }

    public function testFutureDateIsRejected(): void
    {
        $this->submit('/register/save', $this->input(['birthdate' => '2999-01-01']))->assertSessionHas('errors');
        $this->assertSame(0, $this->db->table('customers')->countAllResults());
    }

    public function testDuplicateAcrossTablesIsRejected(): void
    {
        $this->accounts->create('admin', $this->input());
        $this->submit('/register/save', $this->input(['email' => 'TEST@example.com', 'username' => 'different']))->assertSessionHas('errors');
        $this->assertSame(0, $this->db->table('customers')->countAllResults());
    }

    public function testMissingCsrfTokenIsRejected(): void
    {
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->post('/register/save', $this->input());
    }

    public function testGuestCannotOpenStaffCreation(): void
    {
        $this->get('/employees/create')->assertRedirectTo('/login');
        $this->get('/admin/register')->assertRedirectTo('/login');
    }

    public function testCustomerCannotUseStaffRoutesEvenWithForgedSessionRole(): void
    {
        $session = $this->signedIn();
        $session['account']['role'] = 'admin';
        $this->withSession($session)->get('/employees/create')->assertStatus(403);
        $this->submit('/admin/register', $this->input(), $session)->assertStatus(403);
        $this->assertSame(0, $this->db->table('admins')->countAllResults());
    }

    public function testAdminCanCreateEmployeeWithValidatedDepartment(): void
    {
        $session = $this->signedIn('admin');
        $data = $this->input(['email' => 'staff@example.com', 'username' => 'staffuser', 'department' => 'HR']);
        $this->submit('/employees/create', $data, $session)->assertSessionHas('success');
        $account = $this->accounts->authenticate('staffuser', 'OnlyForTests123!');
        $this->assertSame('employee', $account['role']);
        $data['department'] = 'Administrator';
        $this->submit('/employees/create', $data, $session)->assertSessionHas('errors');
        $this->assertSame(1, $this->db->table('employees')->countAllResults());
    }

    public function testHrNeedsExplicitAssignmentAndCannotCreateAdmins(): void
    {
        $session = $this->signedIn('employee', ['department' => 'HR']);
        $this->withSession($session)->get('/employees/create')->assertStatus(403);
        config('AccountAccess')->hrEmployeeIds = [$session['account']['id']];
        $this->withSession($session)->get('/employees/create')->assertOK();
        $this->withSession($session)->get('/admin/register')->assertStatus(403);
    }

    public function testExpiredSessionCannotAccessAccount(): void
    {
        $session = $this->signedIn();
        $session['last_activity'] = time() - 1801;
        $this->withSession($session)->get('/account')->assertRedirectTo('/login');
        $this->assertNull(session()->get('account'));
    }

    public function testDeletedAccountLosesAccess(): void
    {
        $session = $this->signedIn();
        $this->db->table('customers')->where('Customer_id', $session['account']['id'])->delete();
        $this->withSession($session)->get('/account')->assertRedirectTo('/login');
    }

    public function testLoginCreatesSessionWithoutPasswordAndLogoutClearsIt(): void
    {
        $this->accounts->create('customer', $this->input());
        $result = $this->submit('/login', ['identifier' => 'testuser', 'password' => 'OnlyForTests123!']);
        $result->assertRedirectTo('/account');
        $this->assertSame('customer', session()->get('account')['type']);
        $this->assertArrayNotHasKey('password', session()->get('account'));
        $session = $_SESSION;
        $this->withSession($session)->get('/account')->assertSee('Welcome, Test Customer');
        $this->submit('/logout', [], $session)->assertRedirectTo('/login');
        $this->assertNull(session()->get('account'));
    }

    public function testWrongPasswordAndUnknownAccountFail(): void
    {
        $this->accounts->create('customer', $this->input());
        $this->assertNull($this->accounts->authenticate('testuser', 'wrong'));
        $this->assertNull($this->accounts->authenticate('missing', 'wrong'));
    }

    public function testLegacyAmbiguousIdentifiersFailClosed(): void
    {
        $this->accounts->create('customer', $this->input());
        $row = $this->db->table('customers')->get()->getRowArray();
        unset($row['Customer_id']);
        $this->db->table('admins')->insert($row);
        $this->assertNull($this->accounts->authenticate('testuser', 'OnlyForTests123!'));
    }

    public function testDatabaseFailureDoesNotReportSuccessOrLeakDetails(): void
    {
        $this->db->query('DROP TABLE ' . $this->db->prefixTable('employees'));
        $result = $this->submit('/register/save', $this->input());
        $result->assertSessionHas('errors');
        $result->assertSessionMissing('success');
        $this->assertStringStartsWith('Account could not be created.', session()->get('errors')[0]);
    }

    public function testBootstrapRefusesWhenAdminExists(): void
    {
        $this->accounts->create('admin', $this->input(), true);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already exists');
        $this->accounts->create('admin', $this->input(['email' => 'second@example.com', 'username' => 'second']), true);
    }

    public function testLoginIsThrottledAfterFiveIdentifierAttempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->submit('/login', ['identifier' => 'missing', 'password' => 'wrong'])->assertRedirectTo('/login');
        }
        $this->submit('/login', ['identifier' => 'MISSING', 'password' => 'wrong'])->assertStatus(429);
    }

    public function testInvalidGenderDateAndMissingFieldsAreRejected(): void
    {
        foreach ([['gender' => 'forged'], ['birthdate' => '2026-02-30'], ['firstname' => ''], ['password' => 'short'], ['username' => 'bad@username']] as $override) {
            $this->submit('/register/save', $this->input($override))->assertSessionHas('errors');
        }
        $this->assertSame(0, $this->db->table('customers')->countAllResults());
    }

    public function testOverlongMultibytePasswordIsRejectedBeforeBcrypt(): void
    {
        $password = str_repeat('é', 40);
        $this->submit('/register/save', $this->input(['password' => $password, 'confirmPassword' => $password]))->assertSessionHas('errors');
        $this->assertSame(0, $this->db->table('customers')->countAllResults());
    }

    public function testAdminCanCreateAnotherAdminWithoutDepartment(): void
    {
        $session = $this->signedIn('admin');
        $this->submit('/admin/register', $this->input(['email' => 'second@example.com', 'username' => 'second']), $session)->assertSessionHas('success');
        $this->assertSame('admin', $this->accounts->authenticate('second', 'OnlyForTests123!')['role']);
        $this->assertSame(2, $this->db->table('admins')->countAllResults());
    }

    public function testHrCanCreateEmployeesButCannotPostAdminCreation(): void
    {
        $session = $this->signedIn('employee', ['department' => 'HR']);
        config('AccountAccess')->hrEmployeeIds = [$session['account']['id']];
        $this->submit('/employees/create', $this->input(['email' => 'new@example.com', 'username' => 'newstaff', 'department' => 'IT']), $session)->assertSessionHas('success');
        $this->submit('/admin/register', $this->input(), $session)->assertStatus(403);
        $this->assertSame(0, $this->db->table('admins')->countAllResults());
    }

    public function testGuestCannotPostEmployeeCreation(): void
    {
        $this->submit('/employees/create', $this->input(['department' => 'IT']))->assertRedirectTo('/login');
        $this->assertSame(0, $this->db->table('employees')->countAllResults());
    }

    public function testAbsoluteSessionLimitAppliesEvenWithRecentActivity(): void
    {
        $session = $this->signedIn();
        $session['signed_in_at'] = time() - 7201;
        $this->withSession($session)->get('/account')->assertRedirectTo('/login');
    }

    public function testLoginRotatesSessionId(): void
    {
        $this->accounts->create('customer', $this->input());
        session()->didRegenerate = false;
        $this->submit('/login', ['identifier' => 'testuser', 'password' => 'OnlyForTests123!']);
        $this->assertTrue(session()->didRegenerate);
    }

    public function testSafeInputIsEscapedAndPasswordsNotRepopulated(): void
    {
        $result = $this->withSession(['__ci_vars' => ['formInput' => 'new'], 'formInput' => ['firstname' => '"><script>alert(1)</script>', 'password' => 'secret-test-value']])->get('/register');
        $body = (string) $result->response()->getBody();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $body);
        $this->assertStringNotContainsString('secret-test-value', $body);
        $this->assertStringContainsString(esc('"><script>alert(1)</script>', 'attr'), $body);
        $this->assertStringContainsString('no-store', $result->response()->getHeaderLine('Cache-Control'));
    }

    public function testMissingLoginCsrfIsRejected(): void
    {
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->post('/login', ['identifier' => 'testuser', 'password' => 'wrong']);
    }

    public function testMissingLogoutCsrfDoesNotSignUserOut(): void
    {
        $session = $this->signedIn();
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->withSession($session)->post('/logout');
    }

    public function testGetCannotLogUserOut(): void
    {
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->withSession($this->signedIn())->get('/logout');
    }

    public function testAccountDatabaseFailureReturnsServiceUnavailable(): void
    {
        $session = $this->signedIn();
        $this->db->query('DROP TABLE ' . $this->db->prefixTable('customers'));
        $result = $this->withSession($session)->get('/account');
        $result->assertStatus(503);
        $this->assertSame('Account service is temporarily unavailable.', (string) $result->response()->getBody());
    }
}
