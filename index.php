<?php require_once 'includes/header.php'; ?>

<style>
    /* --- เริ่มส่วน CSS ที่แก้ไข --- */
    .hero-video-container {
        height: 70vh;
        width: 100%;
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        color: white;
        overflow: hidden;
    }

    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        z-index: 0;
    }

    #hero-video {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: -1;
    }

    .hero-video-container .hero-text {
        z-index: 1;
        text-shadow: 2px 2px 8px rgba(0,0,0,0.5);
    }
    
    .hero-text h1 {
        font-size: 4rem;
        margin: 0;
        /* --- 1. ทำให้ตัวอักษรหนาขึ้น --- */
        font-weight: 900; 
        /* --- 2. เพิ่ม Animation --- */
        animation: pulse-glow 2.5s infinite alternate;
    }

    /* 3. สร้าง Keyframes สำหรับ Animation */
    @keyframes pulse-glow {
        from {
            text-shadow: 0 0 10px #fff, 0 0 20px #fff, 0 0 30px #007bff;
            transform: scale(1);
        }
        to {
            text-shadow: 0 0 20px #fff, 0 0 30px #00aaff, 0 0 40px #00aaff;
            transform: scale(1.03);
        }
    }
    
    .hero-text p {
        font-size: 1.5rem;
    }
    /* --- จบส่วน CSS ที่แก้ไข --- */

    /* CSS ส่วนอื่นๆ เหมือนเดิม */
    .section-title { text-align: center; font-size: 2.5rem; margin-bottom: 30px; color: #fff; }
    .match-results-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
    /* ... CSS อื่นๆ ... */

</style>

<div class="hero-video-container">
    <div class="hero-overlay"></div>
    <video autoplay loop muted playsinline id="hero-video">
        <source src="assets/videos/game-highlight.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <div class="hero-text">
        <h1>WELCOME TO TAE ESPORT</h1>
        <p>เส้นทางสู่ชัยชนะ เริ่มต้นที่นี่</p>
    </div>
</div>


<div class="container">
    </div>

<?php require_once 'includes/footer.php'; ?>