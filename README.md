# streamyard-poc


# Multi-Camera Live Streaming MVP

This is a **Multi-Camera Live Streaming MVP** built with Laravel, WebRTC, and MySQL.  
It allows hosts to stream video from multiple cameras, switch feeds, invite guests, play pre-recorded videos, and schedule streams.

---

## **Features**

- Multi-camera live streaming with camera switching
- Upload pre-recorded video clips
- Play uploaded videos during live stream
- Schedule pre-recorded streams to go live
- Host/Guest interface with real-time video grid
- Basic control panel: start/stop stream, switch camera, play video

---

## **Tech Stack**

- **Backend:** Laravel (PHP)
- **Frontend:** HTML, JS, WebRTC
- **Database:** MySQL
- **Real-time Signaling:** Laravel Echo Server + Socket.IO
- **Server Requirements:** Node.js (18+), npm, PHP 8+, MySQL 8+

---

## **Installation & Setup**

### 1. Clone Repository

```bash
git clone <your-repo-url>
cd streamyard-poc
