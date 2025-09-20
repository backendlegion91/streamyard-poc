<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Live Stream MVP</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:#0f111a;color:#fff;}
.container{max-width:1200px;margin:20px auto;padding:0 20px;}
header{text-align:center;margin-bottom:30px;}
header h1{font-size:2.5rem;font-weight:700;background:linear-gradient(90deg,#4f46e5,#22d3ee);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.video-section{display:flex;flex-wrap:wrap;gap:20px;margin-bottom:30px;justify-content:center;}
.video-card{position:relative;border-radius:15px;overflow:hidden;border:3px solid #4f46e5;box-shadow:0 10px 20px rgba(0,0,0,0.4);transition:transform 0.3s;}
.video-card:hover{transform:scale(1.05);}
.video-card video{width:100%;display:block;border-radius:15px;}
.badge{position:absolute;top:10px;left:10px;background:#ef4444;color:#fff;padding:4px 8px;border-radius:8px;font-size:0.8rem;font-weight:600;box-shadow:0 2px 6px rgba(0,0,0,0.4);}
.participant-name{position:absolute;bottom:10px;left:10px;background:rgba(0,0,0,0.6);padding:4px 8px;border-radius:8px;font-size:0.85rem;font-weight:500;}
#remoteContainer{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:15px;flex:1;}
.local-controls{text-align:center;margin-top:10px;}
form{background:linear-gradient(145deg,#1f2233,#25283e);padding:20px;border-radius:15px;box-shadow:0 10px 20px rgba(0,0,0,0.5);margin-bottom:25px;}
form h2{margin-bottom:15px;font-weight:600;color:#22d3ee;}
input[type="text"],input[type="file"],select{width:100%;padding:10px 15px;margin:10px 0;border-radius:10px;border:1px solid #555;background:#0f111a;color:#fff;}
button{background:linear-gradient(90deg,#4f46e5,#22d3ee);color:#fff;border:none;padding:12px 20px;border-radius:10px;font-weight:600;transition:transform 0.2s,box-shadow 0.2s;}
button:hover{transform:translateY(-2px);box-shadow:0 8px 16px rgba(0,0,0,0.4);}
@media(max-width:768px){.video-section{flex-direction:column;align-items:center;}}
</style>
</head>
<body>
<div class="container">
<header><h1>Multi-Camera Live Stream</h1></header>

<div class="video-section">
    <div class="video-card" style="max-width:400px;">
        <span class="badge">LIVE</span>
        <video id="localVideo" autoplay playsinline muted></video>
        <div class="participant-name">You</div>
        <div class="local-controls">
            <button onclick="switchCamera()">Switch Camera</button>
        </div>
    </div>
    <div id="remoteContainer"></div>
</div>

<form action="/upload-video" method="POST" enctype="multipart/form-data">
    @csrf
    <h2>Upload Video</h2>
    <input type="text" name="title" placeholder="Video title" required>
    <input type="file" name="video" accept="video/*" required>
    <button type="submit">Upload</button>
</form>

<form action="/schedule-video" method="POST">
    @csrf
    <h2>Schedule Pre-recorded Stream</h2>
    <select name="video_id" required>
        @foreach($videos as $video)
            <option value="{{ $video->id }}">{{ $video->title }}</option>
        @endforeach
    </select>
    <input type="text" id="scheduled_at" name="scheduled_at" placeholder="Select Date & Time" required>
    <button type="submit">Schedule</button>
</form>
</div>

<!-- Load JS libraries -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.socket.io/4.6.1/socket.io.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.11.1/echo.iife.js"></script>

<script>
flatpickr("#scheduled_at", { enableTime:true, dateFormat:"Y-m-d H:i" });

const socket = io('http://127.0.0.1:6001');

// Use separate variable to avoid redeclaration
const echoInstance = new window.Echo({
    broadcaster: 'socket.io',
    host: 'http://127.0.0.1:6001'
});

let localStream;
let peers = {};
const localVideo = document.getElementById('localVideo');
const remoteContainer = document.getElementById('remoteContainer');

// Initialize camera
async function initCamera() {
    try {
        localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        localVideo.srcObject = localStream;
        socket.emit('join-stream', { userId: Date.now(), name: "You" });
    } catch(err){
        console.error("Camera error:", err);
        alert("Cannot access camera/microphone. Check permissions.");
    }
}

// Switch camera (global function, accessible from onclick)
async function switchCamera() {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(d => d.kind === 'videoinput');
        if(videoDevices.length<2){ alert("No secondary camera found!"); return; }

        const currentDeviceId = localStream.getVideoTracks()[0].getSettings().deviceId;
        const currentIndex = videoDevices.findIndex(d => d.deviceId === currentDeviceId);
        const nextIndex = (currentIndex + 1) % videoDevices.length;
        const nextDeviceId = videoDevices[nextIndex].deviceId;

        const newStream = await navigator.mediaDevices.getUserMedia({ video:{deviceId:{exact:nextDeviceId}}, audio:false });
        const newTrack = newStream.getVideoTracks()[0];
        const oldTrack = localStream.getVideoTracks()[0];
        localStream.removeTrack(oldTrack); oldTrack.stop();
        localStream.addTrack(newTrack);
        localVideo.srcObject = null; localVideo.srcObject = localStream;

        Object.values(peers).forEach(pc=>{
            const sender = pc.getSenders().find(s=>s.track.kind==='video');
            if(sender) sender.replaceTrack(newTrack);
        });
    } catch(err){
        console.error("Switch camera error:", err);
    }
}

// Play scheduled video
async function playScheduledVideo(videoPath){
    const video = document.createElement('video');
    video.src = videoPath; video.crossOrigin="anonymous"; await video.play();
    const stream = video.captureStream();
    const newTrack = stream.getVideoTracks()[0];
    const oldTrack = localStream.getVideoTracks()[0];
    localStream.removeTrack(oldTrack); oldTrack.stop();
    localStream.addTrack(newTrack);
    localVideo.srcObject = null; localVideo.srcObject = localStream;

    Object.values(peers).forEach(pc=>{
        const sender = pc.getSenders().find(s=>s.track.kind==='video');
        if(sender) sender.replaceTrack(newTrack);
    });

    video.onended = ()=>initCamera();
}

// Listen for scheduled video events
echoInstance.channel('live-stream').listen('.scheduled-video-live', e=>{
    playScheduledVideo(e.videoPath);
});

// Initialize camera on load
window.onload = initCamera;
</script>
</body>
</html>
