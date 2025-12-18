document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("addNoteBtn");
  if (!btn) return;

  const noteText = document.getElementById("noteText");
  const notesList = document.getElementById("notesList");
  const errorBox = document.getElementById("noteError");
  const csrfToken = document.getElementById("csrfToken").value;

  btn.addEventListener("click", async () => {
    errorBox.style.display = "none";
    console.log("BUTTON GOT CLICKED")
    const comment = noteText.value.trim();
    const contactId = btn.dataset.contactId;

    if (!comment) {
      errorBox.textContent = "Note cannot be empty.";
      errorBox.style.display = "block";
      return;
    }

    const data = new URLSearchParams();
    data.append("contact_id", contactId);
    data.append("comment", comment);
    data.append("csrf_token", csrfToken);

    const res = await fetch("../public/ajax/add_note.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: data.toString()
    });

    const json = await res.json();
    console.log("AJAX RESPONSE:", json);
    if (!json.success) {
      errorBox.textContent = json.message;
      errorBox.style.display = "block";
      return;
    }

    document.getElementById("noNotesMsg")?.remove();

    const note = json.note;
    const div = document.createElement("div");
    div.className = "note";
    div.innerHTML = `
      <div class="note-author">${note.author}</div>
      <div class="note-body">${note.comment.replace(/\n/g, "<br>")}</div>
      <div class="note-date">${note.created_at}</div>
    `;

    notesList.prepend(div);
    noteText.value = "";
  });
});
