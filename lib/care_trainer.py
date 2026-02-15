#!/usr/bin/env python3
"""
Simple trainer for care-type classifier using scikit-learn.
Usage:
  python care_trainer.py train   # trains and saves lib/care_model.joblib
  python care_trainer.py predict --text "fever and cough"  # prints predicted label
"""
import sys
import os
import argparse
import joblib
from pathlib import Path
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.pipeline import make_pipeline
from sklearn.naive_bayes import MultinomialNB

ROOT = Path(__file__).parent
DATA = ROOT / 'data' / 'care_samples.csv'
MODEL = ROOT / 'care_model.joblib'

def train():
    if not DATA.exists():
        print('Dataset not found:', DATA)
        return 1
    df = pd.read_csv(DATA)
    X = df['symptoms'].astype(str).values
    y = df['care_type'].astype(str).values
    pipeline = make_pipeline(TfidfVectorizer(ngram_range=(1,2), max_features=2000), MultinomialNB())
    pipeline.fit(X, y)
    joblib.dump(pipeline, MODEL)
    print('Model trained and saved to', MODEL)
    return 0

def predict(text):
    if not MODEL.exists():
        print('Model not found. Run: python care_trainer.py train')
        return 2
    pipeline = joblib.load(MODEL)
    pred = pipeline.predict([text])[0]
    print(pred)
    return 0

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('action', choices=['train','predict'])
    parser.add_argument('--text', help='Text to predict')
    args = parser.parse_args()
    if args.action == 'train':
        return train()
    if args.action == 'predict':
        if not args.text:
            print('Provide --text')
            return 1
        return predict(args.text)

if __name__ == '__main__':
    raise SystemExit(main())
