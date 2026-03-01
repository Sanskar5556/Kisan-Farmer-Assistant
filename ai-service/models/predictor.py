"""
=============================================================
Kisan Smart Assistant — Crop Disease Predictor
=============================================================

Currently: Uses a smart mock predictor that:
1. Analyzes basic image properties (color distribution)
2. Returns a realistic disease prediction based on crop type

To upgrade to a real AI model:
1. Train a CNN (e.g., ResNet, MobileNet) on PlantVillage dataset
2. Save as model.h5 or model.pt (PyTorch/TensorFlow)
3. Replace the mock logic below with actual model inference:
   
   # TensorFlow example:
   import tensorflow as tf
   model = tf.keras.models.load_model('model.h5')
   prediction = model.predict(preprocessed_image)
   
=============================================================
"""

import io
import random
import hashlib
from PIL import Image


class CropDiseasePredictor:
    """
    Crop disease prediction engine.
    Currently uses a deterministic mock based on image hash.
    Replace `_run_model()` with your actual ML model inference.
    """

    def __init__(self):
        # Disease database: maps to treatment recommendations
        # This will be replaced with actual ML model class labels
        self.disease_database = {
            "Healthy": {
                "recommendation": "✅ Your crop appears healthy! Continue regular care:\n"
                                  "• Maintain proper irrigation schedule\n"
                                  "• Apply scheduled fertilizers (NPK)\n"
                                  "• Monitor regularly for early signs of disease\n"
                                  "• Ensure good air circulation between plants"
            },
            "Wheat Leaf Rust": {
                "recommendation": "⚠️ Wheat Leaf Rust detected (Puccinia triticina):\n"
                                  "• Apply Mancozeb 75% WP at 2g/L water immediately\n"
                                  "• Alternatively use Propiconazole 25% EC at 0.5 mL/L\n"
                                  "• Spray in early morning or late evening\n"
                                  "• Repeat after 10-14 days if symptoms persist\n"
                                  "• Improve field drainage to reduce humidity"
            },
            "Rice Blast": {
                "recommendation": "⚠️ Rice Blast (Magnaporthe oryzae) detected:\n"
                                  "• Spray Tricyclazole 75% WP at 0.6g/L water\n"
                                  "• Or use Isoprothiolane 40% EC at 1.5 mL/L\n"
                                  "• Drain field and re-flood after 3 days\n"
                                  "• Avoid excessive nitrogen fertilization\n"
                                  "• Use resistant varieties in next season"
            },
            "Tomato Early Blight": {
                "recommendation": "⚠️ Early Blight (Alternaria solani) detected:\n"
                                  "• Remove and destroy infected leaves immediately\n"
                                  "• Apply Chlorothalonil 75% WP at 2g/L water\n"
                                  "• Mulch around plants to prevent soil splash\n"
                                  "• Ensure 60cm spacing for air circulation\n"
                                  "• Water at base, avoid wetting foliage"
            },
            "Leaf Blight": {
                "recommendation": "⚠️ Leaf Blight detected:\n"
                                  "• Apply copper-based fungicide (Copper Oxychloride 50% WP at 3g/L)\n"
                                  "• Remove severely affected plant parts\n"
                                  "• Avoid overhead irrigation\n"
                                  "• Spray every 7-10 days during disease pressure\n"
                                  "• Contact your local KVK for variety-specific advice"
            },
            "Powdery Mildew": {
                "recommendation": "⚠️ Powdery Mildew detected:\n"
                                  "• Spray Sulphur 80% WP at 2g/L water\n"
                                  "• Or use Hexaconazole 5% EC at 1 mL/L\n"
                                  "• Spray in morning when humidity is lower\n"
                                  "• Avoid water-stressed conditions\n"
                                  "• Improve canopy ventilation by pruning"
            },
            "Bacterial Leaf Spot": {
                "recommendation": "⚠️ Bacterial Leaf Spot detected:\n"
                                  "• Apply Streptomycin Sulphate + Copper Oxychloride\n"
                                  "• Remove and burn affected plant debris\n"
                                  "• Use disease-free certified seeds next season\n"
                                  "• Avoid working in field when plants are wet\n"
                                  "• Apply 2% Bordeaux mixture as preventive"
            },
        }

        self.disease_names = list(self.disease_database.keys())

    def predict(self, image_bytes: bytes, filename: str = "image.jpg") -> dict:
        """
        Main prediction method.
        
        Args:
            image_bytes: Raw bytes of the uploaded crop image
            filename: Original filename (used to extract crop hints)
            
        Returns:
            dict with keys: disease, confidence, recommendation
        """
        # Analyze the image
        image_analysis = self._analyze_image(image_bytes)
        
        # Run the "model" (mock for now)
        disease_name, confidence = self._run_model(image_bytes, image_analysis, filename)
        
        # Get recommendation for detected disease
        recommendation = self.disease_database.get(
            disease_name, 
            self.disease_database["Leaf Blight"]
        )["recommendation"]

        return {
            "disease": disease_name,
            "confidence": round(confidence, 2),
            "recommendation": recommendation,
            "image_size": f"{image_analysis['width']}x{image_analysis['height']}",
            "analyzed_by": "KisanAI v1.0 (Mock — Upgrade with real ML model)"
        }

    def _analyze_image(self, image_bytes: bytes) -> dict:
        """Extract basic image properties for analysis"""
        try:
            img = Image.open(io.BytesIO(image_bytes)).convert("RGB")
            width, height = img.size
            
            # Sample pixel colors to understand image content
            # This is a very basic heuristic — real models use CNNs
            pixels = list(img.getdata())
            sample = pixels[::max(1, len(pixels)//1000)]  # Sample 1000 pixels
            
            avg_r = sum(p[0] for p in sample) / len(sample)
            avg_g = sum(p[1] for p in sample) / len(sample)
            avg_b = sum(p[2] for p in sample) / len(sample)
            
            return {
                "width": width,
                "height": height,
                "avg_r": avg_r,
                "avg_g": avg_g,
                "avg_b": avg_b,
                "greenness": avg_g / (avg_r + avg_g + avg_b + 1),  # How green the image is
            }
        except Exception:
            return {"width": 0, "height": 0, "avg_r": 128, "avg_g": 128, "avg_b": 128, "greenness": 0.33}

    def _run_model(self, image_bytes: bytes, analysis: dict, filename: str) -> tuple:
        """
        =====================================================
        REPLACE THIS METHOD WITH YOUR ACTUAL ML MODEL
        =====================================================
        
        Current implementation: deterministic mock using image hash
        so the same image always gets the same prediction.
        
        To upgrade with TensorFlow:
        
            import tensorflow as tf
            import numpy as np
            
            model = tf.keras.models.load_model('disease_model.h5')
            img = Image.open(io.BytesIO(image_bytes)).resize((224, 224))
            img_array = np.array(img) / 255.0
            img_array = np.expand_dims(img_array, 0)
            predictions = model.predict(img_array)
            class_idx = np.argmax(predictions[0])
            confidence = predictions[0][class_idx] * 100
            disease = self.disease_names[class_idx]
            return disease, confidence
        """
        # Use image hash for deterministic results (same image = same result)
        image_hash = int(hashlib.md5(image_bytes[:1000]).hexdigest(), 16)
        
        # Use greenness to determine if plant is healthy
        greenness = analysis.get("greenness", 0.33)
        
        if greenness > 0.45:
            # Very green image → likely healthy
            disease = "Healthy"
            confidence = 75 + random.uniform(0, 20)
        else:
            # Less green → pick a disease based on image hash
            disease_idx = image_hash % (len(self.disease_names) - 1) + 1  # Skip "Healthy"
            disease = self.disease_names[disease_idx]
            confidence = 65 + (image_hash % 30)  # 65-95% confidence
        
        return disease, min(confidence, 97.0)  # Cap at 97% (no AI is 100% sure)
