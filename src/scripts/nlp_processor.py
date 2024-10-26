import spacy
import sys
import json
import re
from typing import List, Tuple

# Precompile regex patterns for efficiency
ENTITY_PATTERNS: List[Tuple[re.Pattern, str]] = [
    (re.compile(r'^\d+$'), 'CARDINAL'),
    (re.compile(r'^\S+@\S+\.\S+$'), 'EMAIL'),
    (re.compile(r'^\+?[0-9\s\-\(\)]+$'), 'PHONE'),
    # Add more patterns as needed
]

def load_spacy_model(model_name: str = 'en_core_web_md') -> spacy.Language:
    """
    Load the specified Spacy model.
    """
    try:
        return spacy.load(model_name)
    except OSError as e:
        sys.stderr.write(f"Error loading Spacy model '{model_name}': {e}\n")
        sys.exit(1)

# Load Spacy model once
nlp = load_spacy_model()

def correct_entity_type(text: str, entity_label: str) -> str:
    """
    Correct the entity label based on predefined text patterns.
    """
    for pattern, label in ENTITY_PATTERNS:
        if pattern.match(text):
            return label
    return entity_label

def extract_entities(text: str) -> List[Tuple[str, str]]:
    """
    Extract entities from text and correct their labels.
    """
    doc = nlp(text)
    return [(ent.text, correct_entity_type(ent.text, ent.label_)) for ent in doc.ents]

def main():
    if len(sys.argv) < 2:
        sys.stderr.write("Usage: python script.py <text>\n")
        sys.exit(1)
    text = sys.argv[1]
    entities = extract_entities(text)
    print(json.dumps(entities))

if __name__ == "__main__":
    main()
