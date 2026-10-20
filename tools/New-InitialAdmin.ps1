# Run from the project directory. No credentials are saved to disk or arguments.
$ErrorActionPreference = 'Stop'
$accountDetails = @{}
foreach ($fieldName in @('firstname', 'middlename', 'lastname', 'birthdate', 'gender', 'email', 'contact', 'address', 'username')) {
    $accountDetails[$fieldName] = Read-Host $fieldName
}
$securePassword = Read-Host 'Password (8 characters minimum, 72 bytes maximum)' -AsSecureString
$secureConfirmation = Read-Host 'Confirm password' -AsSecureString
try {
    $accountDetails.password = [System.Net.NetworkCredential]::new('', $securePassword).Password
    $accountDetails.confirmPassword = [System.Net.NetworkCredential]::new('', $secureConfirmation).Password
    $previousEncoding = $OutputEncoding
    $OutputEncoding = [System.Text.UTF8Encoding]::new($false)
    $accountDetails | ConvertTo-Json -Compress | & php spark accounts:initial-admin
    if ($LASTEXITCODE -ne 0) { throw 'Initial administrator was not created.' }
} finally {
    if ($null -ne $previousEncoding) { $OutputEncoding = $previousEncoding }
    $accountDetails.Clear()
    $securePassword.Dispose()
    $secureConfirmation.Dispose()
}
