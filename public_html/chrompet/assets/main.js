// assets/main.js

document.addEventListener('DOMContentLoaded', function() {
    const players = Array.from(document.querySelectorAll('.audio-player'));
  
    // Auto-play the next audio when one ends
    players.forEach((player, idx) => {
      player.addEventListener('ended', () => {
        const next = players[idx + 1];
        if (next) {
          next.play().catch(() => {
            // autoplay blocked, do nothing
          });
          next.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      });
    });
  });
  