$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$ProjectDir = $PSScriptRoot
$PhpVersion = if ($env:PHP_VERSION) { $env:PHP_VERSION } else { '8.5' }
$Tokens = @($args)

if ($Tokens.Count -gt 0 -and $Tokens[0] -in @('--php', '-PhpVersion')) {
    if ($Tokens.Count -lt 2 -or [string]::IsNullOrWhiteSpace($Tokens[1])) {
        throw 'Missing version after --php or -PhpVersion.'
    }

    $PhpVersion = $Tokens[1]
    $Tokens = if ($Tokens.Count -gt 2) { @($Tokens[2..($Tokens.Count - 1)]) } else { @() }
}

$Image = "laravel-ecommerce-dev:php-$PhpVersion"
$ComposerCacheHost = if ($env:COMPOSER_CACHE_HOST) {
    $env:COMPOSER_CACHE_HOST
} else {
    Join-Path $HOME '.cache\composer'
}

function Show-Help {
    Write-Host "Laravel Ecommerce container shell (PHP $PhpVersion)" -ForegroundColor Green
    Write-Host '-------------------------------------------------------------------------------'
    Write-Host 'Usage:' -ForegroundColor Yellow
    Write-Host '  .\app.ps1 [--php version] [command] [arguments]'
    Write-Host ''
    Write-Host 'Commands:' -ForegroundColor Yellow

    $commands = @(
        @('build [--force]', 'Build the local PHP tool image'),
        @('check', 'Run Composer validation, tests and PHPStan'),
        @('test [arguments]', 'Run PHPUnit'),
        @('stan [arguments]', 'Run PHPStan'),
        @('php-cs-fixer [arguments]', 'Run PHP CS Fixer and fix code'),
        @('composer-install [arguments]', 'Run composer install'),
        @('composer-update [arguments]', 'Run composer update'),
        @('composer-validate [arguments]', 'Run composer validate --strict'),
        @('composer-outdated [arguments]', 'List outdated Composer dependencies'),
        @('composer [arguments]', 'Run any Composer command'),
        @('php [arguments]', 'Run any PHP command'),
        @('php-shell', 'Open an interactive shell'),
        @('help', 'Display this help screen')
    )

    foreach ($item in $commands) {
        Write-Host ('  {0,-34} {1}' -f $item[0], $item[1])
    }

    Write-Host ''
    Write-Host 'Examples:' -ForegroundColor Yellow
    Write-Host '  .\app.ps1 test --filter CartTest'
    Write-Host '  .\app.ps1 stan'
    Write-Host '  .\app.ps1 composer require vendor/package'
    Write-Host '  .\app.ps1 --php 8.4 test'
}

function Invoke-Docker {
    param(
        [Parameter(Mandatory)]
        [string[]] $DockerArguments
    )

    & docker @DockerArguments

    if ($LASTEXITCODE -ne 0) {
        throw "Docker exited with code $LASTEXITCODE."
    }
}

function Build-Image {
    param([string[]] $BuildArguments = @())

    $dockerArguments = @(
        'build',
        '--build-arg', "PHP_VERSION=$PhpVersion",
        '--file', (Join-Path $ProjectDir 'docker\Dockerfile'),
        '--tag', $Image
    )

    if ($BuildArguments.Count -gt 0) {
        if ($BuildArguments.Count -ne 1 -or $BuildArguments[0] -ne '--force') {
            throw "Unknown build option: $($BuildArguments -join ' ')"
        }

        $dockerArguments += @('--no-cache', '--pull')
    }

    $dockerArguments += (Join-Path $ProjectDir 'docker')

    Write-Host "Building $Image" -ForegroundColor Green
    Invoke-Docker -DockerArguments $dockerArguments
}

function Confirm-Image {
    $previousErrorActionPreference = $ErrorActionPreference

    try {
        $ErrorActionPreference = 'SilentlyContinue'
        & docker image inspect $Image 1>$null 2>$null
        $imageExists = $LASTEXITCODE -eq 0
    } finally {
        $ErrorActionPreference = $previousErrorActionPreference
    }

    if (-not $imageExists) {
        Build-Image
    }
}

function Get-ContainerArguments {
    return @(
        'run', '--rm',
        '--env', 'HOME=/tmp',
        '--env', 'COMPOSER_HOME=/tmp/.composer',
        '--env', 'COMPOSER_CACHE_DIR=/.composer/cache',
        '--env', 'COMPOSER_ALLOW_SUPERUSER=1',
        '--volume', "${ProjectDir}:/app",
        '--volume', "${ComposerCacheHost}:/.composer/cache",
        '--workdir', '/app'
    )
}

function Invoke-Container {
    param(
        [Parameter(Mandatory)]
        [string[]] $ContainerArguments
    )

    Confirm-Image
    New-Item -ItemType Directory -Force -Path $ComposerCacheHost | Out-Null

    $dockerArguments = @(Get-ContainerArguments) + @($Image) + $ContainerArguments
    Invoke-Docker -DockerArguments $dockerArguments
}

function Confirm-Dependencies {
    if (-not (Test-Path (Join-Path $ProjectDir 'vendor\autoload.php'))) {
        Write-Host 'vendor/autoload.php is missing; running composer install first.' -ForegroundColor Yellow
        Invoke-Container -ContainerArguments @(
            'composer', 'install', '--no-interaction', '--prefer-dist', '--no-progress'
        )
    }
}

function Invoke-Tests {
    param([string[]] $ToolArguments = @())

    Confirm-Dependencies
    Invoke-Container -ContainerArguments (@('php', 'vendor/bin/phpunit') + $ToolArguments)
}

function Invoke-Stan {
    param([string[]] $ToolArguments = @())

    Confirm-Dependencies
    Invoke-Container -ContainerArguments (@('php', 'vendor/bin/phpstan', 'analyse', '--no-progress') + $ToolArguments)
}

function Invoke-CodeStyleFixer {
    param([string[]] $ToolArguments = @())

    Confirm-Dependencies
    Invoke-Container -ContainerArguments (@(
        'php', 'vendor/bin/php-cs-fixer', 'fix', '--allow-risky=yes'
    ) + $ToolArguments)
}

function Invoke-Checks {
    Invoke-Container -ContainerArguments @('composer', 'validate', '--strict')
    Invoke-Tests
    Invoke-Stan
}

$Command = if ($Tokens.Count -gt 0) { $Tokens[0] } else { 'help' }
$CommandArguments = if ($Tokens.Count -gt 1) { @($Tokens[1..($Tokens.Count - 1)]) } else { @() }

switch ($Command) {
    'build' {
        Build-Image -BuildArguments $CommandArguments
    }
    'check' {
        Invoke-Checks
    }
    'test' {
        Invoke-Tests -ToolArguments $CommandArguments
    }
    'stan' {
        Invoke-Stan -ToolArguments $CommandArguments
    }
    'php-cs-fixer' {
        Invoke-CodeStyleFixer -ToolArguments $CommandArguments
    }
    'composer-install' {
        Invoke-Container -ContainerArguments (@(
            'composer', 'install', '--no-interaction', '--prefer-dist'
        ) + $CommandArguments)
    }
    'composer-update' {
        Invoke-Container -ContainerArguments (@(
            'composer', 'update', '--with-all-dependencies'
        ) + $CommandArguments)
    }
    'composer-validate' {
        Invoke-Container -ContainerArguments (@('composer', 'validate', '--strict') + $CommandArguments)
    }
    'composer-outdated' {
        Invoke-Container -ContainerArguments (@('composer', 'outdated') + $CommandArguments)
    }
    'composer' {
        Invoke-Container -ContainerArguments (@('composer') + $CommandArguments)
    }
    'php' {
        Invoke-Container -ContainerArguments (@('php') + $CommandArguments)
    }
    'php-shell' {
        Confirm-Image
        New-Item -ItemType Directory -Force -Path $ComposerCacheHost | Out-Null
        $dockerArguments = @(Get-ContainerArguments) + @('-it', $Image, 'bash')
        Invoke-Docker -DockerArguments $dockerArguments
    }
    { $_ -in @('help', '-h', '--help') } {
        Show-Help
    }
    default {
        Write-Host "Unknown command: $Command" -ForegroundColor Red
        Write-Host ''
        Show-Help
        exit 1
    }
}
