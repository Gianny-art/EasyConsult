# EasyConsult ML Setup Guide

## Overview
This guide explains how to set up and train the AI care classifier for EasyConsult. The system uses scikit-learn (TF-IDF + Multinomial Naive Bayes) to classify patient symptoms and route them to appropriate care types.

## Architecture
- **Backend**: PHP wrapper (`lib/ai_care_predict.php`) calls Python trainer script
- **ML Pipeline**: `lib/care_trainer.py` handles training and predictions
- **Data**: `lib/data/care_samples.csv` contains labeled symptom-to-care mapping
- **Model**: `lib/care_model.joblib` (binary serialized scikit-learn model)

## Prerequisites

### Windows
1. **Python 3.8+** installed and added to PATH
   - Download from https://www.python.org/downloads/
   - **IMPORTANT**: Check "Add Python to PATH" during installation
   - Verify: Open PowerShell and run `python --version`

2. **pip** (comes with Python)
   - Verify: `pip --version`

### macOS / Linux
1. **Python 3.8+** (usually pre-installed)
   - Verify: `python3 --version`
   - If missing: `brew install python3` (macOS) or `sudo apt-get install python3` (Linux)

2. **pip** package manager
   - Verify: `pip3 --version`

## Installation

### Step 1: Navigate to Project Directory
```bash
# Windows (PowerShell)
cd C:\wamp64\www\EasyConsult\lib

# macOS / Linux
cd /var/www/EasyConsult/lib
```

### Step 2: Install Python Dependencies
```bash
# Windows
pip install -r requirements-care.txt

# macOS / Linux
pip3 install -r requirements-care.txt
```

Dependencies installed:
- **pandas**: Data processing (CSV reading/writing)
- **scikit-learn**: ML algorithms (TF-IDF, Naive Bayes)
- **joblib**: Model serialization (save/load)

### Step 3: Train the Model

#### Windows (Quick Method - Double-click)
1. Navigate to `C:\wamp64\www\EasyConsult\lib\`
2. Double-click `train_model.bat`
3. Watch the output; when complete, you'll see "Model trained successfully!"
4. Verify `care_model.joblib` exists in the lib directory

#### Windows (Manual Command)
```bash
cd C:\wamp64\www\EasyConsult\lib
python care_trainer.py train
```

#### macOS / Linux (Bash/Zsh)
```bash
cd /var/www/EasyConsult/lib
python3 care_trainer.py train
```

### Step 4: Verify Installation
Check that these files exist in `lib/`:
- ✅ `care_trainer.py` (trainer script)
- ✅ `care_model.joblib` (generated model)
- ✅ `data/care_samples.csv` (training data)
- ✅ `requirements-care.txt` (dependencies)
- ✅ `ai_care_predict.php` (PHP wrapper)

## Using the Model

### PHP Integration
The model is exposed via `lib/ai_care_predict.php`. Call it via POST:

```bash
curl -X POST http://localhost/EasyConsult/lib/ai_care_predict.php \
  -H "Content-Type: application/json" \
  -d '{"text": "Douleur thoracique, essoufflement"}'
```

Expected response:
```json
{
  "prediction": "cardiac_emergency",
  "confidence": 0.92
}
```

### Web Integration
From JavaScript (e.g., `public/assistant.php`):

```javascript
fetch('/EasyConsult/lib/ai_care_predict.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ text: 'Patient symptoms here' })
})
.then(r => r.json())
.then(data => {
  console.log('Care type:', data.prediction);
  // Route to appropriate department
});
```

## Dataset Update

### Adding New Training Samples
Edit `lib/data/care_samples.csv`:
```csv
symptoms,care_type
"Toux sèche, fièvre légère, mal de gorge","respiratory_infection"
"Douleur thoracique, essoufflement","cardiac_emergency"
... (add more rows)
```

**Format Rules:**
- Column 1: `symptoms` (comma-separated French symptom descriptions)
- Column 2: `care_type` (predefined category, see list below)
- Quote all entries: `"symptom1, symptom2"`
- One sample per line

### Supported Care Types
- `respiratory_infection` - Respiratory/lung diseases
- `cardiac_emergency` - Heart/chest emergencies
- `hypertension_management` - Blood pressure management
- `urgent_surgery` - Surgical emergencies
- `bleeding_emergency` - Severe bleeding
- `trauma_management` - Injuries/trauma
- `gastroenteritis` - Digestive issues
- `allergy` - Non-urgent allergies
- `allergy_urgent` - Anaphylaxis/severe allergies
- `neurology_consultation` - Neurological disorders
- `stroke_emergency` - Stroke/neurological emergency
- `neurology_urgent` - Urgent neurological
- `orthopedics_consultation` - Bone/joint issues
- `infectious_disease` - Infectious diseases (malaria, typhoid, etc.)
- `pediatric_infectious` - Children's infectious diseases
- `pediatric_respiratory` - Children's respiratory
- `dermatology_consultation` - Skin conditions
- `urology_consultation` - Urinary/prostate issues

### Retraining After Updates
After adding samples:

**Windows:**
```bash
python care_trainer.py train
```

**macOS / Linux:**
```bash
python3 care_trainer.py train
```

Model automatically updates `lib/care_model.joblib`.

## Troubleshooting

### "Python not found"
- **Windows**: Reinstall Python, ensuring "Add Python to PATH" is checked
- **macOS/Linux**: Use `which python3` to verify installation path

### "ModuleNotFoundError: No module named 'pandas'"
- Run: `pip install -r requirements-care.txt` again
- Verify pip points to correct Python: `pip --version`

### "care_model.joblib not found"
- Model hasn't been trained yet
- Run: `python care_trainer.py train`
- Check for errors in output

### "ImportError in ai_care_predict.php"
- Verify Python path in `ai_care_predict.php` line: `exec('python ...')`
- Windows may need full path: `exec('C:\\Python39\\python.exe ...')`
- Check PHP error logs for details

### Model predictions are poor quality
- Add more training samples (currently 45 samples)
- Ensure symptom descriptions are accurate French medical terms
- Retrain: `python care_trainer.py train`

## Production Deployment

### Recommended Steps
1. **Train model locally** and commit `care_model.joblib` to repository
2. **On server**: Verify Python 3.8+ installed, run `pip install -r requirements-care.txt`
3. **Test** via `ai_care_predict.php` endpoint
4. **Monitor** predictions; add new samples if accuracy drops

### Performance Notes
- Model loads in ~50ms (cached after first call)
- Prediction time: ~5ms per symptom string
- **DO NOT** retrain during active patient sessions (use async queues for production)

### Security
- `ai_care_predict.php` does NOT validate user input extensively
- In production, add: CSP headers, rate limiting, input sanitization
- Avoid exposing model internals; only return `prediction` field to user

## Questions?
Refer to `lib/care_trainer.py` source code for ML pipeline details.
