// ---- CONFIG ----
const API_BASE = "http://localhost:8080/api"; // 🔁 change if using tunnel

// ---- DOM elements ----
const board = document.getElementById("board");
const boardContainer = document.getElementById("board-container");
const usernameInput = document.getElementById("wall-username");
const contentInput = document.getElementById("wall-content");
const charCount = document.getElementById("char-count");
const postBtn = document.getElementById("post-note-btn");
const postError = document.getElementById("post-error");
const zoomDisplay = document.getElementById("zoom-display");
const zoomInBtn = document.getElementById("zoom-in");
const zoomOutBtn = document.getElementById("zoom-out");
const addNoteDiv = document.getElementById("add-note");
const pullTab = document.getElementById("pull-tab");

// ---- STATE ----
let zoomLevel = 1;
let offsetX = 0,
  offsetY = 0;
let isPanning = false;
let startPanX, startPanY, initialOffsetX, initialOffsetY;
let isDraggingNote = false;

// ---- CURTAIN TOGGLE (smooth slide) ----
pullTab.addEventListener("click", () => {
  addNoteDiv.classList.toggle("hidden");
  if (addNoteDiv.classList.contains("hidden")) {
    pullTab.style.background = "#aa6f3e";
  } else {
    pullTab.style.background = "#c47a44";
  }
});

// ---- ZOOM & PAN LOGIC (mouse + touch) ----
function updateBoardTransform() {
  zoomDisplay.textContent = `${Math.round(zoomLevel * 100)}%`;
  board.style.transform = `translate(${offsetX}px, ${offsetY}px) scale(${zoomLevel})`;
}

zoomInBtn.addEventListener("click", () => {
  zoomLevel = Math.min(zoomLevel + 0.15, 3);
  updateBoardTransform();
});
zoomOutBtn.addEventListener("click", () => {
  zoomLevel = Math.max(zoomLevel - 0.15, 0.3);
  updateBoardTransform();
});

boardContainer.addEventListener("wheel", (e) => {
  e.preventDefault();
  const delta = e.deltaY > 0 ? -0.1 : 0.1;
  zoomLevel = Math.min(Math.max(zoomLevel + delta, 0.3), 3);
  updateBoardTransform();
});

// --- Mouse panning ---
boardContainer.addEventListener("mousedown", (e) => {
  if (e.button !== 0 || isDraggingNote) return;
  isPanning = true;
  startPanX = e.clientX;
  startPanY = e.clientY;
  initialOffsetX = offsetX;
  initialOffsetY = offsetY;
  boardContainer.style.cursor = "grabbing";
  e.preventDefault();
});

document.addEventListener("mousemove", (e) => {
  if (!isPanning) return;
  offsetX = initialOffsetX + (e.clientX - startPanX);
  offsetY = initialOffsetY + (e.clientY - startPanY);
  updateBoardTransform();
});
document.addEventListener("mouseup", () => {
  if (isPanning) {
    isPanning = false;
    boardContainer.style.cursor = "grab";
  }
});

// --- Touch panning (for phones) ---
boardContainer.addEventListener("touchstart", (e) => {
  if (isDraggingNote) return;
  const touch = e.touches[0];
  isPanning = true;
  startPanX = touch.clientX;
  startPanY = touch.clientY;
  initialOffsetX = offsetX;
  initialOffsetY = offsetY;
  e.preventDefault(); // prevent page scroll
});

boardContainer.addEventListener("touchmove", (e) => {
  if (!isPanning) return;
  const touch = e.touches[0];
  offsetX = initialOffsetX + (touch.clientX - startPanX);
  offsetY = initialOffsetY + (touch.clientY - startPanY);
  updateBoardTransform();
  e.preventDefault();
});

boardContainer.addEventListener("touchend", () => {
  if (isPanning) {
    isPanning = false;
  }
});

// ---- FETCH & RENDER ----
async function fetchNotes() {
  try {
    const res = await fetch(`${API_BASE}/get_notes.php?limit=50`);
    const notes = await res.json();
    if (Array.isArray(notes)) {
      board.innerHTML = "";
      notes.forEach((note) => createNoteElement(note));
    }
  } catch (err) {
    console.error("Fetch notes error:", err);
  }
}

function createNoteElement(note) {
  const noteEl = document.createElement("div");
  noteEl.className = "note";
  noteEl.dataset.id = note.id;
  noteEl.dataset.posX = note.pos_x;
  noteEl.dataset.posY = note.pos_y;
  noteEl.style.left = `${note.pos_x}px`;
  noteEl.style.top = `${note.pos_y}px`;

  const timestamp = new Date(note.created_at).toLocaleString();
  noteEl.innerHTML = `
      <div class="note-header">
        <span>📌 ${escapeHtml(note.username)}</span>
        <span>${timestamp}</span>
      </div>
      <div class="note-content">${escapeHtml(note.content)}</div>
    `;
  board.appendChild(noteEl);
  makeDraggable(noteEl);
}

// ---- DRAGGABLE NOTES (mouse + touch) ----
function makeDraggable(el) {
  let isDragActive = false;
  let startMouseX, startMouseY, startLeft, startTop;

  const onDragStart = (e) => {
    // For touch, e.button is undefined, so we allow if it's a touchstart
    if (e.type === "mousedown" && e.button !== 0) return;
    e.preventDefault();
    isDragActive = true;
    isDraggingNote = true;

    const clientX = e.clientX ?? e.touches?.[0]?.clientX;
    const clientY = e.clientY ?? e.touches?.[0]?.clientY;
    startMouseX = clientX;
    startMouseY = clientY;
    startLeft = parseFloat(el.style.left) || 0;
    startTop = parseFloat(el.style.top) || 0;
    el.classList.add("dragging");

    document.addEventListener("mousemove", onDragMove);
    document.addEventListener("mouseup", onDragEnd);
    document.addEventListener("touchmove", onDragMove, { passive: false });
    document.addEventListener("touchend", onDragEnd);
  };

  const onDragMove = (e) => {
    if (!isDragActive) return;
    e.preventDefault();
    let clientX = e.clientX ?? e.touches?.[0]?.clientX;
    let clientY = e.clientY ?? e.touches?.[0]?.clientY;
    let dx = (clientX - startMouseX) / zoomLevel;
    let dy = (clientY - startMouseY) / zoomLevel;
    let newLeft = startLeft + dx;
    let newTop = startTop + dy;

    // Boundaries (optional)
    newLeft = Math.max(
      0,
      Math.min(newLeft, board.clientWidth - el.clientWidth),
    );
    newTop = Math.max(
      0,
      Math.min(newTop, board.clientHeight - el.clientHeight),
    );

    el.style.left = `${newLeft}px`;
    el.style.top = `${newTop}px`;
  };

  const onDragEnd = async () => {
    if (!isDragActive) return;
    isDragActive = false;
    isDraggingNote = false;
    el.classList.remove("dragging");
    document.removeEventListener("mousemove", onDragMove);
    document.removeEventListener("mouseup", onDragEnd);
    document.removeEventListener("touchmove", onDragMove);
    document.removeEventListener("touchend", onDragEnd);

    const finalX = parseFloat(el.style.left);
    const finalY = parseFloat(el.style.top);
    if (
      finalX !== parseFloat(el.dataset.posX) ||
      finalY !== parseFloat(el.dataset.posY)
    ) {
      try {
        await updateNotePosition(el.dataset.id, finalX, finalY);
        el.dataset.posX = finalX;
        el.dataset.posY = finalY;
      } catch (err) {
        console.warn("Position update failed, reverting");
        el.style.left = `${el.dataset.posX}px`;
        el.style.top = `${el.dataset.posY}px`;
      }
    }
  };

  el.addEventListener("mousedown", onDragStart);
  el.addEventListener("touchstart", onDragStart, { passive: false });
}

async function updateNotePosition(id, x, y) {
  const res = await fetch(`${API_BASE}/update_note_position.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      id,
      pos_x: Math.round(x),
      pos_y: Math.round(y),
    }),
  });
  if (!res.ok) throw new Error("Update failed");
}

// ---- POST NEW NOTE ----
contentInput.addEventListener("input", () => {
  charCount.textContent = contentInput.value.length;
});

postBtn.addEventListener("click", async () => {
  const username = usernameInput.value.trim();
  const content = contentInput.value.trim();
  if (!username) {
    postError.textContent = "✏️ Please enter your name";
    return;
  }
  if (!content || content.length > 300) {
    postError.textContent = "📝 Content must be 1–300 characters";
    return;
  }
  postError.textContent = "";
  postBtn.disabled = true;

  const existingNotes = document.querySelectorAll(".note");
  const defaultX = 50 + (existingNotes.length % 6) * 70;
  const defaultY = 60 + Math.floor(existingNotes.length / 6) * 80;

  try {
    const res = await fetch(`${API_BASE}/add_note.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        username,
        content,
        pos_x: defaultX,
        pos_y: defaultY,
      }),
    });
    const data = await res.json();
    if (data.success) {
      contentInput.value = "";
      charCount.textContent = "0";
      await fetchNotes();
    } else {
      throw new Error(data.error || "Server error");
    }
  } catch (err) {
    postError.textContent = "❌ Failed to post. Try again.";
    console.error(err);
  } finally {
    postBtn.disabled = false;
  }
});

function escapeHtml(str) {
  const div = document.createElement("div");
  div.textContent = str;
  return div.innerHTML;
}

// ---- INIT ----
fetchNotes();
boardContainer.style.cursor = "grab";
updateBoardTransform();
