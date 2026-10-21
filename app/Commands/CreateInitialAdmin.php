<?php
namespace App\Commands;

use App\Libraries\Accounts;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CreateInitialAdmin extends BaseCommand
{
    protected $group = 'Accounts';
    protected $name = 'accounts:initial-admin';
    protected $description = 'Create the first administrator from JSON on standard input; refuses if any admin exists.';

    public function run(array $params)
    {
        CLI::write('Reading account JSON from standard input. Do not put passwords in command arguments or files.');
        try {
            $input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($input)) {
                throw new \RuntimeException('Expected an account JSON object.');
            }
            $id = (new Accounts())->create('admin', $input, true);
            CLI::write('Initial administrator created (ID ' . $id . ').', 'green');
        } catch (\Throwable $e) {
            CLI::error(get_class($e) === \RuntimeException::class ? $e->getMessage() : 'Administrator could not be created. Check the account fields and database connection.');
            return EXIT_ERROR;
        }
        return EXIT_SUCCESS;
    }
}
