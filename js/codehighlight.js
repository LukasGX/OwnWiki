function openModal(text, showCloseBtn = true) {
	const modal = document.createElement("div");
	modal.classList.add("modal");

	const modalC = document.createElement("div");
	modalC.classList.add("modal-content");
	modalC.innerHTML = text;

	const close = document.createElement("i");
	close.classList.add("fas", "fa-xmark", "close");
	close.onclick = function () {
		this.parentElement.parentElement.remove();
	};

	if (showCloseBtn) modalC.appendChild(close);
	modal.appendChild(modalC);
	document.body.appendChild(modal);
}

function openWordlistModal(words) {
	let wordsReadable = "";
	words.forEach((word) => {
		wordsReadable += `<li>${word}</li>`;
	});

	openModal(`
	<h2>Wortliste</h2>
	<ul>
		${wordsReadable}
	</ul>
	`);
}

async function editRule(rule) {
	// fetch rule data
	const response = await fetch("backend/autoCheckRuleGet.php", {
		method: "POST",
		headers: { "Content-Type": "application/json" },
		body: JSON.stringify({ "rule-id": rule }),
	});
	const data = await response.json();

	// auto gen later
	const types = { "diff-length": "diff-length", wordlist: "wordlist", "capital-ratio": "capital-ratio", "repeat-word": "repeat-word" };
	const checks = { gt: ">=", lt: "<=", tf: "schlägt an" };

	openModal(`
	<h2>Regel bearbeiten</h2>
	<input type='hidden' name='rule-id' value='${rule}'>
	<input type='checkbox' name='rule-active' id='checkbox-rule-active' value='true' ${data.enabled === true ? "checked" : ""}> Aktiviert<br><br>
	<select name='pattern-type' id='select-pattern-type'>
		${Object.keys(types)
			.map((t) => (t == (data.pattern && data.pattern.type) ? `<option value='${t}' selected>${types[t]}</option>` : `<option value='${t}'>${types[t]}</option>`))
			.join("")}
	</select>
	<select name='pattern-check' id='select-pattern-check'>
		${Object.keys(checks)
			.map((c) => (c == (data.pattern && data.pattern.check) ? `<option value='${c}' selected>${checks[c]}</option>` : `<option value='${c}'>${checks[c]}</option>`))
			.join("")}
	</select>
	<input type='text' name='pattern-threshold' id='input-pattern-threshold' placeholder='Schwellwert' value='${data.pattern.threshold ?? ""}'>
	<input type='text' name='pattern-wordlist' id='input-pattern-wordlist' placeholder='Wortliste' style='display: none;'>
	<button id='send' class='full'>Speichern</button>
	`);

	sel2();

	$("#select-pattern-type").on("change", function (e) {
		const value = $(this).val();
		if (value == "wordlist") {
			$("#input-pattern-wordlist").show();
			$("#select-pattern-check").val("tf").trigger("change");
		} else $("#input-pattern-wordlist").hide();
	});

	$("#select-pattern-check").on("change", function (e) {
		const value = $(this).val();
		if (value == "tf") $("#input-pattern-threshold").hide();
		else $("#input-pattern-threshold").show();
	});

	$("#select-pattern-type").trigger("change");

	$("#send").on("click", async function () {
		const type = $("#select-pattern-type").val();
		const check = $("#select-pattern-check").val();
		const threshold = $("#input-pattern-threshold").val();
		let words = $("#input-pattern-wordlist")
			.val()
			.split(",")
			.map((w) => w.trim());
		words = words.filter((w) => w.length > 0);
		if (type == "wordlist" && words.length == 0) {
			alert("Bitte mindestens ein Wort in die Wortliste einfügen.");
			return;
		}
		if ((type == "diff-length" || type == "capital-ratio") && (isNaN(threshold) || threshold.length == 0)) {
			alert("Bitte einen gültigen Schwellwert eingeben.");
			return;
		}
		if (type == "repeat-word" && (isNaN(threshold) || threshold.length == 0 || parseInt(threshold) < 2)) {
			alert("Bitte einen gültigen Schwellwert (mindestens 2) eingeben.");
			return;
		}
		const ruleID = $("input[name='rule-id']").val();
		const response = await fetch("backend/autoCheckRuleEdit.php", {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
			},
			body: JSON.stringify({
				"rule-id": ruleID,
				"rule-active": $("#checkbox-rule-active").is(":checked") ? "true" : "false",
				"pattern-type": type,
				"pattern-check": check,
				"pattern-threshold": threshold,
				"pattern-words": words,
			}),
		});
		const data = await response.json();
		if (data.success) {
			window.location.reload();
		} else if (data.error) {
			alert(`Fehler: ${data.error}`);
		}
	});
}

function deleteRule(rule) {
	openModal(
		`
	<h2>Regel löschen</h2>
	<p>Möchten Sie die Regel wirklich löschen? Das kann nicht rückgängig gemacht werden!</p>
	<button id='confirmDelete' class='full extDeleteButton'>Löschen</button>
	<button id='cancelDelete' class='full cancelButton'>Abbrechen</button>
	`,
		false
	);

	$("#cancelDelete").on("click", function () {
		$(".modal").remove();
	});

	$("#confirmDelete").on("click", async function () {
		fetch("backend/autoCheckRuleDelete.php", {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({ "rule-id": rule }),
		}).then((response) => {
			if (response.ok) {
				window.location.reload();
			} else {
				alert("Fehler beim Löschen der Regel.");
			}
		});
	});
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
			openModal(
				`
                <h2>Bearbeitung blockiert</h2>
                <p>
                    Die Bearbeitung wurde von folgender automatischen Regel blockiert: <span class="codeh">${data.rule}</span>
                </p>
            `,
				false
			);
		} else if (data.error == "Warn") {
			const extraInfo = data.extraInfo ?? "";
			openModal(
				`
                <h2>Achtung</h2>
                <p>
                    Die Bearbeitung verstößt möglicherweise gegen folgende Regel: <span class="codeh">${data.rule}</span><br />
                    ${extraInfo}
                </p>
            `,
				false
			);
		} else {
			openModal(
				`
                <h2>Fehler</h2>
                <p>Ein Fehler ist aufgetreten.</p>
            `,
				true
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
