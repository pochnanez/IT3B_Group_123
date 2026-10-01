<?php

use App\Models\AdminModel;
use App\Models\CustomerModel;
use App\Models\EmployeeModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Database\AdminTable;

final class RegistrationRoutingTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $forge = \Config\Database::forge('tests');
        foreach (['customers' => 'customer_id', 'employees' => 'employee_id'] as $table => $primaryKey) {
            $forge->dropTable($table, true);
            $fields = [$primaryKey => ['type' => 'INTEGER', 'auto_increment' => true]];
            foreach (['first_name', 'middle_name', 'last_name', 'birthdate', 'gender',
                'email', 'contact_number', 'address', 'username', 'password'] as $field) {
                $fields[$field] = ['type' => 'TEXT'];
            }
            if ($table === 'employees') {
                $fields['department'] = ['type' => 'TEXT'];
            }
            $forge->addField($fields);
            $forge->addKey($primaryKey, true);
            $forge->createTable($table);
        }
        AdminTable::create();
    }

    public static function departments(): array
    {
        return [['N/A', 'customers'], ['Administration', 'employees'], ['IT', 'employees'], ['HR', 'employees']];
    }

    #[DataProvider('departments')]
    public function testNormalRegistrationStillUsesCustomerAndEmployeeTables(string $department, string $table): void
    {
        $this->post('register/save', [
            'firstname' => 'Juan', 'middlename' => 'Solar', 'lastname' => 'Dela Cruz',
            'birthdate' => '1990-05-20', 'gender' => 'Male', 'email' => 'juan@example.com',
            'contact' => '09123456789', 'address' => 'Solar Street', 'department' => $department,
            'username' => 'juan.user', 'password' => 'SolarPassword123!', 'confirmPassword' => 'SolarPassword123!',
        ])->assertRedirect();

        $customer = new CustomerModel();
        $employee = new EmployeeModel();
        $this->assertSame($table === 'customers' ? 1 : 0, $customer->countAllResults());
        $this->assertSame($table === 'employees' ? 1 : 0, $employee->countAllResults());
        $this->assertSame(0, (new AdminModel())->countAllResults());
        $row = $table === 'customers' ? $customer->first() : $employee->first();
        $this->assertTrue(password_verify('SolarPassword123!', $row['password']));
        $this->assertArrayNotHasKey('confirmPassword', $row);
        if ($table === 'employees') {
            $this->assertSame($department, $row['department']);
        }
    }

    public function testNormalViewKeepsItsOwnStylesAndSharedJavaScript(): void
    {
        $result = $this->get('register');
        $result->assertOK();
        $result->assertSee('css/R_Style.css');
        $result->assertSee('js/R_Script.js');
        $this->assertStringNotContainsString('admin/register', $result->getBody());
    }
}
