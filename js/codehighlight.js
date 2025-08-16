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

	window.location.href = `?f=${encodeURIComponent(titleFromGet)}`;
});
