@echo off
REM Git Pre-commit Hook Installer for AI Code Detection (Windows)
REM This script installs the AI detection pre-commit hook

echo ============================================
echo   AI Code Detection - Hook Installer
echo ============================================
echo.

REM Get script directory
set SCRIPT_DIR=%~dp0

REM Check if we're in a git repository
if not exist ".git" (
    echo ERROR: Not in a git repository!
    pause
    exit /b 1
)

echo Installing pre-commit hook...

REM Copy the pre-commit hook
copy "%SCRIPT_DIR%.git\hooks\pre-commit" ".git\hooks\pre-commit" /Y

if exist ".git\hooks\pre-commit" (
    echo.
    echo ✓ Pre-commit hook installed successfully!
    echo.
    echo The hook will:
    echo   • Analyze PHP files before each commit
    echo   • Detect AI-generated code patterns
    echo   • Block commits with critical AI errors
    echo   • Warn about high AI pattern counts
    echo.
    echo To skip the hook on a specific commit, use:
    echo   git commit --no-verify
    echo.
) else (
    echo ERROR: Failed to install hook!
    pause
    exit /b 1
)

pause

