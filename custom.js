const hotSpotReplace = () => {
	const textOf = document.querySelector(".h5p-question-feedback-content-text");
	textOf.innerText = textOf.innerText.replace(" of ", " von ");
}

const hotSpotFix = () => {
	const wrapper = document.querySelector(".image-hotspot-question .image-wrapper");
	if (!wrapper) { return;}
	const observeWrapper = new MutationObserver( mutations => {
		mutations.forEach(mutation => {
			if (mutation.type === "childList") {
				hotSpotReplace();
			}
		})
	})
	observeWrapper.observe(wrapper, { childList: true });
}

window.addEventListener("load", hotSpotFix);

const referrerToClass = ref => {
	const refClassMapping = {
		"vorschau.test-dilewe.de": "dbhessen",

		"redaktionsvorschau.lasub.dilewe.de": "lasub",
		"module-sachsen.dilewe.de": "lasub",
		"lasub.staging.test-dilewe.de": "lasub",
	};
	return refClassMapping[ref] || "unknown";
};
document.addEventListener("DOMContentLoaded", () => {
	document.body.classList.add(referrerToClass(String(document.referrer).split("/")[2]));
}, false); 