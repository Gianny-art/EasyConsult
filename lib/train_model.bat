@echo off
setlocal enabledelayedexpansion

echo.
echo ========================================
echo  EasyConsult Care Classifier Training
echo  Windows Batch Script
echo ========================================
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python is not installed or not in PATH.
    echo Please install Python from https://www.python.org/downloads/
    echo Make sure to check "Add Python to PATH" during installation.
    pause
    exit /b 1
)

echo [OK] Python found
python --version

REM Navigate to the lib directory
cd /d "%~dp0"
echo [INFO] Working directory: %CD%

REM Check if requirements file exists
if not exist "requirements-care.txt" (
    echo [ERROR] requirements-care.txt not found in %CD%
    pause
    exit /b 1
)

echo [INFO] Installing Python dependencies...
pip install -r requirements-care.txt
if errorlevel 1 (
    echo [ERROR] Failed to install dependencies
    pause
    exit /b 1
)

REM Check if training data exists
if not exist "data\care_samples.csv" (
    echo [ERROR] Training data not found at data\care_samples.csv
    pause
    exit /b 1
)

echo [INFO] Training care classifier model...
echo [INFO] Input: data\care_samples.csv
python care_trainer.py train
if errorlevel 1 (
    echo [ERROR] Training failed
    pause
    exit /b 1
)

REM Verify model was created
if exist "care_model.joblib" (
    echo.
    echo [SUCCESS] Model trained successfully!
    echo [OK] Generated: care_model.joblib
    dir /s care_model.joblib
) else (
    echo [ERROR] Model file was not created
    pause
    exit /b 1
)

echo.
echo ========================================
echo  Training Complete
echo ========================================
echo The model is ready for predictions.
echo You can now use care_predict.php to classify symptoms.
echo.

pause
