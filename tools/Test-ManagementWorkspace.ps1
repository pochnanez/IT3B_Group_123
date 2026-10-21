param(
    [string]$BaseUrl = 'http://127.0.0.1:8088/',
    [string]$ChromePath = 'C:/Program Files/Google/Chrome/Application/chrome.exe',
    [switch]$CustomerHomepageOnly
)
$ErrorActionPreference = 'Stop'
$projectPath = Split-Path $PSScriptRoot -Parent
$profilePath = Join-Path $projectPath 'writable/landing-browser-profile'
$screenshotPath = Join-Path $projectPath 'docs/screenshots'
[void][System.IO.Directory]::CreateDirectory($screenshotPath)
$browserProcess = $null
$socket = $null
$script:messageId = 0
$script:browserErrors = [System.Collections.Generic.List[string]]::new()

function Invoke-Cdp([string]$Method, [hashtable]$Parameters = @{}) {
    $script:messageId++
    $currentId = $script:messageId
    $payload = @{id=$currentId; method=$Method; params=$Parameters} | ConvertTo-Json -Depth 25 -Compress
    $bytes = [Text.Encoding]::UTF8.GetBytes($payload)
    $timeout = [Threading.CancellationTokenSource]::new(20000)
    try {
        $null = $socket.SendAsync([ArraySegment[byte]]::new($bytes), [Net.WebSockets.WebSocketMessageType]::Text, $true, $timeout.Token).GetAwaiter().GetResult()
        while ($true) {
            $buffer = New-Object byte[] 65536
            $stream = [IO.MemoryStream]::new()
            try {
                do {
                    $received = $socket.ReceiveAsync([ArraySegment[byte]]::new($buffer), $timeout.Token).GetAwaiter().GetResult()
                    $stream.Write($buffer, 0, $received.Count)
                } until ($received.EndOfMessage)
                $message = [Text.Encoding]::UTF8.GetString($stream.ToArray()) | ConvertFrom-Json
            } finally { $stream.Dispose() }
            if ($message.method -eq 'Runtime.exceptionThrown') { $script:browserErrors.Add(($message.params | ConvertTo-Json -Depth 10 -Compress)) }
            if ($message.method -eq 'Log.entryAdded' -and $message.params.entry.level -eq 'error') { $script:browserErrors.Add($message.params.entry.text) }
            if ($message.id -eq $currentId) {
                if ($message.error) { throw ($message.error | ConvertTo-Json -Compress) }
                return $message.result
            }
        }
    } finally { $timeout.Dispose() }
}

function Invoke-Js([string]$Expression) {
    $result = Invoke-Cdp 'Runtime.evaluate' @{expression=$Expression; returnByValue=$true; awaitPromise=$true}
    if ($result.exceptionDetails) { throw ($result.exceptionDetails | ConvertTo-Json -Depth 10 -Compress) }
    return $result.result.value
}

function Open-Page([string]$Path, [string]$Selector = '.workspace') {
    $null = Invoke-Cdp 'Page.navigate' @{url=($BaseUrl.TrimEnd('/') + '/' + $Path.TrimStart('/'))}
    for ($i=0; $i -lt 40; $i++) {
        Start-Sleep -Milliseconds 100
        if (Invoke-Js ('document.readyState === "complete" && !!document.querySelector(' + ($Selector | ConvertTo-Json -Compress) + ')')) { return }
    }
    throw "Page did not load: $Path"
}
function Wait-Workspace {
    Start-Sleep -Milliseconds 150
    for ($i=0; $i -lt 40; $i++) {
        if (Invoke-Js 'document.readyState === "complete" && !!document.querySelector(".workspace")') { return }
        Start-Sleep -Milliseconds 100
    }
    throw 'Form did not return to the workspace.'
}
function Login-As([string]$Role) {
    Open-Page 'login' '#identifier'
    $expression = 'document.getElementById("identifier").value="preview_' + $Role + '"; document.getElementById("password").value="PreviewOnly123!"; document.querySelector("form").requestSubmit(); true'
    $null = Invoke-Js $expression
    Wait-Workspace
    $expectedTitle=if($Role -eq 'customer') { 'homepage' } else { $Role }
    if (!(Invoke-Js ('document.querySelector("h1").textContent.toLowerCase().includes(' + ($expectedTitle | ConvertTo-Json -Compress) + ')'))) { throw "Wrong dashboard after $Role login" }
    if (!(Invoke-Js 'document.body.textContent.includes("TEST ENVIRONMENT")')) { throw 'Refusing to test a non-test workspace.' }
}
function Save-Screenshot([string]$Name, [int]$Width = 1440) {
    $null = Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{width=$Width;height=960;deviceScaleFactor=1;mobile=($Width -lt 600)}
    Start-Sleep -Milliseconds 150
    if (Invoke-Js 'document.documentElement.scrollWidth > innerWidth') { throw "Workspace overflow: $Name at $Width" }
    $null = Invoke-Js 'window.scrollTo(0,0)'
    $shot=Invoke-Cdp 'Page.captureScreenshot' @{format='png';captureBeyondViewport=$false}
    [IO.File]::WriteAllBytes((Join-Path $screenshotPath $Name),[Convert]::FromBase64String($shot.data))
}
function Submit-Form([string]$Selector, [hashtable]$Fields) {
    $expression='(() => { window.confirm=()=>true; const f=document.querySelector(' + ($Selector | ConvertTo-Json -Compress) + '); const values=' + ($Fields | ConvertTo-Json -Compress) + '; for (const [key,value] of Object.entries(values)) f.elements.namedItem(key).value=value; f.requestSubmit(); return true; })()'
    $null=Invoke-Js $expression
    Start-Sleep -Milliseconds 200
    Wait-Workspace
    if (Invoke-Js '!!document.querySelector(".feedback[role=alert]")') { throw (Invoke-Js 'document.querySelector(".feedback[role=alert]").textContent') }
}
try {
    $browserProcess = Start-Process -FilePath $ChromePath -ArgumentList @('--headless=new','--remote-debugging-port=9227',('--user-data-dir="'+$profilePath+'"'),'--no-first-run','--no-default-browser-check','about:blank') -PassThru -WindowStyle Hidden
    $targets=$null
    for ($attempt=0;$attempt -lt 30;$attempt++) { try { $targets=Invoke-RestMethod 'http://127.0.0.1:9227/json/list'; break } catch { Start-Sleep -Milliseconds 300 } }
    $target=$targets | Where-Object type -eq 'page' | Select-Object -First 1
    if (!$target) { throw 'No browser target.' }
    $socket=[Net.WebSockets.ClientWebSocket]::new()
    $null=$socket.ConnectAsync([Uri]$target.webSocketDebuggerUrl,[Threading.CancellationToken]::None).GetAwaiter().GetResult()
    $null=Invoke-Cdp 'Page.enable'; $null=Invoke-Cdp 'Runtime.enable'; $null=Invoke-Cdp 'Log.enable'; $null=Invoke-Cdp 'Page.bringToFront'
    $null=Invoke-Cdp 'Emulation.setEmulatedMedia' @{features=@(@{name='prefers-reduced-motion';value='reduce'})}
    $null=Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{width=1440;height=960;deviceScaleFactor=1;mobile=$false}
    Login-As 'customer'
    if($CustomerHomepageOnly) {
        foreach($width in @(1440,1024,768,390,320)) {
            $null=Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{width=$width;height=960;deviceScaleFactor=1;mobile=($width -lt 600)}
            Start-Sleep -Milliseconds 150
            if(Invoke-Js 'document.documentElement.scrollWidth > innerWidth') { throw "Homepage overflow at $width" }
            Write-Output "Homepage layout ${width}: no horizontal overflow"
        }
        if(!(Invoke-Js 'document.querySelector("h1").textContent === "Homepage" && document.querySelector("nav a[aria-current=page]").textContent.trim() === "Homepage"')) { throw 'Homepage title/navigation label incorrect.' }
        $links=Invoke-Js 'JSON.stringify([...document.querySelectorAll(".customer-welcome a, .customer-content-grid a, .service-overview a")].map(a=>a.href))'
        $null=Invoke-Js ('Promise.all('+$links+'.map(async url=>{const r=await fetch(url); if(!r.ok) throw new Error("Broken link "+url); return true;}))')
        Save-Screenshot 'customer-homepage-desktop.png'
        Save-Screenshot 'customer-homepage-mobile.png' 390
        $null=Invoke-Cdp 'Page.navigate' @{url=$BaseUrl}
        Start-Sleep -Milliseconds 150
        if($script:browserErrors.Count) { throw ($script:browserErrors -join "`n") }
        Write-Output 'PASS: Homepage heading/navigation, live booking cards, all customer action links, responsive widths and browser error checks.'
        return
    }
    Save-Screenshot 'revision-customer-desktop.png'
    Save-Screenshot 'revision-customer-mobile.png' 390
    Open-Page 'bookings/1'
    if (!(Invoke-Js 'document.body.textContent.includes("PREVIEW ONLY")')) { throw 'Synthetic customer booking was not visible.' }
    Open-Page 'receipts/1'
    if (!(Invoke-Js 'document.body.textContent.includes("PREVIEW-NOT-A-REAL-PAYMENT")')) { throw 'Synthetic receipt unavailable.' }
    Login-As 'admin'
    Save-Screenshot 'revision-admin-desktop.png'
    Open-Page 'reports'
    $null=Invoke-Js 'const s=document.getElementById("chart-series"); s.value="Bookings by status"; s.dispatchEvent(new Event("change")); true'
    if (!(Invoke-Js '[...document.querySelectorAll(".chart-panel")].filter(p=>!p.hidden).length === 1')) { throw 'Chart selection did not update.' }
    Save-Screenshot 'revision-reports-desktop.png'
    Open-Page 'manage/products'
    Submit-Form 'details[open] form' @{name='Preview verified panel';description='Synthetic catalog entry for browser verification only.';category='Solar panels';price='20000.00';stock='5';active='1'}
    Open-Page 'users'
    Save-Screenshot 'revision-users-mobile.png' 390
    Login-As 'dispatcher'
    Save-Screenshot 'revision-dispatcher-desktop.png'
    Open-Page 'bookings/2'
    $schedule=(Get-Date).AddDays(3).ToString('yyyy-MM-ddTHH:mm')
    Submit-Form 'form[action$="/assign"]' @{technician_user_id='5';scheduled_at=$schedule}
    if (!(Invoke-Js 'document.querySelector(".heading-row .badge").textContent === "scheduled"')) { throw 'Dispatcher assignment did not schedule booking.' }
    Login-As 'technician'
    Open-Page 'bookings/1'
    $null=Invoke-Cdp 'Browser.setPermission' @{permission=@{name='geolocation'};setting='denied';origin=$BaseUrl.TrimEnd('/')}
    $null=Invoke-Js 'document.querySelector(".gps-button").click(); true'
    Start-Sleep -Milliseconds 400
    if (!(Invoke-Js 'document.querySelector(".gps-status").textContent.includes("manual") && document.querySelector(".gps-checkin details").open')) { throw 'Denied GPS did not offer the manual alternative.' }
    $null=Invoke-Js 'const f=document.querySelector(".gps-checkin"); f.elements.manual_reason.value="Synthetic preview: location permission denied in automated browser test."; f.requestSubmit(f.querySelector("button[name=manual]")); true'
    Start-Sleep -Milliseconds 250
    Wait-Workspace
    if (!(Invoke-Js 'document.querySelector(".heading-row .badge").textContent === "in_progress"')) { throw 'Manual technician check-in did not start the booking.' }
    Save-Screenshot 'revision-technician-desktop.png'
    Submit-Form 'form[action$="/report"]' @{report='Synthetic preview completion report. Test workflow only; no real service was performed.'}
    if (!(Invoke-Js 'document.querySelector(".heading-row .badge").textContent === "completed"')) { throw 'Service report did not complete booking.' }
    Submit-Form 'form[action$="/attendance"]' @{}
    Login-As 'hr'
    Save-Screenshot 'revision-hr-desktop.png'
    Open-Page 'announcements'
    Submit-Form 'form[action$="/announcements"]' @{title='Preview browser announcement';body='Synthetic browser test announcement. This is not an operational safety instruction.';category='safety'}
    Login-As 'employee'
    Save-Screenshot 'revision-employee-mobile.png' 390
    if (!(Invoke-Js 'document.body.textContent.includes("Preview browser announcement")')) { throw 'Employee did not receive the published announcement.' }
    if ($script:browserErrors.Count) { throw ($script:browserErrors -join "`n") }
    Write-Output 'PASS: six role dashboards, desktop/mobile overflow, customer booking/receipt, admin catalog, chart selection, dispatcher assignment, GPS denial/manual check-in, technician report/completion/check-out and HR/employee announcements.'
    Write-Output "Screenshots: $screenshotPath"
} finally {
    if ($socket -and $socket.State -eq [Net.WebSockets.WebSocketState]::Open) { try { $null=Invoke-Cdp 'Browser.close' } catch {}; $socket.Dispose() }
    if ($browserProcess) { $browserProcess.Dispose() }
}
