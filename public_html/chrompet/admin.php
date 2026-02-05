<?php
require 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle transcript update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_transcript'])) {
    $id = (int)$_POST['id'];
    $transcript = trim($_POST['transcript']);
    $stmt = $pdo->prepare("UPDATE audios SET transcript = ? WHERE id = ?");
    $stmt->execute([$transcript, $id]);
    echo "<script>alert('Transcript updated');</script>";
}

// Fetch all audios (use audio_order to stay consistent with frontend ordering)
$stmt = $pdo->query("SELECT * FROM audios ORDER BY audio_order ASC, id ASC");
$audios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin - Audio Manager</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="grey lighten-4">

<div class="container">
  <div class="row valign-wrapper" style="margin-top:16px; margin-bottom: 10px;">
    <div class="col s12 m7">
      <h4 style="margin:0;">🎙️ Admin - Manage Audio Files</h4>
    </div>
    <div class="col s12 m5 right-align" style="margin-top:8px;">
      <a href="access_logs.php" class="btn grey">View Access Logs</a>
    </div>
  </div>

  <!-- Upload new audio -->
  <div class="card">
    <div class="card-content">
      <span class="card-title">Upload Audio</span>
      <form action="upload.php" method="POST" enctype="multipart/form-data">
        <div class="file-field input-field">
          <div class="btn blue">
            <span>Select Audio (.m4a / .mp3)</span>
            <input type="file" name="audio" accept=".m4a,.mp3">
          </div>
          <div class="file-path-wrapper">
            <input class="file-path validate" type="text" placeholder="Upload new audio file">
          </div>
        </div>
        <button type="submit" class="btn green">Upload</button>
      </form>
    </div>
  </div>

  <!-- Existing audios -->
  <div id="audio-container">
  <?php foreach ($audios as $a): ?>
    <div class="card blue lighten-5 z-depth-1 audio-card" 
         draggable="true" 
         data-audio-id="<?= $a['id'] ?>">
      <div class="card-content">
        <span class="card-title" style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
          <span>🎧 <?= htmlspecialchars($a['filename']) ?></span>
          <button class="btn-small red delete-audio" title="Delete audio" aria-label="Delete audio" data-audio-id="<?= $a['id'] ?>">🗑️</button>
        </span>

        <!-- Audio player -->
        <audio controls preload="metadata" style="width:100%; margin-top:10px;">
          <source src="uploads/<?= htmlspecialchars($a['filename']) ?>" type="audio/mp4">
          <?php
            $base = pathinfo($a['filename'], PATHINFO_FILENAME);
            if (file_exists("uploads/$base.mp3")):
          ?>
            <source src="uploads/<?= $base ?>.mp3" type="audio/mpeg">
          <?php endif; ?>
          Your browser does not support audio playback.
        </audio>

        <!-- Single transcript -->
        <form method="POST" style="margin-top:10px;">
          <input type="hidden" name="id" value="<?= $a['id'] ?>">
          <div class="input-field">
            <textarea name="transcript" class="materialize-textarea"><?= htmlspecialchars($a['transcript'] ?? '') ?></textarea>
            <label class="active">Transcript</label>
          </div>
          <button type="submit" name="update_transcript" class="btn blue">💾 Save Transcript</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<button id="save-order" class="btn green" style="margin:20px 0;">💾 Save Order</button>


<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("audio-container");
  let dragEl = null;

  container.addEventListener("dragstart", e => {
    if (e.target.classList.contains("audio-card")) {
      dragEl = e.target;
      e.dataTransfer.effectAllowed = "move";
      e.target.classList.add("dragging");
    }
  });

  container.addEventListener("dragend", e => {
    e.target.classList.remove("dragging");
    dragEl = null;
  });

  container.addEventListener("dragover", e => {
    e.preventDefault();
    const after = getDragAfterElement(container, e.clientY);
    if (!after) container.appendChild(dragEl);
    else container.insertBefore(dragEl, after);
  });

  function getDragAfterElement(container, y) {
    const elements = [...container.querySelectorAll(".audio-card:not(.dragging)")];
    return elements.reduce((closest, el) => {
      const box = el.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) return { offset, element: el };
      return closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  }

  // Save order to server
  document.getElementById("save-order").addEventListener("click", () => {
    const order = [...container.querySelectorAll(".audio-card")].map((el, idx) => ({
  id: el.dataset.audioId,
  audio_order: idx + 1
}));

fetch("reorder.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ audios: order })
})

    .then(res => res.json())
    .then(data => {
      if (data.success) M.toast({html: '✅ Order saved', classes: 'green'});
      else M.toast({html: '❌ Error saving order', classes: 'red'});
    });
  });

  // Delete audio handler
  container.addEventListener('click', async (e) => {
    const btn = e.target.closest('.delete-audio');
    if (!btn) return;
    const card = btn.closest('.audio-card');
    const id = btn.getAttribute('data-audio-id');
    if (!id || !card) return;
    if (!confirm('Delete this audio and its transcript? This cannot be undone.')) return;
    try {
      const res = await fetch('delete_audio.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: Number(id) })
      });
      const data = await res.json();
      if (data.success) {
        card.remove();
        M.toast({ html: '🗑️ Deleted', classes: 'green' });
      } else {
        M.toast({ html: '❌ ' + (data.message || 'Delete failed'), classes: 'red' });
      }
    } catch (err) {
      M.toast({ html: '❌ Network error', classes: 'red' });
    }
  });
});
</script>
