"""
=============================================================
Kisan Smart Assistant — AI Microservice (FastAPI)
=============================================================

What this does:
- Receives a crop image from the Laravel backend
- Analyzes it (using a mock AI for now)
- Returns: disease name, confidence score, recommendation

How to run:
    pip install -r requirements.txt
    uvicorn main:app --reload --host 0.0.0.0 --port 8001

How to test manually:
    curl -X POST "http://localhost:8001/predict" \
         -F "file=@your_crop_image.jpg"

The Laravel backend automatically calls this service when
a farmer uploads a crop image through the app.
=============================================================
"""

from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from models.predictor import CropDiseasePredictor
import uvicorn
import io
import os

# Create the FastAPI app
app = FastAPI(
    title="Kisan Smart Assistant — AI Service",
    description="AI-powered crop disease detection microservice",
    version="1.0.0"
)

# Allow Laravel backend to call this API (CORS)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:8000", "http://127.0.0.1:8000"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize the predictor (loads the AI model)
predictor = CropDiseasePredictor()


@app.get("/")
def root():
    """Health check — tells Laravel the AI service is running"""
    return {
        "service": "Kisan AI Microservice",
        "status": "running",
        "version": "1.0.0",
        "endpoint": "POST /predict — Upload crop image for disease detection"
    }


@app.get("/health")
def health():
    """Health check used by monitoring tools"""
    return {"status": "healthy"}


@app.post("/predict")
async def predict_disease(file: UploadFile = File(...)):
    """
    Main AI endpoint — Receives image, returns disease prediction.
    
    Expected by Laravel (ImageAnalysisController):
    {
        "disease": "Wheat Leaf Rust",
        "confidence": 94.5,
        "recommendation": "Treatment advice here..."
    }
    """
    # Step 1: Validate file type
    allowed_types = ["image/jpeg", "image/jpg", "image/png", "image/webp"]
    if file.content_type not in allowed_types:
        raise HTTPException(
            status_code=400,
            detail=f"Invalid file type: {file.content_type}. Only JPEG, PNG, WebP allowed."
        )

    # Step 2: Read image bytes
    try:
        image_bytes = await file.read()
        if len(image_bytes) == 0:
            raise HTTPException(status_code=400, detail="Empty file received")
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"Could not read image: {str(e)}")

    # Step 3: Run prediction
    try:
        result = predictor.predict(image_bytes, filename=file.filename)
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Prediction failed: {str(e)}")

    return JSONResponse(content=result)


if __name__ == "__main__":
    uvicorn.run("main:app", host="0.0.0.0", port=8001, reload=True)
