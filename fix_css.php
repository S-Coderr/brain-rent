<?php
$file = __DIR__ . '/assets/css/custom.css';
$content = file_get_contents($file);

// Find the position of "main {\r\n  animation: pagein 0.3s ease;\r\n}\r\n"
$pos = strpos($content, "main {\r\n  animation: pagein 0.3s ease;\r\n}");
if ($pos === false) {
    $pos = strpos($content, "main {\n  animation: pagein 0.3s ease;\n}");
}

if ($pos !== false) {
    // Keep everything up to the end of the main animation rule
    $endOfMainPos = strpos($content, "}", $pos);
    if ($endOfMainPos !== false) {
        $cleanContent = substr($content, 0, $endOfMainPos + 1);
        
        // Append the new animations and hover effects properly
        $additions = "\n
/* --- 3D Floating Animations --- */
@keyframes floatUp {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-15px); }
  100% { transform: translateY(0px); }
}

@keyframes floatSlow {
  0% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(2deg); }
  100% { transform: translateY(0px) rotate(0deg); }
}

.br-float {
  animation: floatUp 4s ease-in-out infinite;
}

.br-float-slow {
  animation: floatSlow 6s ease-in-out infinite;
}

.br-3d-image {
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(232, 116, 59, 0.15);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.br-3d-image:hover {
  transform: scale(1.05);
  box-shadow: 0 30px 60px rgba(232, 116, 59, 0.25);
}

/* Glassmorphism & Hover Glow */
.br-glass-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 8px 32px rgba(31, 38, 135, 0.07);
}

.br-hover-glow {
  transition: all 0.3s ease;
}

.br-hover-glow:hover {
  transform: translateY(-5px);
  box-shadow: 0 0 25px var(--br-gold-dim), 0 5px 15px rgba(0,0,0,0.05);
}

/* Scroll Reveal Initial States */
.reveal-up {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.8s ease-out, transform 0.8s ease-out;
}

.reveal-up.active {
  opacity: 1;
  transform: translateY(0);
}

/* Live Pulse Animation */
@keyframes pulseGlow {
  0% { box-shadow: 0 0 0 0 rgba(232, 116, 59, 0.4); }
  70% { box-shadow: 0 0 0 10px rgba(232, 116, 59, 0); }
  100% { box-shadow: 0 0 0 0 rgba(232, 116, 59, 0); }
}

.live-indicator {
  display: inline-block;
  width: 10px;
  height: 10px;
  background-color: var(--br-gold);
  border-radius: 50%;
  animation: pulseGlow 2s infinite;
  margin-right: 8px;
}
";
        file_put_contents($file, $cleanContent . $additions);
        echo "Successfully cleaned and updated custom.css";
    } else {
        echo "Could not find closing brace";
    }
} else {
    echo "Could not find main animation block";
}
