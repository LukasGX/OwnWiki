function openModal(text, showCloseBtn = true) {
	const modal = document.createElement("div");
	modal.classList.add("modal");

	const modalC = document.createElement("div");
	modalC.classList.add("modal-content");
	modalC.innerHTML = text;

	const close = document.createElement("i");
	close.classList.add("fas", "fa-xmark", "close");

	if (showCloseBtn) modalC.appendChild(close);
	modal.appendChild(modalC);
	document.body.appendChild(modal);
}

const textareaElement = document.querySelector(".code");
const previewButton = document.querySelector(".preview-button");
const previewOutput = document.querySelector(".code-preview");
const saveButton = document.querySelector(".save-button");

previewButton.addEventListener("click", async function () {
	previewOutput.innerHTML = "<p>Loading...</p>";

	const codeContent = textareaElement.value;

	const response = await fetch("backend/renderAPI.php", {
		method: "POST",
		headers: {
			"Content-Type": "application/json",
		},
		body: JSON.stringify({ text: codeContent }),
	});
	const data = await response.json();

	previewOutput.innerHTML = "";

	if (data.error) {
		previewOutput.innerHTML = `<p class="error">ERROR: ${data.error}</p>`;
		return;
	}

	if (data.success) {
		previewOutput.innerHTML = data.success;
	}
});

saveButton.addEventListener("click", async function () {
	const urlParams = new URLSearchParams(window.location.search);
	const titleFromGet = urlParams.get("t") || "";
	const filename = urlParams.get("f") || "";

	const codeContent = textareaElement.value;

	let response;

	if (filename.endsWith("create")) {
		response = await fetch("backend/saveAPI.php", {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
			},
			body: JSON.stringify({
				text: codeContent,
				title: titleFromGet,
			}),
		});
	} else if (filename.endsWith("edit")) {
		response = await fetch("backend/editAPI.php", {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
			},
			body: JSON.stringify({
				text: codeContent,
				title: titleFromGet,
			}),
		});
	} else {
		console.error(`${filename} doesn't match any!`);
		return;
	}

	const data = await response.json();
	if (data.success) {
		window.location.href = `?f=${encodeURIComponent(titleFromGet)}`;
	} else if (data.error) {
		if (data.error == "Not allowed") {
			const extraInfo = data.extraInfo ?? "";
			const info = data.extraInfo ? data.rule + ": " + extraInfo : data.rule;
			openModal(
				`
                <h2>Bearbeitung blockiert</h2>
                <p>
                    Die Bearbeitung wurde von einer automatischen Regel blockiert.<br />
                    Wenn du denkst, das ist ein Fehler, dann melde dich mit folgender Info:
                </p>
                <spam class="codeh">${info}</span>
            `,
				false
			);
		}
	} else {
		openModal(
			`
            <h2>Fehler</h2>
            <p>Ein Fehler ist aufgetreten.</p>
        `,
			true
		);
	}
});
