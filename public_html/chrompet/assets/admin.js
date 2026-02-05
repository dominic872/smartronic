// assets/admin.js

document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("audio-container");
    let dragEl = null;
  
    container.addEventListener("dragstart", (e) => {
      if (e.target.classList.contains("audio-card")) {
        dragEl = e.target;
        e.dataTransfer.effectAllowed = "move";
        e.target.classList.add("dragging");
      }
    });
  
    container.addEventListener("dragend", (e) => {
      e.target.classList.remove("dragging");
      dragEl = null;
    });
  
    container.addEventListener("dragover", (e) => {
      e.preventDefault();
      const after = getDragAfterElement(container, e.clientY);
      if (after == null) container.appendChild(dragEl);
      else container.insertBefore(dragEl, after);
    });
  
    function getDragAfterElement(container, y) {
      const elements = [...container.querySelectorAll(".audio-card:not(.dragging)")];
      return elements.reduce(
        (closest, el) => {
          const box = el.getBoundingClientRect();
          const offset = y - box.top - box.height / 2;
          if (offset < 0 && offset > closest.offset) return { offset, element: el };
          else return closest;
        },
        { offset: Number.NEGATIVE_INFINITY }
      ).element;
    }
  
    document.getElementById("save-order").addEventListener("click", () => {
      const ids = [...container.querySelectorAll(".audio-card")].map((el, idx) => ({
        id: el.dataset.audioId,
        order: idx + 1,
      }));
  
      fetch("reorder.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ audios: ids }),
      })
        .then((r) => r.json())
        .then((res) => {
          if (res.success) M.toast({ html: "Order saved", classes: "green" });
          else M.toast({ html: "Error saving order", classes: "red" });
        });
    });
  });
  