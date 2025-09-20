<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Multi-Camera Live Stream</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: #0f111a; color: #fff; }

    .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }

    header { text-align: center; margin-bottom: 30px; }
    header h1 {
        font-size: 2.5rem; font-weight: 700;
        background: linear-gradient(90deg, #4f46e5, #22d3ee);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }

    /* Video Section */
    .video-section { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; justify-content: center; }

    .video-card { position: relative; border-radius: 15px; overflow: hidden; border: 3px solid #4f46e5; box-shadow: 0 10px 20px rgba(0,0,0,0.4); transition: transform 0.3s; }
    .video-card:hover { transform: scale(1.05); }

    .video-card video { width: 100%; display: block; border-radius: 15px; }

    .badge {
        position: absolute; top: 10px; left: 10px;
        background: #ef4444; color: #fff;
        padding: 4px 8px; border-radius: 8px;
        font-size: 0.8rem; font-weight: 600;
        box-shadow: 0 2px 6px rgba(0,0,0,0.4);
    }

    .participant-name {
        position: absolute; bottom: 10px; left: 10px;
        background: rgba(0,0,0,0.6); padding: 4px 8px; border-radius: 8px;
        font-size: 0.85rem; font-weight: 500;
    }

    #remoteContainer { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 15px; flex: 1; }

    .local-controls { text-align: center; margin-top: 10px; }

    /* Forms */
    form {
        background: linear-gradient(145deg, #1f2233, #25283e);
        padding: 20px; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.5);
        margin-bottom: 25px;
    }

    form h2 { margin-bottom: 15px; font-weight: 600; color: #22d3ee; }

    input[type="text"], input[type="file"], input[type="datetime-local"], select {
        width: 100%; padding: 10px 15px; margin: 10px 0;
        border-radius: 10px; border: 1px solid #555; background: #0f111a; color: #fff;
    }

    button {
        background: linear-gradient(90deg, #4f46e5, #22d3ee);
        color: #fff; border: none; padding: 12px 20px; border-radius: 10px;
        font-weight: 600; transition: transform 0.2s, box-shadow 0.2s;
    }

    button:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.4); }

    @media (max-width: 768px) { .video-section { flex-direction: column; align-items: center; } }
</style>
</head>
<body>

<div class="container">
    <header><h1>Multi-Camera Live Stream</h1></header>

    <!-- Video Section -->
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

    <!-- Upload Form -->
    <form action="/upload-video" method="POST" enctype="multipart/form-data">
        @csrf
        <h2>Upload Video</h2>
        <input type="text" name="title" placeholder="Video title" required>
        <input type="file" name="video" accept="video/*" required>
        <button type="submit">Upload</button>
    </form>

    <!-- Schedule Video Form -->
    <form action="/schedule-video" method="POST">
        @csrf
        <h2>Schedule Pre-recorded Stream</h2>
        <select name="video_id">
            @foreach($videos as $video)
                <option value="{{ $video->id }}">{{ $video->title }}</option>
            @endforeach
        </select>
        <input type="datetime-local" name="scheduled_at" required>
        <button type="submit">Schedule</button>
    </form>
</div>

<script src="https://cdn.socket.io/4.6.1/socket.io.min.js"></script>
<script>
const socket = io('http://127.0.0.1:6001');
let localStream;
let peers = {};
const localVideo = document.getElementById('localVideo');
const remoteContainer = document.getElementById('remoteContainer');

async function initCamera() {
    localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
    localVideo.srcObject = localStream;
    socket.emit('join-stream', { userId: Date.now(), name: "You" });
}

socket.on('user-joined', async ({ userId, name }) => {
    const pc = createPeerConnection(userId, name);
    peers[userId] = pc;
    localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
    const remoteVideoCard = createRemoteVideo(userId, name);
    pc.ontrack = e => remoteVideoCard.querySelector('video').srcObject = e.streams[0];
    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);
    socket.emit('offer', { to: userId, offer });
});

socket.on('offer', async ({ from, offer, name }) => {
    const pc = createPeerConnection(from, name);
    peers[from] = pc;
    localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
    const remoteVideoCard = createRemoteVideo(from, name);
    pc.ontrack = e => remoteVideoCard.querySelector('video').srcObject = e.streams[0];
    await pc.setRemoteDescription(offer);
    const answer = await pc.createAnswer();
    await pc.setLocalDescription(answer);
    socket.emit('answer', { to: from, answer });
});

socket.on('answer', async ({ from, answer }) => {
    if (peers[from]) await peers[from].setRemoteDescription(answer);
});

function createPeerConnection(userId, name="Guest") {
    const pc = new RTCPeerConnection();
    pc.onicecandidate = e => {
        if (e.candidate) socket.emit('ice-candidate', { to: userId, candidate: e.candidate });
    };
    return pc;
}

function createRemoteVideo(userId, name="Guest") {
    const card = document.createElement('div');
    card.className = 'video-card';
    const badge = document.createElement('span');
    badge.className = 'badge';
    badge.textContent = 'LIVE';
    const video = document.createElement('video');
    video.autoplay = true; video.playsInline = true;
    const participantName = document.createElement('div');
    participantName.className = 'participant-name';
    participantName.textContent = name;
    card.appendChild(badge);
    card.appendChild(video);
    card.appendChild(participantName);
    remoteContainer.appendChild(card);
    return card;
}

async function switchCamera() {
    const devices = await navigator.mediaDevices.enumerateDevices();
    const videoDevices = devices.filter(d => d.kind === 'videoinput');

    if(videoDevices.length < 2) {
        alert("No secondary camera found!");
        return;
    }

    // Get current camera index
    const currentDeviceId = localStream.getVideoTracks()[0].getSettings().deviceId;
    const currentIndex = videoDevices.findIndex(d => d.deviceId === currentDeviceId);

    // Select next camera
    const nextIndex = (currentIndex + 1) % videoDevices.length;
    const nextDeviceId = videoDevices[nextIndex].deviceId;

    // Get new stream from next camera
    const newStream = await navigator.mediaDevices.getUserMedia({ 
        video: { deviceId: { exact: nextDeviceId } }, 
        audio: false // don't replace audio
    });

    const newVideoTrack = newStream.getVideoTracks()[0];

    // Replace track in local stream
    const oldTrack = localStream.getVideoTracks()[0];
    localStream.removeTrack(oldTrack);
    oldTrack.stop();
    localStream.addTrack(newVideoTrack);

    // Update local video element
    localVideo.srcObject = null;
    localVideo.srcObject = localStream;

    // Replace track for all peer connections
    Object.values(peers).forEach(pc => {
        const sender = pc.getSenders().find(s => s.track.kind === 'video');
        if(sender) sender.replaceTrack(newVideoTrack);
    });
}


initCamera();
</script>
</body>
</html>
