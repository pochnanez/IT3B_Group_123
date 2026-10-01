<?php

use Tests\Support\Database\AdminTable;
use App\Models\AdminModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class AdminRegistrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        config('Security')->csrfProtection = 'session';
        AdminTable::create();
    }

    private function validData(): array
    {
        return [
            'firstname' => 'Maria', 'middlename' => '', 'lastname' => "Dela-Cruz",
            'birthdate' => '1995-06-12', 'gender' => 'Female',
            'email' => 'maria@example.com', 'contact' => '09123456789',
            'address' => '123 Solar Street',
            'username' => 'maria.admin', 'password' => 'SolarPassword123!',
            'confirmPassword' => 'SolarPassword123!',
        ];
    }

    private function submit(array $data)
    {
        $security = service('security');
        return $this->withHeaders([$security->getHeaderName() => $security->getHash()])
            ->post('admin/register/save', $data);
    }

    public function testViewReusesAssetsAndHasNoDepartmentOrRoleSelection(): void
    {
        $result = $this->get('admin/register');
        $result->assertOK();
        $result->assertSee('css/AR_Style.css');
        $result->assertSee('js/R_Script.js');
        $result->assertSee('images/logo.png');
        $result->assertSee('Admin Registration');
        $result->assertSee('Create an administrator account for authorized Sun Son Solar personnel.');
        $result->assertSee('Personal Information');
        $result->assertSee('Contact Information');
        $result->assertSee('Account Credentials');
        $result->assertSee('Create Admin Account');
        $this->assertStringNotContainsString('value="N/A"', $result->getBody());
        foreach (['department', 'role', 'account_type'] as $field) {
            $this->assertStringNotContainsString('name="' . $field . '"', $result->getBody());
        }
        $this->assertStringContainsString('csrf_test_name', $result->getBody());
    }

    public function testSuccessfulRegistrationStoresOnlyHash(): void
    {
        $this->submit($this->validData())->assertRedirect();
        $model = new AdminModel();
        $row = $model->first();
        $this->assertNotNull($row);
        $this->assertTrue(password_verify('SolarPassword123!', $row['password']));
        $this->assertNull($row['middle_name']);
        $this->assertArrayNotHasKey('created_at', $row);
        $this->assertArrayNotHasKey('confirmPassword', $row);
        $this->assertArrayNotHasKey('confirm_password', $row);
        $this->assertArrayNotHasKey('department', $row);
        $this->assertSame('Admin account created successfully.', session('success'));
    }

    public function testUnexpectedDepartmentIsIgnored(): void
    {
        $data = $this->validData();
        $data['department'] = 'N/A';
        $this->submit($data)->assertRedirect();
        $row = (new AdminModel())->first();
        $this->assertNotNull($row);
        $this->assertArrayNotHasKey('department', $row);
    }

    public function testInvalidDataPreservesOnlyNonSensitiveFields(): void
    {
        $data = $this->validData();
        $data['firstname'] = 'Maria123';
        $data['birthdate'] = date('Y-m-d', strtotime('+1 day'));
        $data['contact'] = '12345';
        $data['gender'] = 'invalid';
        $data['email'] = 'bad-email';
        $data['confirmPassword'] = 'different';
        $this->submit($data)->assertRedirect();
        $this->assertSame(0, (new AdminModel())->countAllResults());
        foreach (['firstname', 'birthdate', 'contact', 'gender', 'email', 'confirmPassword'] as $field) {
            $this->assertArrayHasKey($field, session('admin_errors'));
        }
        $this->assertSame('Maria123', session('admin_old')['firstname']);
        $this->assertArrayNotHasKey('password', session('admin_old'));
        $this->assertArrayNotHasKey('confirmPassword', session('admin_old'));
    }

    public function testDuplicateEmailAndUsernameCannotCreateAnotherAdmin(): void
    {
        $data = $this->validData();
        $this->submit($data)->assertRedirect();
        $this->submit($data)->assertRedirect();
        $this->assertSame(1, (new AdminModel())->countAllResults());
        $this->assertArrayHasKey('email', session('admin_errors'));
        $this->assertArrayHasKey('username', session('admin_errors'));
    }

    public function testMissingCsrfTokenCannotCreateAnAccount(): void
    {
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->post('admin/register/save', $this->validData());
    }

    public function testMalformedPostAndInvalidCalendarDateAreRejected(): void
    {
        $data = $this->validData();
        $data['firstname'] = ['unexpected' => 'array'];
        $data['birthdate'] = '2025-02-30';
        $data['address'] = '   ';
        $data['username'] = 'abc';
        $data['password'] = 'short';
        $this->submit($data)->assertRedirect();
        foreach (['firstname', 'birthdate', 'address', 'username', 'password'] as $field) {
            $this->assertArrayHasKey($field, session('admin_errors'));
        }
        $this->assertSame(0, (new AdminModel())->countAllResults());
    }

    public function testUnicodeNamesAndOptionalMiddleNameAreAccepted(): void
    {
        $data = $this->validData();
        $data['firstname'] = 'José';
        $data['middlename'] = 'María';
        $data['lastname'] = "O’Neill";
        $this->submit($data)->assertRedirect();
        $this->assertSame('José', (new AdminModel())->first()['first_name']);
    }

    public function testDatabaseUniqueConstraintPreventsBypassingDuplicateCheck(): void
    {
        $this->submit($this->validData());
        $model = new AdminModel();
        $row = $model->first();
        unset($row['admin_id']);
        $this->expectException(\CodeIgniter\Database\Exceptions\DatabaseException::class);
        $model->insert($row);
    }

    public function testErrorPageEscapesInputAndNeverRefillsPasswords(): void
    {
        $data = $this->validData();
        $data['firstname'] = '<script>alert(1)</script>';
        $this->submit($data);
        $result = $this->withSession()->get('admin/register');
        $this->assertStringNotContainsString('<script>alert(1)</script>', $result->getBody());
        $this->assertStringContainsString('&lt;script&gt;', $result->getBody());
        $this->assertStringNotContainsString('SolarPassword123!', $result->getBody());
    }
}
