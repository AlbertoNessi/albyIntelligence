import spacy
import sys
import json
import re

# Load Spacy model
# Change to the medium model
nlp = spacy.load('en_core_web_md')
# Or use the large model
# nlp = spacy.load('en_core_web_lg')
# Or use the transformer model
# nlp = spacy.load('en_core_web_trf')

def correct_entity_type(text, entity_label):
    """
    Function to correct the entity label based on text patterns.
    """
    if re.match(r'^\d+$', text):
        return 'CARDINAL'
    elif re.match(r'^\S+@\S+\.\S+$', text):
        return 'EMAIL'
    elif re.match(r'^\+?[0-9\s\-\(\)]+$', text):
        return 'PHONE'
    # Add more corrections as needed
    return entity_label

def extract_entities(text):
    doc = nlp(text)
    entities = [(ent.text, correct_entity_type(ent.text, ent.label_)) for ent in doc.ents]
    return entities

if __name__ == "__main__":
    text = sys.argv[1]
    entities = extract_entities(text)
    print(json.dumps(entities))
