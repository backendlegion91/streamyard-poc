<!DOCTYPE html>
<html>
<head>
    <title>Multi-Camera Stream</title>
</head>
<body>
<h1>Multi-Camera Live Stream</h1>

<video id="localVideo" autoplay playsinline></video>
<video id="remoteVideo" autoplay playsinline></video>
<button onclick="switchCamera()">Switch Camera</button>

<form action="/upload-video" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="text" name="title" placeholder="Video title" required>
    <input type="file" name="video" accept="video/*" required>
    <button type="submit">Upload</button>
</form>

<form action="/schedule-video" method="POST">
    @csrf
    <select name="video_id">
        @foreach($videos as $video)
            <option value="{{ $video->id }}">{{ $video->title }}</option>
        @endforeach
    </select>
    <input type="datetime-local" name="scheduled_at" required>
    <button type="submit">Schedule</button>
</form>




<script src="https://cdn.socket.io/4.6.1/socket.io.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.1/dist/echo.iife.js"></script>

<script>
const socket = io('http://127.0.0.1:6001'); // Echo server
let localStream;
let peers = {}; // store RTCPeerConnections
const localVideo = document.getElementById('localVideo');
const remoteContainer = document.createElement('div');
document.body.appendChild(remoteContainer);

async function initCamera() {
    localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
    localVideo.srcObject = localStream;

    socket.emit('join-stream', { userId: Date.now() }); // simple unique ID
}

// Handle signaling messages
socket.on('user-joined', async ({ userId }) => {
    const pc = new RTCPeerConnection();
    peers[userId] = pc;

    localStream.getTracks().forEach(track => pc.addTrack(track, localStream));

    const remoteVideo = document.createElement('video');
    remoteVideo.autoplay = true;
    remoteVideo.playsInline = true;
    remoteContainer.appendChild(remoteVideo);

    pc.ontrack = e => remoteVideo.srcObject = e.streams[0];

    // Create and send offer
    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);
    socket.emit('offer', { to: userId, offer });
});

socket.on('offer', async ({ from, offer }) => {
    const pc = new RTCPeerConnection();
    peers[from] = pc;

    localStream.getTracks().forEach(track => pc.addTrack(track, localStream));

    const remoteVideo = document.createElement('video');
    remoteVideo.autoplay = true;
    remoteVideo.playsInline = true;
    remoteContainer.appendChild(remoteVideo);

    pc.ontrack = e => remoteVideo.srcObject = e.streams[0];

    await pc.setRemoteDescription(offer);
    const answer = await pc.createAnswer();
    await pc.setLocalDescription(answer);
    socket.emit('answer', { to: from, answer });
});

socket.on('answer', async ({ from, answer }) => {
    await peers[from].setRemoteDescription(answer);
});

initCamera();
</script>

</body>
</html>
