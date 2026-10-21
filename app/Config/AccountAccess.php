<?php
namespace Config;
use CodeIgniter\Config\BaseConfig;
class AccountAccess extends BaseConfig
{
    // Initial migration/bootstrap HR assignments only. After migration, use the
    // protected administrator user-management screen. Departments never grant roles.
    public array $hrEmployeeIds = [];
    public int $idleSeconds = 1800;
    public int $absoluteSeconds = 7200;
    public bool $preview = false;
}
