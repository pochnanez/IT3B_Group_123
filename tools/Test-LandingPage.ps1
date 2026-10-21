param(
    [string]$BaseUrl = 'http://127.0.0.1:8087/',
    [string]$ChromePath = 'C:/Program Files/Google/Chrome/Application/chrome.exe'
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

try {
    # The isolated headless browser has no user profile, accounts or credentials.
    $browserProcess = Start-Process -FilePath $ChromePath -ArgumentList @('--headless=new', '--remote-debugging-port=9227', ('--user-data-dir="' + $profilePath + '"'), '--no-first-run', '--no-default-browser-check', 'about:blank') -PassThru -WindowStyle Hidden
    $targets = $null
    for ($attempt = 0; $attempt -lt 30; $attempt++) {
        try { $targets = Invoke-RestMethod 'http://127.0.0.1:9227/json/list'; break } catch { Start-Sleep -Milliseconds 300 }
    }
    $target = $targets | Where-Object type -eq 'page' | Select-Object -First 1
    if (!$target) { throw 'Headless Chrome did not expose a page.' }
    $socket = [Net.WebSockets.ClientWebSocket]::new()
    $null = $socket.ConnectAsync([Uri]$target.webSocketDebuggerUrl, [Threading.CancellationToken]::None).GetAwaiter().GetResult()
    $null = Invoke-Cdp 'Page.enable'
    $null = Invoke-Cdp 'Runtime.enable'
    $null = Invoke-Cdp 'Log.enable'
    $null = Invoke-Cdp 'Page.bringToFront'
    $null = Invoke-Cdp 'Emulation.setEmulatedMedia' @{features=@(@{name='prefers-reduced-motion';value='reduce'})}
    $null = Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{width=1440;height=1000;deviceScaleFactor=1;mobile=$false}
    $null = Invoke-Cdp 'Page.navigate' @{url=$BaseUrl}
    $ready = $false
    for ($attempt = 0; $attempt -lt 40; $attempt++) {
        Start-Sleep -Milliseconds 150
        $ready = Invoke-Js 'document.readyState === "complete" && !!document.querySelector(".hero")'
        if ($ready) { break }
    }
    if (!$ready) { throw 'Landing page did not finish loading.' }
    $null = Invoke-Js 'Promise.all([...document.images].map(i => i.decode().catch(() => null)))'
    $brokenImages = Invoke-Js 'JSON.stringify([...document.images].filter(i => !i.naturalWidth).map(i => i.src))'
    if ($brokenImages -ne '[]') { throw "Broken images: $brokenImages" }
    $missingAnchors = Invoke-Js 'JSON.stringify([...document.querySelectorAll("a[href^=\"#\"]")].filter(a => !document.getElementById(a.hash.slice(1))).map(a => a.hash))'
    if ($missingAnchors -ne '[]') { throw "Missing section anchors: $missingAnchors" }

    foreach ($width in @(1440, 1024, 768, 390, 320)) {
        $null = Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{width=$width;height=900;deviceScaleFactor=1;mobile=($width -lt 600)}
        Start-Sleep -Milliseconds 150
        $overflow = Invoke-Js 'document.documentElement.scrollWidth > window.innerWidth'
        if ($overflow) { throw "Horizontal overflow at width $width" }
        Write-Output "Layout width ${width}: no horizontal overflow"
        if ($width -eq 1440 -or $width -eq 390) {
            $null = Invoke-Js 'window.scrollTo(0,0)'
            $shot = Invoke-Cdp 'Page.captureScreenshot' @{format='png';captureBeyondViewport=$true;clip=@{x=0;y=0;width=$width;height=(Invoke-Js 'document.documentElement.scrollHeight');scale=1}}
            $fileName = if ($width -eq 1440) { 'home-desktop.png' } else { 'home-mobile.png' }
            [IO.File]::WriteAllBytes((Join-Path $screenshotPath $fileName), [Convert]::FromBase64String($shot.data))
            $fullName = $fileName.Replace('.png', '-full.png')
            Move-Item -LiteralPath (Join-Path $screenshotPath $fileName) -Destination (Join-Path $screenshotPath $fullName) -Force
            $shot = Invoke-Cdp 'Page.captureScreenshot' @{format='png';captureBeyondViewport=$false}
            [IO.File]::WriteAllBytes((Join-Path $screenshotPath $fileName), [Convert]::FromBase64String($shot.data))
        }
    }
    $null = Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{width=390;height=844;deviceScaleFactor=1;mobile=$true}
    if (!(Invoke-Js 'document.querySelector(".menu-toggle").click(); document.querySelector(".menu-toggle").getAttribute("aria-expanded") === "true" && getComputedStyle(document.querySelector(".site-navigation")).display !== "none"')) { throw 'Mobile menu did not open.' }
    $null = Invoke-Cdp 'Input.dispatchKeyEvent' @{type='keyDown';key='Escape';code='Escape';windowsVirtualKeyCode=27}
    if (!(Invoke-Js 'document.querySelector(".menu-toggle").getAttribute("aria-expanded") === "false" && document.activeElement === document.querySelector(".menu-toggle")')) { throw 'Escape did not close and focus the mobile menu.' }
    if (!(Invoke-Js 'document.querySelector(".menu-toggle").click(); document.querySelector(".nav-links a[href=\"#services\"]").click(); document.querySelector(".menu-toggle").getAttribute("aria-expanded") === "false" && document.activeElement.id === "services"')) { throw 'Section navigation did not close menu and move focus.' }
    $null = Invoke-Js 'document.querySelector(".faq summary").focus()'
    $null = Invoke-Cdp 'Input.dispatchKeyEvent' @{type='keyDown';key='Enter';code='Enter';windowsVirtualKeyCode=13;text="`r"}
    $null = Invoke-Cdp 'Input.dispatchKeyEvent' @{type='keyUp';key='Enter';code='Enter';windowsVirtualKeyCode=13}
    if (!(Invoke-Js 'document.querySelector(".faq").open')) { throw 'FAQ did not open using Enter.' }
    if (!(Invoke-Js 'document.querySelector(".product-details summary").click(); document.querySelector(".product-details").open')) { throw 'Product details did not open.' }
    $null = Invoke-Js 'document.querySelector(".service-details summary").click()'
    if (!(Invoke-Js 'document.querySelector(".service-details").open')) { throw 'Service details did not open.' }
    if (!(Invoke-Js 'const b=document.getElementById("booking-status").textContent; b.includes("not available yet") || b.includes("Online service requests are available")')) { throw 'Missing booking availability information.' }
    $null = Invoke-Cdp 'Page.navigate' @{url=$BaseUrl}
    Start-Sleep -Milliseconds 250
    if ($script:browserErrors.Count -gt 0) { throw ($script:browserErrors -join "`n") }
    Write-Output 'PASS: images, section links, menu open/Escape/link focus, keyboard FAQ, product/service details, booking disclosure and browser error checks.'
    $null = Invoke-Cdp 'Emulation.setScriptExecutionDisabled' @{value=$true}
    $null = Invoke-Cdp 'Page.navigate' @{url=$BaseUrl}
    Start-Sleep -Milliseconds 350
    if (!(Invoke-Js '!document.documentElement.classList.contains("js") && getComputedStyle(document.querySelector(".site-navigation")).display !== "none"')) { throw 'Navigation unavailable without JavaScript.' }
    if (!(Invoke-Js 'document.querySelector(".faq summary").click(); document.querySelector(".faq").open')) { throw 'FAQ unavailable without JavaScript.' }
    $null = Invoke-Cdp 'Emulation.setScriptExecutionDisabled' @{value=$false}
    $null = Invoke-Cdp 'Network.enable'
    $null = Invoke-Cdp 'Network.setCacheDisabled' @{cacheDisabled=$true}
    $null = Invoke-Cdp 'Network.setBlockedURLs' @{urls=@('*sssbg.png')}
    $null = Invoke-Cdp 'Page.navigate' @{url=$BaseUrl}
    Start-Sleep -Milliseconds 350
    if (!(Invoke-Js 'document.querySelector(".hero-art").complete && !document.querySelector(".hero-art").naturalWidth && document.querySelector(".hero-art").alt.length > 0 && document.querySelector(".hero-art-frame").getBoundingClientRect().height > 200 && document.querySelector(".hero-copy .button").getBoundingClientRect().width > 0')) { throw 'Hero image failure fallback was not preserved.' }
    Write-Output 'PASS: navigation/FAQ without JavaScript and deliberate hero image failure fallback.'
    Write-Output "Screenshots: $screenshotPath"
} finally {
    if ($socket -and $socket.State -eq [Net.WebSockets.WebSocketState]::Open) {
        try { $null = Invoke-Cdp 'Browser.close' } catch {}
        $socket.Dispose()
    }
    if ($browserProcess) { $browserProcess.Dispose() }
}
