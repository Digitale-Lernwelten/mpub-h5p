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

document.addEventListener("DOMContentLoaded", () => {
    document.body.setAttribute("iframe-referrer", String(document.referrer).split("/")[2]); 
}, false); 