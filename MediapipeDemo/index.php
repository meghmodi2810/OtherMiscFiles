<!DOCTYPE html>
<html>
<head>
  <title>MediaPipe Face Detection with PHP</title>
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/face_detection.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js"></script>
  <style>
    video, canvas {
      position: absolute;
      top: 0;
      left: 0;
      transform: scaleX(-1);
    }
  </style>
</head>
<body>
  <video id="input_video" width="640" height="480" autoplay muted></video>
  <canvas id="output_canvas" width="640" height="480"></canvas>

  <script>
    const videoElement = document.getElementById('input_video');
    const canvasElement = document.getElementById('output_canvas');
    const canvasCtx = canvasElement.getContext('2d');

    const faceDetection = new FaceDetection.FaceDetection({
      locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/${file}`
    });

    faceDetection.setOptions({
      model: 'short',
      minDetectionConfidence: 0.5
    });

    faceDetection.onResults(results => {
      canvasCtx.save();
      canvasCtx.clearRect(0, 0, canvasElement.width, canvasElement.height);
      canvasCtx.drawImage(results.image, 0, 0, canvasElement.width, canvasElement.height);

      if (results.detections.length > 0) {
        for (const detection of results.detections) {
          const box = detection.boundingBox;
          canvasCtx.beginPath();
          canvasCtx.rect(box.xCenter * canvasElement.width - box.width * canvasElement.width / 2,
                         box.yCenter * canvasElement.height - box.height * canvasElement.height / 2,
                         box.width * canvasElement.width,
                         box.height * canvasElement.height);
          canvasCtx.lineWidth = 3;
          canvasCtx.strokeStyle = 'lime';
          canvasCtx.stroke();
          canvasCtx.closePath();
        }
      }

      canvasCtx.restore();
    });

    const camera = new Camera(videoElement, {
      onFrame: async () => {
        await faceDetection.send({ image: videoElement });
      },
      width: 640,
      height: 480
    });
    camera.start();
  </script>
</body>
</html>
